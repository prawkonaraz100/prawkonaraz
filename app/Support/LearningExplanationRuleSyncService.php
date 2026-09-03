<?php

namespace App\Support;

use App\Models\ContentImportRun;
use App\Models\Question;
use App\Models\QuestionExplanationSyncEntry;
use App\Models\QuestionPublicExplanation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LearningExplanationRuleSyncService
{
    public const PREVIEW_KIND = 'learning_public_memory_rule_preview';

    public const APPLY_KIND = 'learning_public_memory_rule_sync';

    public const ROLLBACK_KIND = 'learning_public_memory_rule_rollback';

    public const RULE_ORIGIN_EXTRACTED = 'public_extracted';

    public const RULE_ORIGIN_CURATED = 'editorial_curated';

    public function __construct(
        protected LearningExplanationRuleSyncPlanner $planner,
    ) {}

    /**
     * @param  list<string>  $requestedExternalIds
     * @param  array<string, string>  $curatedRules
     * @return array{run:ContentImportRun, report:array<string, mixed>}
     */
    public function preview(
        array $requestedExternalIds = [],
        array $curatedRules = [],
        string $ruleOrigin = self::RULE_ORIGIN_EXTRACTED,
    ): array {
        $plan = $this->planner->plan($requestedExternalIds, $curatedRules);
        $now = now();

        $run = DB::transaction(function () use ($plan, $now, $ruleOrigin): ContentImportRun {
            $run = ContentImportRun::query()->create([
                'kind' => self::PREVIEW_KIND,
                'identifier' => $plan['manifest_checksum'],
                'status' => 'preview_ready',
                'dry_run' => true,
                'rows_total' => $plan['summary']['safe_rule_groups'],
                'questions_total' => $plan['summary']['planned_question_rows'],
                'errors_count' => 0,
                'warnings_count' => $plan['summary']['skipped_groups'],
                'summary' => $this->previewSummary($plan, $ruleOrigin),
                'started_at' => $now,
                'completed_at' => $now,
            ]);

            collect($plan['entries'])
                ->chunk(500)
                ->each(function (Collection $entries) use ($run, $now): void {
                    QuestionExplanationSyncEntry::query()->insert(
                        $entries
                            ->map(fn (array $entry): array => [
                                ...$entry,
                                'content_import_run_id' => $run->getKey(),
                                'applied_at' => null,
                                'rolled_back_at' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ])
                            ->all(),
                    );
                });

            return $run;
        });

        return [
            'run' => $run,
            'report' => [
                'mode' => 'preview',
                'run_id' => $run->getKey(),
                'manifest_checksum' => $plan['manifest_checksum'],
                'summary' => $plan['summary'],
                'skipped_groups' => $plan['skipped_groups'],
                'planned_entries' => $plan['entries'],
            ],
        ];
    }

    /**
     * @return array{run:ContentImportRun, report:array<string, mixed>}
     */
    public function applyPreview(
        int $previewRunId,
        string $confirmation,
        string $expectedRuleOrigin = self::RULE_ORIGIN_EXTRACTED,
    ): array {
        $previewRun = $this->previewRun($previewRunId, $expectedRuleOrigin);
        $checksum = $this->manifestChecksumForRun($previewRun);

        if (! hash_equals($checksum, trim($confirmation))) {
            throw ValidationException::withMessages([
                'confirm' => 'Checksum preview nie jest zgodny z aktualnym manifestem.',
            ]);
        }

        /** @var EloquentCollection<int, QuestionExplanationSyncEntry> $previewEntries */
        $previewEntries = $previewRun->questionExplanationSyncEntries()
            ->where('status', QuestionExplanationSyncEntry::STATUS_PLANNED)
            ->orderBy('external_id')
            ->orderBy('question_id')
            ->get();

        if ($previewEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'run' => 'Wybrany preview nie zawiera rekordow gotowych do synchronizacji.',
            ]);
        }

        $now = now();
        $run = DB::transaction(function () use ($previewRun, $previewEntries, $checksum, $now, $expectedRuleOrigin): ContentImportRun {
            $run = ContentImportRun::query()->create([
                'kind' => self::APPLY_KIND,
                'identifier' => 'preview:'.$previewRun->getKey(),
                'status' => 'running',
                'dry_run' => false,
                'rows_total' => $previewEntries->pluck('external_id')->unique()->count(),
                'questions_total' => $previewEntries->count(),
                'errors_count' => 0,
                'warnings_count' => 0,
                'summary' => [
                    'preview_run_id' => $previewRun->getKey(),
                    'manifest_checksum' => $checksum,
                    'rule_origin' => $expectedRuleOrigin,
                ],
                'started_at' => $now,
            ]);

            $this->copyEntries($previewEntries, $run, $now);

            return $run;
        });

        $results = [
            'groups_applied' => 0,
            'question_rows_applied' => 0,
            'groups_skipped' => 0,
            'question_rows_skipped' => 0,
            'skip_reasons' => [],
        ];

        /** @var EloquentCollection<int, QuestionExplanationSyncEntry> $entries */
        $entries = $run->questionExplanationSyncEntries()
            ->where('status', QuestionExplanationSyncEntry::STATUS_PLANNED)
            ->orderBy('external_id')
            ->orderBy('question_id')
            ->get();

        foreach ($entries->groupBy('external_id') as $groupEntries) {
            $groupResult = $this->applyGroup($groupEntries);
            $results[$groupResult['applied'] ? 'groups_applied' : 'groups_skipped']++;
            $results[$groupResult['applied'] ? 'question_rows_applied' : 'question_rows_skipped'] += $groupEntries->count();

            if (! $groupResult['applied']) {
                $reason = $groupResult['reason'];
                $results['skip_reasons'][$reason] = ($results['skip_reasons'][$reason] ?? 0) + 1;
            }
        }

        $status = $results['groups_skipped'] > 0 ? 'completed_with_skips' : 'completed';
        $run->forceFill([
            'status' => $status,
            'warnings_count' => $results['groups_skipped'],
            'summary' => [
                ...($run->summary ?? []),
                ...$results,
            ],
            'completed_at' => now(),
        ])->save();

        return [
            'run' => $run->fresh(),
            'report' => [
                'mode' => 'write',
                'run_id' => $run->getKey(),
                'preview_run_id' => $previewRun->getKey(),
                'manifest_checksum' => $checksum,
                ...$results,
            ],
        ];
    }

    /**
     * @return array{report:array<string, mixed>, run:ContentImportRun|null}
     */
    public function rollback(int $applyRunId, string $confirmation = '', bool $write = false): array
    {
        $applyRun = ContentImportRun::query()->findOrFail($applyRunId);

        if ($applyRun->kind !== self::APPLY_KIND) {
            throw ValidationException::withMessages([
                'run' => 'Rollback wymaga przebiegu synchronizacji, a nie preview.',
            ]);
        }

        $checksum = $this->manifestChecksumForRun($applyRun);

        if ($write && ! hash_equals($checksum, trim($confirmation))) {
            throw ValidationException::withMessages([
                'confirm' => 'Checksum rollbacku nie jest zgodny z przebiegiem synchronizacji.',
            ]);
        }

        /** @var EloquentCollection<int, QuestionExplanationSyncEntry> $entries */
        $entries = $applyRun->questionExplanationSyncEntries()
            ->where('status', QuestionExplanationSyncEntry::STATUS_APPLIED)
            ->orderBy('external_id')
            ->orderBy('question_id')
            ->get();

        $report = [
            'mode' => $write ? 'write' : 'preview',
            'apply_run_id' => $applyRun->getKey(),
            'manifest_checksum' => $checksum,
            'groups_to_restore' => 0,
            'question_rows_to_restore' => 0,
            'groups_with_conflicts' => 0,
            'question_rows_with_conflicts' => 0,
            'conflict_reasons' => [],
        ];

        if (! $write) {
            foreach ($entries->groupBy('external_id') as $groupEntries) {
                $inspection = $this->inspectRollbackGroup($groupEntries);
                $report[$inspection['restorable'] ? 'groups_to_restore' : 'groups_with_conflicts']++;
                $report[$inspection['restorable'] ? 'question_rows_to_restore' : 'question_rows_with_conflicts'] += $groupEntries->count();

                if (! $inspection['restorable']) {
                    $reason = $inspection['reason'];
                    $report['conflict_reasons'][$reason] = ($report['conflict_reasons'][$reason] ?? 0) + 1;
                }
            }

            return ['run' => null, 'report' => $report];
        }

        $rollbackRun = ContentImportRun::query()->create([
            'kind' => self::ROLLBACK_KIND,
            'identifier' => 'sync:'.$applyRun->getKey(),
            'status' => 'running',
            'dry_run' => false,
            'rows_total' => $entries->pluck('external_id')->unique()->count(),
            'questions_total' => $entries->count(),
            'errors_count' => 0,
            'warnings_count' => 0,
            'summary' => [
                'apply_run_id' => $applyRun->getKey(),
                'manifest_checksum' => $checksum,
            ],
            'started_at' => now(),
        ]);

        foreach ($entries->groupBy('external_id') as $groupEntries) {
            $groupResult = $this->rollbackGroup($groupEntries);
            $report[$groupResult['restorable'] ? 'groups_to_restore' : 'groups_with_conflicts']++;
            $report[$groupResult['restorable'] ? 'question_rows_to_restore' : 'question_rows_with_conflicts'] += $groupEntries->count();

            if (! $groupResult['restorable']) {
                $reason = $groupResult['reason'];
                $report['conflict_reasons'][$reason] = ($report['conflict_reasons'][$reason] ?? 0) + 1;
            }
        }

        $rollbackRun->forceFill([
            'status' => $report['groups_with_conflicts'] > 0 ? 'completed_with_conflicts' : 'completed',
            'warnings_count' => $report['groups_with_conflicts'],
            'summary' => [
                ...($rollbackRun->summary ?? []),
                ...$report,
            ],
            'completed_at' => now(),
        ])->save();

        return ['run' => $rollbackRun->fresh(), 'report' => $report];
    }

    protected function applyGroup(Collection $entries): array
    {
        try {
            return DB::transaction(function () use ($entries): array {
                $reason = $this->applyGroupIssue($entries);

                if ($reason !== null) {
                    $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_SKIPPED_STALE, $reason);

                    return ['applied' => false, 'reason' => $reason];
                }

                $questionIds = $entries->pluck('question_id')->map(fn (mixed $id): int => (int) $id)->all();
                $newExplanation = (string) $entries->first()->new_explanation;

                Question::query()
                    ->whereIn('id', $questionIds)
                    ->update([
                        'explanation' => $newExplanation,
                        'updated_at' => now(),
                    ]);

                $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_APPLIED, null, 'applied_at');

                return ['applied' => true, 'reason' => null];
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_SKIPPED_STALE, 'apply_failed');

            return ['applied' => false, 'reason' => 'apply_failed'];
        }
    }

    protected function applyGroupIssue(Collection $entries): ?string
    {
        if ($entries->pluck('question_public_explanation_id')->unique()->count() !== 1
            || $entries->pluck('source_public_explanation_hash')->unique()->count() !== 1
            || $entries->pluck('new_explanation')->unique()->count() !== 1) {
            return 'manifest_group_inconsistent';
        }

        $source = QuestionPublicExplanation::query()
            ->lockForUpdate()
            ->find($entries->first()->question_public_explanation_id);

        if (! $source instanceof QuestionPublicExplanation
            || $source->status !== QuestionPublicExplanation::STATUS_PUBLISHED
            || $source->published_at === null
            || ! hash_equals((string) $entries->first()->source_public_explanation_hash, $this->planner->publicExplanationHash($source))) {
            return 'public_source_changed';
        }

        $externalId = (string) $entries->first()->external_id;
        $questions = Question::query()
            ->where('external_id', $externalId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $entryIds = $entries->pluck('question_id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();

        if ($questions->keys()->map(fn (mixed $id): int => (int) $id)->sort()->values()->all() !== $entryIds) {
            return 'local_group_changed';
        }

        foreach ($entries as $entry) {
            /** @var Question|null $question */
            $question = $questions->get($entry->question_id);

            if (! $question instanceof Question
                || ! hash_equals($entry->question_integrity_hash, $this->planner->questionIntegrityHash($question))
                || ! hash_equals($entry->previous_explanation_hash, $this->planner->explanationHash($question->explanation))) {
                return 'local_question_changed';
            }
        }

        return null;
    }

    protected function inspectRollbackGroup(Collection $entries): array
    {
        $questionIds = $entries->pluck('question_id')->map(fn (mixed $id): int => (int) $id)->all();
        $questions = Question::query()->whereIn('id', $questionIds)->get()->keyBy('id');

        if ($questions->count() !== count($questionIds)) {
            return ['restorable' => false, 'reason' => 'local_question_missing'];
        }

        foreach ($entries as $entry) {
            /** @var Question|null $question */
            $question = $questions->get($entry->question_id);

            if (! $question instanceof Question
                || ! hash_equals($entry->new_explanation_hash, $this->planner->explanationHash($question->explanation))) {
                return ['restorable' => false, 'reason' => 'explanation_changed_after_sync'];
            }
        }

        return ['restorable' => true, 'reason' => null];
    }

    protected function rollbackGroup(Collection $entries): array
    {
        try {
            return DB::transaction(function () use ($entries): array {
                $questionIds = $entries->pluck('question_id')->map(fn (mixed $id): int => (int) $id)->all();
                $questions = Question::query()
                    ->whereIn('id', $questionIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($questions->count() !== count($questionIds)) {
                    $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_ROLLBACK_CONFLICT, 'local_question_missing');

                    return ['restorable' => false, 'reason' => 'local_question_missing'];
                }

                if ($entries->pluck('previous_explanation')->unique()->count() !== 1) {
                    $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_ROLLBACK_CONFLICT, 'rollback_payload_inconsistent');

                    return ['restorable' => false, 'reason' => 'rollback_payload_inconsistent'];
                }

                foreach ($entries as $entry) {
                    /** @var Question|null $question */
                    $question = $questions->get($entry->question_id);

                    if (! $question instanceof Question
                        || ! hash_equals($entry->new_explanation_hash, $this->planner->explanationHash($question->explanation))) {
                        $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_ROLLBACK_CONFLICT, 'explanation_changed_after_sync');

                        return ['restorable' => false, 'reason' => 'explanation_changed_after_sync'];
                    }
                }

                Question::query()
                    ->whereIn('id', $questionIds)
                    ->update([
                        'explanation' => $entries->first()->previous_explanation,
                        'updated_at' => now(),
                    ]);

                $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_ROLLED_BACK, null, 'rolled_back_at');

                return ['restorable' => true, 'reason' => null];
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->markEntries($entries, QuestionExplanationSyncEntry::STATUS_ROLLBACK_CONFLICT, 'rollback_failed');

            return ['restorable' => false, 'reason' => 'rollback_failed'];
        }
    }

    protected function previewRun(
        int $previewRunId,
        string $expectedRuleOrigin = self::RULE_ORIGIN_EXTRACTED,
    ): ContentImportRun {
        $run = ContentImportRun::query()->findOrFail($previewRunId);

        if ($run->kind !== self::PREVIEW_KIND || ! $run->dry_run || $run->status !== 'preview_ready') {
            throw ValidationException::withMessages([
                'run' => 'Wybrany przebieg nie jest gotowym preview synchronizacji.',
            ]);
        }

        if (data_get($run->summary, 'rule_origin', self::RULE_ORIGIN_EXTRACTED) !== $expectedRuleOrigin) {
            throw ValidationException::withMessages([
                'run' => 'Wybrany preview pochodzi z innego trybu synchronizacji.',
            ]);
        }

        return $run;
    }

    protected function manifestChecksumForRun(ContentImportRun $run): string
    {
        $checksum = trim((string) data_get($run->summary, 'manifest_checksum', $run->identifier));

        if ($checksum === '') {
            throw ValidationException::withMessages([
                'run' => 'Przebieg nie zawiera checksumy manifestu.',
            ]);
        }

        return $checksum;
    }

    /**
     * @param  EloquentCollection<int, QuestionExplanationSyncEntry>  $entries
     */
    protected function copyEntries(EloquentCollection $entries, ContentImportRun $run, mixed $now): void
    {
        $entries
            ->chunk(500)
            ->each(function (Collection $chunk) use ($run, $now): void {
                QuestionExplanationSyncEntry::query()->insert(
                    $chunk
                        ->map(fn (QuestionExplanationSyncEntry $entry): array => [
                            'content_import_run_id' => $run->getKey(),
                            'question_id' => $entry->question_id,
                            'question_public_explanation_id' => $entry->question_public_explanation_id,
                            'external_id' => $entry->external_id,
                            'license_category_code' => $entry->license_category_code,
                            'source_public_explanation_hash' => $entry->source_public_explanation_hash,
                            'question_integrity_hash' => $entry->question_integrity_hash,
                            'previous_explanation_hash' => $entry->previous_explanation_hash,
                            'new_explanation_hash' => $entry->new_explanation_hash,
                            'previous_explanation' => $entry->previous_explanation,
                            'new_explanation' => $entry->new_explanation,
                            'status' => QuestionExplanationSyncEntry::STATUS_PLANNED,
                            'reason' => null,
                            'applied_at' => null,
                            'rolled_back_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all(),
                );
            });
    }

    /**
     * @param  array{entries:list<array<string, mixed>>, skipped_groups:list<array<string, mixed>>, summary:array<string, int>, manifest_checksum:string}  $plan
     * @return array<string, mixed>
     */
    protected function previewSummary(array $plan, string $ruleOrigin): array
    {
        return [
            ...$plan['summary'],
            'manifest_checksum' => $plan['manifest_checksum'],
            'rule_origin' => $ruleOrigin,
            'skipped_reason_counts' => collect($plan['skipped_groups'])
                ->countBy('reason')
                ->all(),
            'skipped_group_samples' => array_slice($plan['skipped_groups'], 0, 20),
        ];
    }

    protected function markEntries(Collection $entries, string $status, ?string $reason, ?string $timestampColumn = null): void
    {
        $attributes = [
            'status' => $status,
            'reason' => $reason,
            'updated_at' => now(),
        ];

        if ($timestampColumn !== null) {
            $attributes[$timestampColumn] = now();
        }

        QuestionExplanationSyncEntry::query()
            ->whereIn('id', $entries->pluck('id')->all())
            ->update($attributes);
    }
}
