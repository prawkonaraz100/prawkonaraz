<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationDraft;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionExplanationDraftStagingService
{
    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, mixed>
     */
    public function stage(array $records, string $source = 'pj360', bool $reset = false): array
    {
        $candidates = collect($records)
            ->filter(fn (mixed $record): bool => is_array($record))
            ->values();

        $externalIds = $candidates
            ->map(fn (array $record): string => $this->normalizeExternalId($record['gov_id'] ?? null))
            ->filter(fn (string $externalId): bool => $externalId !== '')
            ->unique()
            ->values();

        /** @var Collection<string, EloquentCollection<int, Question>> $questionsByExternalId */
        $questionsByExternalId = Question::query()
            ->with('licenseCategory:id,code')
            ->whereIn('external_id', $externalIds->all())
            ->get(['id', 'external_id', 'license_category_id', 'prompt', 'correct_answer', 'explanation'])
            ->groupBy('external_id');

        $summary = [
            'source' => $source,
            'candidate_total' => $candidates->count(),
            'publish_ready_total' => 0,
            'staged_count' => 0,
            'conflict_count' => 0,
            'skipped_not_publish_ready_count' => 0,
            'entries_with_existing_explanations' => 0,
            'target_question_count' => 0,
            'targeted_questions_with_existing_explanations' => 0,
        ];

        DB::transaction(function () use ($candidates, $questionsByExternalId, $source, $reset, &$summary): void {
            if ($reset) {
                QuestionExplanationDraft::query()
                    ->where('source', $source)
                    ->delete();
            }

            foreach ($candidates as $record) {
                if (! (bool) ($record['publish_ready'] ?? false)) {
                    $summary['skipped_not_publish_ready_count']++;

                    continue;
                }

                $summary['publish_ready_total']++;

                $externalId = $this->normalizeExternalId($record['gov_id'] ?? null);

                if ($externalId === '') {
                    continue;
                }

                $payload = $this->buildDraftPayload(
                    $source,
                    $record,
                    $questionsByExternalId->get($externalId, new EloquentCollection())
                );

                QuestionExplanationDraft::query()->updateOrCreate(
                    [
                        'source' => $source,
                        'external_id' => $externalId,
                    ],
                    $payload,
                );

                $summary['target_question_count'] += (int) $payload['local_question_count'];
                $summary['targeted_questions_with_existing_explanations'] += (int) $payload['local_existing_explanation_count'];

                if ((int) $payload['local_existing_explanation_count'] > 0) {
                    $summary['entries_with_existing_explanations']++;
                }

                if ($payload['status'] === QuestionExplanationDraft::STATUS_STAGING_CONFLICT) {
                    $summary['conflict_count']++;
                } else {
                    $summary['staged_count']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(string $source = 'pj360'): array
    {
        $baseQuery = QuestionExplanationDraft::query()->where('source', $source);

        return [
            'source' => $source,
            'total_entries' => (clone $baseQuery)->count(),
            'status_counts' => (clone $baseQuery)
                ->select('status', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('status')
                ->pluck('aggregate', 'status')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
            'source_queue_counts' => (clone $baseQuery)
                ->select('source_queue', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('source_queue')
                ->pluck('aggregate', 'source_queue')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
            'staging_issue_counts' => (clone $baseQuery)
                ->whereNotNull('staging_issue')
                ->select('staging_issue', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('staging_issue')
                ->pluck('aggregate', 'staging_issue')
                ->map(fn (mixed $count): int => (int) $count)
                ->all(),
            'entries_with_existing_explanations' => (clone $baseQuery)
                ->where('local_existing_explanation_count', '>', 0)
                ->count(),
            'target_question_count' => (clone $baseQuery)->sum('local_question_count'),
            'targeted_questions_with_existing_explanations' => (clone $baseQuery)->sum('local_existing_explanation_count'),
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  EloquentCollection<int, Question>  $questions
     * @return array<string, mixed>
     */
    protected function buildDraftPayload(string $source, array $record, EloquentCollection $questions): array
    {
        $categories = $this->normalizeCategories($record['categories'] ?? []);
        $sourcePrompt = trim((string) ($record['prompt'] ?? ''));
        $acceptedAnswerLabel = Str::upper(trim((string) ($record['accepted_answer_label'] ?? '')));

        $matchingQuestions = $questions
            ->filter(function (Question $question) use ($categories): bool {
                $categoryCode = Str::upper((string) $question->licenseCategory?->code);

                return $categoryCode !== '' && in_array($categoryCode, $categories, true);
            })
            ->values();

        $localCategoryCodes = $matchingQuestions
            ->map(fn (Question $question): string => Str::upper((string) $question->licenseCategory?->code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $missingCategoryCodes = array_values(array_diff($categories, $localCategoryCodes));
        $localQuestionIds = $matchingQuestions->pluck('id')->values()->all();
        $localExistingExplanationCount = $matchingQuestions
            ->filter(fn (Question $question): bool => filled($question->explanation))
            ->count();

        $stagingFlags = [];

        if ($matchingQuestions->isEmpty()) {
            $stagingFlags[] = 'missing_local_questions';
        }

        if ($missingCategoryCodes !== []) {
            $stagingFlags[] = 'missing_local_categories';
        }

        if ($sourcePrompt !== '' && $matchingQuestions->contains(fn (Question $question): bool => $this->normalizeText($question->prompt) !== $this->normalizeText($sourcePrompt))) {
            $stagingFlags[] = 'local_prompt_mismatch';
        }

        if ($acceptedAnswerLabel !== '' && $matchingQuestions->contains(function (Question $question) use ($acceptedAnswerLabel): bool {
            return Str::upper((string) $question->correct_answer) !== $acceptedAnswerLabel;
        })) {
            $stagingFlags[] = 'local_answer_mismatch';
        }

        if ($localExistingExplanationCount > 0) {
            $stagingFlags[] = 'existing_explanations_present';
        }

        $stagingFlags = array_values(array_unique($stagingFlags));

        $stagingIssue = collect([
            'missing_local_questions',
            'missing_local_categories',
            'local_prompt_mismatch',
            'local_answer_mismatch',
        ])->first(fn (string $flag): bool => in_array($flag, $stagingFlags, true));

        return [
            'source' => $source,
            'external_id' => $this->normalizeExternalId($record['gov_id'] ?? null),
            'prompt' => $sourcePrompt,
            'draft_text' => trim((string) ($record['draft_text'] ?? '')),
            'source_summary' => $this->nullableString($record['source_summary'] ?? null),
            'categories' => $categories,
            'question_type' => $this->nullableString($record['question_type'] ?? null),
            'question_media_kind' => $this->nullableString($record['question_media_kind'] ?? null),
            'structure_scope' => $this->nullableString($record['structure_scope'] ?? null),
            'accepted_answer' => $this->nullableString($record['accepted_answer'] ?? null),
            'accepted_answer_label' => $acceptedAnswerLabel !== '' ? $acceptedAnswerLabel : null,
            'source_queue' => $this->nullableString($record['source_queue'] ?? null),
            'source_question_id' => $this->nullableString($record['external_site_question_id'] ?? null),
            'source_url' => $this->nullableString($record['external_url'] ?? null),
            'tier_b_decision' => $this->nullableString($record['tier_b_decision'] ?? null),
            'tier_b_note' => $this->nullableString($record['tier_b_note'] ?? null),
            'resolution_method' => $this->nullableString($record['resolution_method'] ?? null),
            'quality_flags' => $this->normalizeStringList($record['quality_flags'] ?? []),
            'staging_flags' => $stagingFlags,
            'local_category_codes' => $localCategoryCodes,
            'missing_category_codes' => $missingCategoryCodes,
            'local_question_ids' => $localQuestionIds,
            'local_question_count' => count($localQuestionIds),
            'local_existing_explanation_count' => $localExistingExplanationCount,
            'status' => $stagingIssue === null ? QuestionExplanationDraft::STATUS_STAGED : QuestionExplanationDraft::STATUS_STAGING_CONFLICT,
            'staging_issue' => $stagingIssue,
            'source_payload' => $record,
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function normalizeCategories(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn (mixed $category): string => Str::upper(trim((string) $category)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    protected function normalizeStringList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function normalizeExternalId(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function normalizeText(string $value): string
    {
        return Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->value();
    }

    protected function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
