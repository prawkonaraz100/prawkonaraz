<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionExplanationPtOverlapBackfillService
{
    /**
     * @param  array<int, string>  $externalIds
     * @return array<string, mixed>
     */
    public function apply(bool $write = false, ?int $limit = null, array $externalIds = []): array
    {
        $ptMissingQuestions = Question::query()
            ->with('licenseCategory')
            ->whereHas('licenseCategory', fn ($query) => $query->where('code', 'PT'))
            ->where(function ($query): void {
                $query->whereNull('explanation')
                    ->orWhere('explanation', '');
            })
            ->when($externalIds !== [], fn ($query) => $query->whereIn('external_id', $externalIds))
            ->orderBy('external_id')
            ->orderBy('id')
            ->get();

        $grouped = $ptMissingQuestions
            ->groupBy(fn (Question $question) => (string) $question->external_id)
            ->values();

        if ($limit !== null) {
            $grouped = $grouped->take($limit);
        }

        $candidateEntries = [];
        $updatedQuestionIds = [];
        $skippedInconsistentDonor = [];
        $skippedPromptMismatch = [];
        $skippedAnswerMismatch = [];

        foreach ($grouped as $questionsForExternalId) {
            /** @var Collection<int, Question> $questionsForExternalId */
            $externalId = (string) $questionsForExternalId->first()?->external_id;

            $donorQuestions = Question::query()
                ->with('licenseCategory')
                ->where('external_id', $externalId)
                ->whereHas('licenseCategory', fn ($query) => $query->where('code', '!=', 'PT'))
                ->where(function ($query): void {
                    $query->whereNotNull('explanation')
                        ->where('explanation', '!=', '');
                })
                ->orderBy('id')
                ->get();

            if ($donorQuestions->isEmpty()) {
                continue;
            }

            $donorExplanations = $donorQuestions
                ->pluck('explanation')
                ->filter(fn (?string $text) => filled($text))
                ->map(fn (string $text) => trim($text))
                ->unique()
                ->values();

            if ($donorExplanations->count() !== 1) {
                $skippedInconsistentDonor[] = $externalId;

                continue;
            }

            $ptPrompt = trim((string) $questionsForExternalId->first()->prompt);
            $donorPrompt = trim((string) $donorQuestions->first()->prompt);

            if ($ptPrompt !== $donorPrompt) {
                $skippedPromptMismatch[] = $externalId;

                continue;
            }

            $ptCorrectAnswer = (string) $questionsForExternalId->first()->correct_answer;
            $donorCorrectAnswers = $donorQuestions
                ->pluck('correct_answer')
                ->filter()
                ->unique()
                ->values();

            if ($donorCorrectAnswers->count() !== 1 || (string) $donorCorrectAnswers->first() !== $ptCorrectAnswer) {
                $skippedAnswerMismatch[] = $externalId;

                continue;
            }

            $donorExplanation = (string) $donorExplanations->first();

            $entry = [
                'external_id' => $externalId,
                'question_ids' => $questionsForExternalId->pluck('id')->all(),
                'prompt' => $ptPrompt,
                'correct_answer' => $ptCorrectAnswer,
                'donor_question_ids' => $donorQuestions->pluck('id')->all(),
                'donor_category_codes' => $donorQuestions
                    ->map(fn (Question $question) => $question->licenseCategory?->code)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'copied_explanation' => $donorExplanation,
            ];

            $candidateEntries[] = $entry;

            if (! $write) {
                continue;
            }

            DB::transaction(function () use ($donorExplanation, $entry, &$updatedQuestionIds): void {
                Question::query()
                    ->whereIn('id', $entry['question_ids'])
                    ->update(['explanation' => $donorExplanation]);

                $updatedQuestionIds = [...$updatedQuestionIds, ...$entry['question_ids']];
            });
        }

        return [
            'write' => $write,
            'candidate_external_ids' => count($candidateEntries),
            'candidate_question_rows' => array_sum(array_map(
                fn (array $entry): int => count($entry['question_ids']),
                $candidateEntries,
            )),
            'updated_question_rows' => count($updatedQuestionIds),
            'updated_question_ids' => $updatedQuestionIds,
            'skipped_inconsistent_donor_count' => count($skippedInconsistentDonor),
            'skipped_inconsistent_donor_external_ids' => $skippedInconsistentDonor,
            'skipped_prompt_mismatch_count' => count($skippedPromptMismatch),
            'skipped_prompt_mismatch_external_ids' => $skippedPromptMismatch,
            'skipped_answer_mismatch_count' => count($skippedAnswerMismatch),
            'skipped_answer_mismatch_external_ids' => $skippedAnswerMismatch,
            'entries' => $candidateEntries,
        ];
    }
}
