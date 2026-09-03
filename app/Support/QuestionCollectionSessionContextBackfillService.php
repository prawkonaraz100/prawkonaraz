<?php

namespace App\Support;

use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use App\Models\StudySession;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class QuestionCollectionSessionContextBackfillService
{
    /**
     * Prepare a read-only report for historical course sessions.
     *
     * @return array<string, mixed>
     */
    public function preview(string $identifier, int $chunkSize = 500, int $sampleLimit = 25): array
    {
        $collection = $this->findCollection($identifier);

        return [
            'mode' => 'preview',
            'collection' => $this->collectionSummary($collection),
            ...$this->scan($collection, $chunkSize, $sampleLimit),
            'generated_at' => now()->utc()->toIso8601String(),
        ];
    }

    /**
     * Apply the backfill only when the complete preview has no blocking rows.
     *
     * @return array<string, mixed>
     */
    public function apply(string $identifier, int $chunkSize = 500, int $sampleLimit = 25): array
    {
        $collection = $this->findCollection($identifier);
        $preview = $this->scan($collection, $chunkSize, $sampleLimit);

        if ((int) $preview['blocking_sessions'] > 0) {
            return [
                'mode' => 'apply',
                'applied' => false,
                'collection' => $this->collectionSummary($collection),
                ...$preview,
                'generated_at' => now()->utc()->toIso8601String(),
            ];
        }

        $updated = 0;
        $concurrentOrAlreadyUpdated = 0;

        $this->scan($collection, $chunkSize, $sampleLimit, function (StudySession $session, array $resolution) use (&$updated, &$concurrentOrAlreadyUpdated): void {
            $affected = StudySession::query()
                ->whereKey($session->getKey())
                ->whereNull('question_collection_id')
                ->whereNull('question_module_id')
                ->update([
                    'question_collection_id' => $resolution['collection_id'],
                    'question_module_id' => $resolution['module_id'],
                    'updated_at' => now(),
                ]);

            if ($affected === 1) {
                $updated++;

                return;
            }

            $concurrentOrAlreadyUpdated++;
        });

        return [
            'mode' => 'apply',
            'applied' => true,
            'collection' => $this->collectionSummary($collection),
            ...$preview,
            'updated_sessions' => $updated,
            'concurrent_or_already_updated' => $concurrentOrAlreadyUpdated,
            'generated_at' => now()->utc()->toIso8601String(),
        ];
    }

    protected function findCollection(string $identifier): QuestionCollection
    {
        $collection = QuestionCollection::query()
            ->where(function ($query) use ($identifier): void {
                $query
                    ->where('code', $identifier)
                    ->orWhere('slug', $identifier);
            })
            ->first();

        if (! $collection instanceof QuestionCollection) {
            throw (new ModelNotFoundException)->setModel(QuestionCollection::class, [$identifier]);
        }

        return $collection;
    }

    /**
     * @param  callable(StudySession, array{collection_id:int,module_id:int|null}): void|null  $onReady
     * @return array{scanned_sessions:int,course_context_sessions:int,ready_sessions:int,ignored_sessions:int,blocking_sessions:int,blocking_reasons:array<string, int>,samples:array<int, array<string, mixed>>}
     */
    protected function scan(
        QuestionCollection $collection,
        int $chunkSize,
        int $sampleLimit,
        ?callable $onReady = null,
    ): array {
        $chunkSize = max(1, min($chunkSize, 2000));
        $sampleLimit = max(1, min($sampleLimit, 100));
        $catalog = $this->catalogFor($collection);
        $report = [
            'scanned_sessions' => 0,
            'course_context_sessions' => 0,
            'ready_sessions' => 0,
            'ignored_sessions' => 0,
            'blocking_sessions' => 0,
            'blocking_reasons' => [],
            'samples' => [],
        ];

        StudySession::query()
            ->where('license_category_id', $collection->license_category_id)
            ->whereNull('question_collection_id')
            ->whereNull('question_module_id')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($sessions) use ($collection, $catalog, $sampleLimit, $onReady, &$report): void {
                foreach ($sessions as $session) {
                    $report['scanned_sessions']++;
                    $resolution = $this->resolveSession($session, $collection, $catalog);

                    if ($resolution['state'] === 'ignored') {
                        $report['ignored_sessions']++;

                        continue;
                    }

                    $report['course_context_sessions']++;

                    if ($resolution['state'] === 'blocked') {
                        $report['blocking_sessions']++;
                        $reason = $resolution['reason'];
                        $report['blocking_reasons'][$reason] = ($report['blocking_reasons'][$reason] ?? 0) + 1;

                        if (count($report['samples']) < $sampleLimit) {
                            $report['samples'][] = $this->sessionSample($session, $reason);
                        }

                        continue;
                    }

                    $report['ready_sessions']++;

                    if ($onReady !== null) {
                        $onReady($session, [
                            'collection_id' => (int) $collection->getKey(),
                            'module_id' => $resolution['module_id'],
                        ]);
                    }
                }
            });

        ksort($report['blocking_reasons']);

        return $report;
    }

    /**
     * @return array{modules:array<int, QuestionModule>,question_ids_by_module:array<int, array<int, true>>,question_ids:array<int, true>}
     */
    protected function catalogFor(QuestionCollection $collection): array
    {
        $modules = QuestionModule::query()
            ->where('question_collection_id', $collection->getKey())
            ->get()
            ->keyBy(fn (QuestionModule $module): int => (int) $module->getKey())
            ->all();
        $moduleIds = array_keys($modules);
        $questionIdsByModule = [];
        $questionIds = [];

        if ($moduleIds !== []) {
            DB::table('question_module_question')
                ->whereIn('question_module_id', $moduleIds)
                ->orderBy('question_module_id')
                ->orderBy('question_id')
                ->get(['question_module_id', 'question_id'])
                ->each(function (object $assignment) use (&$questionIdsByModule, &$questionIds): void {
                    $moduleId = (int) $assignment->question_module_id;
                    $questionId = (int) $assignment->question_id;
                    $questionIdsByModule[$moduleId][$questionId] = true;
                    $questionIds[$questionId] = true;
                });
        }

        return [
            'modules' => $modules,
            'question_ids_by_module' => $questionIdsByModule,
            'question_ids' => $questionIds,
        ];
    }

    /**
     * @param  array{modules:array<int, QuestionModule>,question_ids_by_module:array<int, array<int, true>>,question_ids:array<int, true>}  $catalog
     * @return array{state:'ignored'|'blocked'|'ready',reason:string,module_id:int|null}
     */
    protected function resolveSession(StudySession $session, QuestionCollection $collection, array $catalog): array
    {
        $context = data_get($session->payload, 'context');

        if (! is_array($context)) {
            return $this->ignored();
        }

        $type = $context['type'] ?? null;

        if (! in_array($type, ['question_module', 'question_collection_review'], true)) {
            return $this->ignored();
        }

        $contextCollectionId = $this->positiveInteger($context['question_collection_id'] ?? null);
        $contextCollectionCode = trim((string) ($context['collection_code'] ?? ''));
        $matchesById = $contextCollectionId === (int) $collection->getKey();
        $matchesByCode = $contextCollectionCode === $collection->code;

        if (! $matchesById && ! $matchesByCode) {
            return $this->ignored();
        }

        if (! $matchesById || ! $matchesByCode) {
            return $this->blocked('collection_identity_mismatch');
        }

        $sessionQuestionIds = collect($session->payload['question_ids'] ?? [])
            ->map(fn (mixed $questionId): ?int => $this->positiveInteger($questionId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($sessionQuestionIds === []) {
            return $this->blocked('missing_session_questions');
        }

        if ($type === 'question_collection_review') {
            if ($this->positiveInteger($context['question_module_id'] ?? null) !== null) {
                return $this->blocked('review_has_module_context');
            }

            return $this->allQuestionsBelongTo($sessionQuestionIds, $catalog['question_ids'])
                ? $this->ready(null)
                : $this->blocked('session_questions_outside_collection');
        }

        $moduleId = $this->positiveInteger($context['question_module_id'] ?? null);
        $moduleCode = trim((string) ($context['module_code'] ?? ''));
        $module = $moduleId === null ? null : ($catalog['modules'][$moduleId] ?? null);

        if (! $module instanceof QuestionModule) {
            return $this->blocked('module_missing_or_not_in_collection');
        }

        if ($moduleCode === '' || $moduleCode !== $module->code) {
            return $this->blocked('module_identity_mismatch');
        }

        return $this->allQuestionsBelongTo($sessionQuestionIds, $catalog['question_ids_by_module'][$moduleId] ?? [])
            ? $this->ready($moduleId)
            : $this->blocked('session_questions_outside_module');
    }

    /**
     * @param  array<int, int>  $sessionQuestionIds
     * @param  array<int, true>  $allowedQuestionIds
     */
    protected function allQuestionsBelongTo(array $sessionQuestionIds, array $allowedQuestionIds): bool
    {
        foreach ($sessionQuestionIds as $questionId) {
            if (! isset($allowedQuestionIds[$questionId])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{state:'ignored',reason:string,module_id:null}
     */
    protected function ignored(): array
    {
        return [
            'state' => 'ignored',
            'reason' => 'not_target_collection',
            'module_id' => null,
        ];
    }

    /**
     * @return array{state:'blocked',reason:string,module_id:null}
     */
    protected function blocked(string $reason): array
    {
        return [
            'state' => 'blocked',
            'reason' => $reason,
            'module_id' => null,
        ];
    }

    /**
     * @return array{state:'ready',reason:string,module_id:int|null}
     */
    protected function ready(?int $moduleId): array
    {
        return [
            'state' => 'ready',
            'reason' => 'ready',
            'module_id' => $moduleId,
        ];
    }

    /**
     * @return array{id:int,code:string,slug:string,name:string,license_category_id:int}
     */
    protected function collectionSummary(QuestionCollection $collection): array
    {
        return [
            'id' => (int) $collection->getKey(),
            'code' => $collection->code,
            'slug' => $collection->slug,
            'name' => $collection->name,
            'license_category_id' => (int) $collection->license_category_id,
        ];
    }

    /**
     * @return array{id:int,user_id:int,status:string,context_type:mixed,collection_id:mixed,collection_code:mixed,module_id:mixed,module_code:mixed}
     */
    protected function sessionSample(StudySession $session, string $reason): array
    {
        return [
            'id' => (int) $session->getKey(),
            'user_id' => (int) $session->user_id,
            'status' => (string) $session->status,
            'reason' => $reason,
            'context_type' => data_get($session->payload, 'context.type'),
            'collection_id' => data_get($session->payload, 'context.question_collection_id'),
            'collection_code' => data_get($session->payload, 'context.collection_code'),
            'module_id' => data_get($session->payload, 'context.question_module_id'),
            'module_code' => data_get($session->payload, 'context.module_code'),
        ];
    }

    protected function positiveInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value) || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }

        return (int) $value;
    }
}
