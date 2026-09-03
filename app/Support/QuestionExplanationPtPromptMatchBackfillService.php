<?php

namespace App\Support;

use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionExplanationPtPromptMatchBackfillService
{
    /**
     * @param  array<int, string>  $externalIds
     * @return array<string, mixed>
     */
    public function apply(bool $write = false, ?int $limit = null, array $externalIds = []): array
    {
        $ptMissingQuestions = Question::query()
            ->with(['licenseCategory', 'media'])
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
        $skippedNoMatchingDonor = [];
        $skippedInconsistentDonor = [];

        foreach ($grouped as $questionsForExternalId) {
            /** @var Collection<int, Question> $questionsForExternalId */
            $ptQuestion = $questionsForExternalId->first();
            $externalId = (string) $ptQuestion?->external_id;

            if (! $ptQuestion) {
                continue;
            }

            $ptPrompt = trim((string) $ptQuestion->prompt);
            $ptQuestionType = (string) $ptQuestion->question_type;
            $ptCorrectAnswer = (string) $ptQuestion->correct_answer;
            $ptPrimaryMediaKind = $this->primaryMediaKind($ptQuestion);

            $donorQuestions = Question::query()
                ->with(['licenseCategory', 'media'])
                ->whereHas('licenseCategory', fn ($query) => $query->where('code', '!=', 'PT'))
                ->where('prompt', $ptPrompt)
                ->where('question_type', $ptQuestionType)
                ->where('correct_answer', $ptCorrectAnswer)
                ->where(function ($query): void {
                    $query->whereNotNull('explanation')
                        ->where('explanation', '!=', '');
                })
                ->orderBy('id')
                ->get()
                ->filter(fn (Question $question): bool => $this->primaryMediaKind($question) === $ptPrimaryMediaKind)
                ->values();

            if ($donorQuestions->isEmpty()) {
                $skippedNoMatchingDonor[] = $externalId;

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

            $donorExplanation = (string) $donorExplanations->first();

            $entry = [
                'external_id' => $externalId,
                'question_ids' => $questionsForExternalId->pluck('id')->all(),
                'prompt' => $ptPrompt,
                'question_type' => $ptQuestionType,
                'correct_answer' => $ptCorrectAnswer,
                'primary_media_kind' => $ptPrimaryMediaKind,
                'donor_question_ids' => $donorQuestions->pluck('id')->all(),
                'donor_external_ids' => $donorQuestions
                    ->pluck('external_id')
                    ->filter()
                    ->map(fn ($value): string => (string) $value)
                    ->unique()
                    ->values()
                    ->all(),
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
            'skipped_no_matching_donor_count' => count($skippedNoMatchingDonor),
            'skipped_no_matching_donor_external_ids' => $skippedNoMatchingDonor,
            'skipped_inconsistent_donor_count' => count($skippedInconsistentDonor),
            'skipped_inconsistent_donor_external_ids' => $skippedInconsistentDonor,
            'entries' => $candidateEntries,
        ];
    }

    private function primaryMediaKind(Question $question): string
    {
        $question->loadMissing('media');

        return (string) ($question->media->first()?->kind ?? 'none');
    }
}
