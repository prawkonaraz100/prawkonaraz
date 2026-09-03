<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionModule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionCollectionPreflightService
{
    /**
     * Build a read-only integrity report for a collection before its learner rollout.
     *
     * @return array<string, mixed>
     */
    public function audit(string $identifier, int $sampleLimit = 20): array
    {
        $collection = QuestionCollection::query()
            ->with('licenseCategory')
            ->where(function ($query) use ($identifier): void {
                $query
                    ->where('code', $identifier)
                    ->orWhere('slug', $identifier);
            })
            ->first();

        if ($collection === null) {
            throw (new ModelNotFoundException)->setModel(QuestionCollection::class, [$identifier]);
        }

        $sampleLimit = max(1, min($sampleLimit, 100));
        $modules = QuestionModule::query()
            ->where('question_collection_id', $collection->getKey())
            ->withCount('questions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $moduleIds = $modules->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $moduleById = $modules->keyBy('id');

        $assignmentQuery = DB::table('question_module_question as pivot')
            ->join('question_modules as module', 'module.id', '=', 'pivot.question_module_id')
            ->where('module.question_collection_id', $collection->getKey());
        $assignmentTotal = (clone $assignmentQuery)->count();
        $questionIds = (clone $assignmentQuery)
            ->distinct()
            ->pluck('pivot.question_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->orderBy('id')
            ->get();
        $questionsById = $questions->keyBy('id');

        $mediaTotals = $questionIds === []
            ? collect()
            : DB::table('question_media')
                ->whereIn('question_id', $questionIds)
                ->selectRaw('question_id, COUNT(*) as media_total')
                ->groupBy('question_id')
                ->pluck('media_total', 'question_id');
        $mediaByKind = $questionIds === []
            ? []
            : DB::table('question_media')
                ->whereIn('question_id', $questionIds)
                ->selectRaw('kind, COUNT(*) as total')
                ->groupBy('kind')
                ->orderBy('kind')
                ->pluck('total', 'kind')
                ->map(static fn (mixed $total): int => (int) $total)
                ->all();

        $repeatedQuestionRows = $moduleIds === []
            ? collect()
            : DB::table('question_module_question as pivot')
                ->selectRaw('pivot.question_id, COUNT(DISTINCT pivot.question_module_id) as module_total')
                ->whereIn('pivot.question_module_id', $moduleIds)
                ->groupBy('pivot.question_id')
                ->havingRaw('COUNT(DISTINCT pivot.question_module_id) > 1')
                ->orderBy('pivot.question_id')
                ->get();
        $repeatedQuestionIds = $repeatedQuestionRows
            ->pluck('question_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        $modulesByRepeatedQuestion = $this->modulesByQuestion($repeatedQuestionIds, $moduleIds, $moduleById);

        $moduleFindings = $this->moduleFindings($modules, $sampleLimit);
        $questionFindings = $this->questionFindings(
            questions: $questions,
            mediaTotals: $mediaTotals,
            sampleLimit: $sampleLimit,
            expectedCategoryId: (int) $collection->license_category_id,
        );
        $repeatedSamples = $repeatedQuestionRows
            ->take($sampleLimit)
            ->map(function (object $row) use ($questionsById, $modulesByRepeatedQuestion): array {
                $questionId = (int) $row->question_id;
                $question = $questionsById->get($questionId);

                return [
                    ...$this->questionSample($question),
                    'module_total' => (int) $row->module_total,
                    'modules' => $modulesByRepeatedQuestion[$questionId] ?? [],
                ];
            })
            ->values()
            ->all();

        $errors = array_values(array_filter([
            $this->finding('missing_modules', $modules->isEmpty(), $modules->isEmpty() ? 1 : 0),
            $this->finding('missing_questions', $questions->isEmpty(), $questions->isEmpty() ? 1 : 0),
            $this->finding('questions_from_other_category', $questionFindings['category_mismatch_total'] > 0, $questionFindings['category_mismatch_total'], $questionFindings['category_mismatch_samples']),
            $this->finding('missing_external_ids', $questionFindings['missing_external_id_total'] > 0, $questionFindings['missing_external_id_total'], $questionFindings['missing_external_id_samples']),
            $this->finding('duplicate_external_ids', $questionFindings['duplicate_external_id_total'] > 0, $questionFindings['duplicate_external_id_total'], $questionFindings['duplicate_external_id_samples']),
            $this->finding('missing_prompts', $questionFindings['missing_prompt_total'] > 0, $questionFindings['missing_prompt_total'], $questionFindings['missing_prompt_samples']),
            $this->finding('missing_required_answers', $questionFindings['missing_required_answer_total'] > 0, $questionFindings['missing_required_answer_total'], $questionFindings['missing_required_answer_samples']),
            $this->finding('invalid_correct_answers', $questionFindings['invalid_correct_answer_total'] > 0, $questionFindings['invalid_correct_answer_total'], $questionFindings['invalid_correct_answer_samples']),
            $this->finding(
                'active_questions_in_nonpublic_collection',
                ! $collection->is_public && $questionFindings['active_total'] > 0,
                $questionFindings['active_total'],
                $questionFindings['active_samples'],
            ),
        ]));

        $warnings = array_values(array_filter([
            $this->finding('empty_modules', $moduleFindings['empty_total'] > 0, $moduleFindings['empty_total'], $moduleFindings['empty_samples']),
            $this->finding('module_expected_question_count_mismatches', $moduleFindings['expected_count_mismatch_total'] > 0, $moduleFindings['expected_count_mismatch_total'], $moduleFindings['expected_count_mismatch_samples']),
            $this->finding('module_position_gaps', $moduleFindings['position_issue_total'] > 0, $moduleFindings['position_issue_total'], $moduleFindings['position_issue_samples']),
            $this->finding('questions_repeated_between_modules', $repeatedQuestionRows->isNotEmpty(), $repeatedQuestionRows->count(), $repeatedSamples),
            $this->finding('questions_with_delivery_issue', $questionFindings['delivery_issue_total'] > 0, $questionFindings['delivery_issue_total'], $questionFindings['delivery_issue_samples']),
            $this->finding('missing_required_primary_media', $questionFindings['missing_required_primary_media_total'] > 0, $questionFindings['missing_required_primary_media_total'], $questionFindings['missing_required_primary_media_samples']),
        ]));

        return [
            'generated_at' => now()->utc()->toIso8601String(),
            'read_only' => true,
            'collection' => [
                'id' => (int) $collection->getKey(),
                'code' => $collection->code,
                'slug' => $collection->slug,
                'name' => $collection->name,
                'kind' => $collection->kind,
                'source' => $collection->source,
                'is_active' => (bool) $collection->is_active,
                'is_public' => (bool) $collection->is_public,
                'category' => [
                    'id' => (int) $collection->licenseCategory->getKey(),
                    'code' => $collection->licenseCategory->code,
                    'name' => $collection->licenseCategory->name,
                    'is_active' => (bool) $collection->licenseCategory->is_active,
                ],
            ],
            'totals' => [
                'modules' => $modules->count(),
                'active_modules' => $modules->where('is_active', true)->count(),
                'inactive_modules' => $modules->where('is_active', false)->count(),
                'module_assignments' => $assignmentTotal,
                'unique_questions' => $questions->count(),
                'duplicate_assignments_between_modules' => max(0, $assignmentTotal - $questions->count()),
                'media' => array_sum($mediaByKind),
                'media_by_kind' => $mediaByKind,
            ],
            'questions' => [
                'total' => $questions->count(),
                'active' => $questionFindings['active_total'],
                'inactive' => $questions->count() - $questionFindings['active_total'],
                'with_media' => $questionFindings['with_media_total'],
                'without_media' => $questions->count() - $questionFindings['with_media_total'],
                'requires_primary_media' => $questionFindings['requires_primary_media_total'],
                'missing_required_primary_media' => $questionFindings['missing_required_primary_media_total'],
                'delivery_issues' => $questionFindings['delivery_issues'],
            ],
            'modules' => $moduleFindings['rows'],
            'findings' => [
                'errors' => $errors,
                'warnings' => $warnings,
            ],
            'summary' => [
                'error_total' => count($errors),
                'warning_total' => count($warnings),
                'is_ready_for_next_stage' => $errors === [],
            ],
        ];
    }

    /**
     * @param  array<int, int>  $questionIds
     * @param  array<int, int>  $moduleIds
     * @param  Collection<int, QuestionModule>  $moduleById
     * @return array<int, array<int, array{code:string,name:string}>>
     */
    protected function modulesByQuestion(array $questionIds, array $moduleIds, Collection $moduleById): array
    {
        if ($questionIds === [] || $moduleIds === []) {
            return [];
        }

        $rows = DB::table('question_module_question')
            ->whereIn('question_id', $questionIds)
            ->whereIn('question_module_id', $moduleIds)
            ->orderBy('question_id')
            ->orderBy('question_module_id')
            ->get(['question_id', 'question_module_id']);
        $result = [];

        foreach ($rows as $row) {
            $questionId = (int) $row->question_id;
            $module = $moduleById->get((int) $row->question_module_id);

            if ($module === null) {
                continue;
            }

            $result[$questionId][] = [
                'code' => (string) $module->code,
                'name' => (string) $module->name,
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, QuestionModule>  $modules
     * @return array{rows:array<int, array<string, mixed>>, empty_total:int, empty_samples:array<int, array<string, mixed>>, expected_count_mismatch_total:int, expected_count_mismatch_samples:array<int, array<string, mixed>>, position_issue_total:int, position_issue_samples:array<int, array<string, mixed>>}
     */
    protected function moduleFindings(Collection $modules, int $sampleLimit): array
    {
        $rows = [];
        $emptySamples = [];
        $expectedCountMismatchSamples = [];
        $positionIssueSamples = [];

        foreach ($modules as $module) {
            $positionStats = DB::table('question_module_question')
                ->where('question_module_id', $module->getKey())
                ->selectRaw('COUNT(*) as assignment_total, MIN(position) as first_position, MAX(position) as last_position, COUNT(DISTINCT position) as unique_position_total')
                ->first();
            $assignmentTotal = (int) ($positionStats->assignment_total ?? 0);
            $firstPosition = $positionStats->first_position === null ? null : (int) $positionStats->first_position;
            $lastPosition = $positionStats->last_position === null ? null : (int) $positionStats->last_position;
            $uniquePositionTotal = (int) ($positionStats->unique_position_total ?? 0);
            $hasPositionIssue = $assignmentTotal > 0
                && ($firstPosition !== 1 || $lastPosition !== $assignmentTotal || $uniquePositionTotal !== $assignmentTotal);
            $hasExpectedCountMismatch = $module->expected_questions !== null
                && (int) $module->expected_questions !== $assignmentTotal;
            $row = [
                'id' => (int) $module->getKey(),
                'code' => (string) $module->code,
                'name' => (string) $module->name,
                'is_active' => (bool) $module->is_active,
                'expected_questions' => $module->expected_questions === null ? null : (int) $module->expected_questions,
                'assigned_questions' => $assignmentTotal,
                'first_position' => $firstPosition,
                'last_position' => $lastPosition,
                'unique_position_total' => $uniquePositionTotal,
                'has_expected_count_mismatch' => $hasExpectedCountMismatch,
                'has_position_issue' => $hasPositionIssue,
            ];
            $rows[] = $row;

            if ($assignmentTotal === 0 && count($emptySamples) < $sampleLimit) {
                $emptySamples[] = $row;
            }

            if ($hasExpectedCountMismatch && count($expectedCountMismatchSamples) < $sampleLimit) {
                $expectedCountMismatchSamples[] = $row;
            }

            if ($hasPositionIssue && count($positionIssueSamples) < $sampleLimit) {
                $positionIssueSamples[] = $row;
            }
        }

        return [
            'rows' => $rows,
            'empty_total' => count(array_filter($rows, static fn (array $row): bool => $row['assigned_questions'] === 0)),
            'empty_samples' => $emptySamples,
            'expected_count_mismatch_total' => count(array_filter($rows, static fn (array $row): bool => $row['has_expected_count_mismatch'])),
            'expected_count_mismatch_samples' => $expectedCountMismatchSamples,
            'position_issue_total' => count(array_filter($rows, static fn (array $row): bool => $row['has_position_issue'])),
            'position_issue_samples' => $positionIssueSamples,
        ];
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  Collection<int, int>  $mediaTotals
     * @return array<string, mixed>
     */
    protected function questionFindings(
        Collection $questions,
        Collection $mediaTotals,
        int $sampleLimit,
        int $expectedCategoryId,
    ): array {
        $missingExternalIdSamples = [];
        $missingPromptSamples = [];
        $missingRequiredAnswerSamples = [];
        $invalidCorrectAnswerSamples = [];
        $activeSamples = [];
        $categoryMismatchSamples = [];
        $deliveryIssueSamples = [];
        $missingRequiredPrimaryMediaSamples = [];
        $externalIds = [];
        $activeTotal = 0;
        $withMediaTotal = 0;
        $requiresPrimaryMediaTotal = 0;
        $missingRequiredPrimaryMediaTotal = 0;
        $missingExternalIdTotal = 0;
        $missingPromptTotal = 0;
        $missingRequiredAnswerTotal = 0;
        $invalidCorrectAnswerTotal = 0;
        $categoryMismatchTotal = 0;
        $deliveryIssueTotal = 0;
        $deliveryIssues = [];

        foreach ($questions as $question) {
            $sample = $this->questionSample($question);
            $externalId = trim((string) ($question->external_id ?? ''));
            $mediaTotal = (int) ($mediaTotals->get($question->getKey()) ?? 0);
            $hasMissingOption = trim((string) $question->option_a) === '' || trim((string) $question->option_b) === '';
            $correctAnswer = strtolower(trim((string) $question->correct_answer));
            $correctOption = match ($correctAnswer) {
                'a' => (string) $question->option_a,
                'b' => (string) $question->option_b,
                'c' => (string) ($question->option_c ?? ''),
                default => null,
            };

            if ($externalId === '') {
                $missingExternalIdTotal++;
                $this->appendSample($missingExternalIdSamples, $sample, $sampleLimit);
            } else {
                $externalIds[$externalId][] = $sample;
            }

            if (trim((string) $question->prompt) === '') {
                $missingPromptTotal++;
                $this->appendSample($missingPromptSamples, $sample, $sampleLimit);
            }

            if ($hasMissingOption) {
                $missingRequiredAnswerTotal++;
                $this->appendSample($missingRequiredAnswerSamples, $sample, $sampleLimit);
            }

            if (! in_array($correctAnswer, ['a', 'b', 'c'], true) || trim((string) $correctOption) === '') {
                $invalidCorrectAnswerTotal++;
                $this->appendSample($invalidCorrectAnswerSamples, [
                    ...$sample,
                    'correct_answer' => $correctAnswer,
                ], $sampleLimit);
            }

            if ((bool) $question->is_active) {
                $activeTotal++;
                $this->appendSample($activeSamples, $sample, $sampleLimit);
            }

            if ((int) $question->license_category_id !== $expectedCategoryId) {
                $categoryMismatchTotal++;
                $this->appendSample($categoryMismatchSamples, [
                    ...$sample,
                    'license_category_id' => (int) $question->license_category_id,
                ], $sampleLimit);
            }

            if ($mediaTotal > 0) {
                $withMediaTotal++;
            }

            if ((bool) $question->requires_primary_media) {
                $requiresPrimaryMediaTotal++;

                if ($mediaTotal === 0) {
                    $missingRequiredPrimaryMediaTotal++;
                    $this->appendSample($missingRequiredPrimaryMediaSamples, $sample, $sampleLimit);
                }
            }

            if (filled($question->delivery_issue)) {
                $deliveryIssueTotal++;
                $issue = (string) $question->delivery_issue;
                $deliveryIssues[$issue] = ($deliveryIssues[$issue] ?? 0) + 1;
                $this->appendSample($deliveryIssueSamples, [
                    ...$sample,
                    'delivery_issue' => $issue,
                ], $sampleLimit);
            }
        }

        $duplicateExternalIdSamples = [];
        $duplicateExternalIdTotal = 0;

        foreach ($externalIds as $externalId => $samples) {
            if (count($samples) < 2) {
                continue;
            }

            $duplicateExternalIdTotal++;
            $this->appendSample($duplicateExternalIdSamples, [
                'external_id' => $externalId,
                'questions' => array_slice($samples, 0, $sampleLimit),
            ], $sampleLimit);
        }

        ksort($deliveryIssues);

        return [
            'active_total' => $activeTotal,
            'active_samples' => $activeSamples,
            'with_media_total' => $withMediaTotal,
            'requires_primary_media_total' => $requiresPrimaryMediaTotal,
            'missing_required_primary_media_total' => $missingRequiredPrimaryMediaTotal,
            'missing_required_primary_media_samples' => $missingRequiredPrimaryMediaSamples,
            'missing_external_id_total' => $missingExternalIdTotal,
            'missing_external_id_samples' => $missingExternalIdSamples,
            'duplicate_external_id_total' => $duplicateExternalIdTotal,
            'duplicate_external_id_samples' => $duplicateExternalIdSamples,
            'missing_prompt_total' => $missingPromptTotal,
            'missing_prompt_samples' => $missingPromptSamples,
            'missing_required_answer_total' => $missingRequiredAnswerTotal,
            'missing_required_answer_samples' => $missingRequiredAnswerSamples,
            'invalid_correct_answer_total' => $invalidCorrectAnswerTotal,
            'invalid_correct_answer_samples' => $invalidCorrectAnswerSamples,
            'category_mismatch_total' => $categoryMismatchTotal,
            'category_mismatch_samples' => $categoryMismatchSamples,
            'delivery_issue_total' => $deliveryIssueTotal,
            'delivery_issue_samples' => $deliveryIssueSamples,
            'delivery_issues' => $deliveryIssues,
        ];
    }

    /**
     * @return array{id:int|null, external_id:string|null, prompt:string}
     */
    protected function questionSample(?Question $question): array
    {
        if ($question === null) {
            return [
                'id' => null,
                'external_id' => null,
                'prompt' => '',
            ];
        }

        return [
            'id' => (int) $question->getKey(),
            'external_id' => filled($question->external_id) ? (string) $question->external_id : null,
            'prompt' => str($question->prompt)->squish()->limit(160)->toString(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $samples
     * @param  array<string, mixed>  $sample
     */
    protected function appendSample(array &$samples, array $sample, int $sampleLimit): void
    {
        if (count($samples) < $sampleLimit) {
            $samples[] = $sample;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $samples
     * @return array{key:string,total:int,samples:array<int, array<string, mixed>>}|null
     */
    protected function finding(string $key, bool $hasFinding, int $total, array $samples = []): ?array
    {
        if (! $hasFinding) {
            return null;
        }

        return [
            'key' => $key,
            'total' => $total,
            'samples' => $samples,
        ];
    }
}
