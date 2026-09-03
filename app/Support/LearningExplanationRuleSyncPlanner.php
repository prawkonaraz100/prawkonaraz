<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionExplanationSyncEntry;
use App\Models\QuestionPublicExplanation;
use Illuminate\Support\Collection;

class LearningExplanationRuleSyncPlanner
{
    public function __construct(
        protected PublicExplanationMemoryRuleExtractor $extractor,
    ) {}

    /**
     * @param  list<string>  $requestedExternalIds
     * @param  array<string, string>  $curatedRules
     * @return array{entries:list<array<string, mixed>>, skipped_groups:list<array<string, mixed>>, summary:array<string, int>, manifest_checksum:string}
     */
    public function plan(array $requestedExternalIds = [], array $curatedRules = []): array
    {
        $requestedExternalIds = collect($requestedExternalIds)
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $publicExplanations = QuestionPublicExplanation::query()
            ->published()
            ->when(
                $requestedExternalIds !== [],
                fn ($query) => $query->whereIn('external_id', $requestedExternalIds),
            )
            ->orderBy('id')
            ->get(['id', 'external_id', 'body', 'status', 'published_at']);

        $sourceGroups = $publicExplanations
            ->filter(fn (QuestionPublicExplanation $explanation): bool => trim((string) $explanation->external_id) !== '')
            ->groupBy(fn (QuestionPublicExplanation $explanation): string => (string) $explanation->external_id);

        $skippedGroups = [];
        $sources = collect();

        foreach ($sourceGroups as $externalId => $group) {
            if ($group->count() !== 1) {
                $skippedGroups[] = $this->skip($externalId, 'multiple_published_sources');

                continue;
            }

            /** @var QuestionPublicExplanation $source */
            $source = $group->first();
            $rule = $curatedRules[$externalId] ?? $this->extractor->extract($source->body);

            if ($rule === null) {
                $skippedGroups[] = $this->skip($externalId, 'no_safe_memory_rule');

                continue;
            }

            $sources->put($externalId, [
                'model' => $source,
                'rule' => $rule,
                'hash' => $this->publicExplanationHash($source),
            ]);
        }

        $questionsByExternalId = $sources->isEmpty()
            ? collect()
            : Question::query()
                ->with('licenseCategory:id,code')
                ->whereIn('external_id', $sources->keys()->all())
                ->orderBy('external_id')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (Question $question): string => (string) $question->external_id);

        $manuallyEditedQuestionIds = $this->manuallyEditedQuestionIds();
        $entries = [];

        foreach ($sources as $externalId => $source) {
            /** @var Collection<int, Question> $questions */
            $questions = $questionsByExternalId->get($externalId, collect());
            $reason = $this->groupIssue($questions, $manuallyEditedQuestionIds);

            if ($reason !== null) {
                $skippedGroups[] = $this->skip(
                    $externalId,
                    $reason,
                    $questions->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all(),
                );

                continue;
            }

            /** @var QuestionPublicExplanation $publicExplanation */
            $publicExplanation = $source['model'];

            foreach ($questions as $question) {
                $entries[] = [
                    'question_id' => (int) $question->getKey(),
                    'question_public_explanation_id' => (int) $publicExplanation->getKey(),
                    'external_id' => $externalId,
                    'license_category_code' => $question->licenseCategory?->code,
                    'source_public_explanation_hash' => $source['hash'],
                    'question_integrity_hash' => $this->questionIntegrityHash($question),
                    'previous_explanation_hash' => $this->explanationHash($question->explanation),
                    'new_explanation_hash' => $this->explanationHash($source['rule']),
                    'previous_explanation' => $question->explanation,
                    'new_explanation' => $source['rule'],
                    'status' => QuestionExplanationSyncEntry::STATUS_PLANNED,
                    'reason' => null,
                ];
            }
        }

        usort($entries, fn (array $left, array $right): int => [
            $left['external_id'],
            $left['license_category_code'] ?? '',
            $left['question_id'],
        ] <=> [
            $right['external_id'],
            $right['license_category_code'] ?? '',
            $right['question_id'],
        ]);
        usort($skippedGroups, fn (array $left, array $right): int => [$left['external_id'], $left['reason']] <=> [$right['external_id'], $right['reason']]);

        $summary = [
            'published_sources_considered' => $publicExplanations->count(),
            'safe_rule_groups' => $sources->count(),
            'planned_groups' => collect($entries)->pluck('external_id')->unique()->count(),
            'planned_question_rows' => count($entries),
            'skipped_groups' => count($skippedGroups),
            'manual_edit_groups_skipped' => collect($skippedGroups)->where('reason', 'manual_explanation_edit')->count(),
            'existing_explanation_conflict_groups_skipped' => collect($skippedGroups)->where('reason', 'existing_explanation_conflict')->count(),
        ];

        return [
            'entries' => $entries,
            'skipped_groups' => $skippedGroups,
            'summary' => $summary,
            'manifest_checksum' => $this->manifestChecksum($entries),
        ];
    }

    public function publicExplanationHash(QuestionPublicExplanation $explanation): string
    {
        return $this->hash([
            'id' => (int) $explanation->getKey(),
            'external_id' => (string) $explanation->external_id,
            'status' => (string) $explanation->status,
            'published_at' => $explanation->published_at?->toIso8601String(),
            'body' => (string) $explanation->body,
        ]);
    }

    public function questionIntegrityHash(Question $question): string
    {
        return $this->hash([
            'id' => (int) $question->getKey(),
            'external_id' => (string) $question->external_id,
            'prompt' => (string) $question->prompt,
            'option_a' => (string) $question->option_a,
            'option_b' => (string) $question->option_b,
            'option_c' => $question->option_c === null ? null : (string) $question->option_c,
            'correct_answer' => (string) $question->correct_answer,
            'question_type' => (string) $question->question_type,
            'points' => (int) $question->points,
            'source' => $question->source === null ? null : (string) $question->source,
            'is_active' => (bool) $question->is_active,
        ]);
    }

    public function explanationHash(?string $explanation): string
    {
        return hash('sha256', $explanation === null ? '__NULL__' : $explanation);
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<int, true>  $manuallyEditedQuestionIds
     */
    protected function groupIssue(Collection $questions, array $manuallyEditedQuestionIds): ?string
    {
        if ($questions->isEmpty()) {
            return 'missing_local_questions';
        }

        if ($questions->contains(fn (Question $question): bool => ! $question->is_active)) {
            return 'inactive_local_question';
        }

        if ($questions->contains(fn (Question $question): bool => isset($manuallyEditedQuestionIds[(int) $question->getKey()]))) {
            return 'manual_explanation_edit';
        }

        if ($questions->map(fn (Question $question): string => $this->structuralSignature($question))->unique()->count() !== 1) {
            return 'question_structure_conflict';
        }

        if ($questions->map(fn (Question $question): string => $this->explanationHash($question->explanation))->unique()->count() !== 1) {
            return 'existing_explanation_conflict';
        }

        return null;
    }

    /**
     * @return array<int, true>
     */
    protected function manuallyEditedQuestionIds(): array
    {
        $ids = [];

        AuditLog::query()
            ->where('action', 'admin.question.explanation_updated')
            ->get(['metadata'])
            ->each(function (AuditLog $auditLog) use (&$ids): void {
                $metadata = is_array($auditLog->metadata) ? $auditLog->metadata : [];
                $affectedIds = array_merge(
                    is_array($metadata['affected_question_ids'] ?? null) ? $metadata['affected_question_ids'] : [],
                    [$metadata['question_id'] ?? null],
                );

                foreach ($affectedIds as $id) {
                    if (is_numeric($id) && (int) $id > 0) {
                        $ids[(int) $id] = true;
                    }
                }
            });

        return $ids;
    }

    protected function structuralSignature(Question $question): string
    {
        return $this->hash([
            'prompt' => (string) $question->prompt,
            'option_a' => (string) $question->option_a,
            'option_b' => (string) $question->option_b,
            'option_c' => $question->option_c === null ? null : (string) $question->option_c,
            'correct_answer' => (string) $question->correct_answer,
            'question_type' => (string) $question->question_type,
            'points' => (int) $question->points,
            'source' => $question->source === null ? null : (string) $question->source,
        ]);
    }

    /**
     * @param  array<int, int>  $questionIds
     * @return array{external_id:string, reason:string, question_ids:array<int, int>}
     */
    protected function skip(string $externalId, string $reason, array $questionIds = []): array
    {
        return [
            'external_id' => $externalId,
            'reason' => $reason,
            'question_ids' => $questionIds,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    protected function manifestChecksum(array $entries): string
    {
        return $this->hash(array_map(fn (array $entry): array => [
            'question_id' => $entry['question_id'],
            'question_public_explanation_id' => $entry['question_public_explanation_id'],
            'external_id' => $entry['external_id'],
            'source_public_explanation_hash' => $entry['source_public_explanation_hash'],
            'question_integrity_hash' => $entry['question_integrity_hash'],
            'previous_explanation_hash' => $entry['previous_explanation_hash'],
            'new_explanation_hash' => $entry['new_explanation_hash'],
        ], $entries));
    }

    /**
     * @param  array<string|int, mixed>|list<mixed>  $value
     */
    protected function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
