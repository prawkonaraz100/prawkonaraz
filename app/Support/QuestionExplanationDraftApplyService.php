<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionExplanationDraft;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionExplanationDraftApplyService
{
    /**
     * @param  array<int, string>  $externalIds
     * @return array<string, mixed>
     */
    public function apply(
        string $source = 'pj360',
        bool $write = false,
        bool $overwriteExisting = false,
        ?int $limit = null,
        array $externalIds = [],
    ): array {
        $drafts = $this->draftQuery($source, $externalIds, $limit)->get();

        $summary = [
            'source' => $source,
            'write' => $write,
            'overwrite_existing' => $overwriteExisting,
            'candidate_entries' => $drafts->count(),
            'entries_fully_applicable' => 0,
            'entries_partially_applicable' => 0,
            'entries_skipped_existing_only' => 0,
            'entries_without_targets' => 0,
            'question_updates_planned' => 0,
            'question_updates_performed' => 0,
            'questions_skipped_existing' => 0,
            'exception_count' => 0,
            'exception_external_ids' => [],
            'entry_reports' => [],
            'exception_entries' => [],
        ];

        DB::transaction(function () use ($drafts, $write, $overwriteExisting, &$summary): void {
            $regularReports = [];
            $exceptionReports = [];

            foreach ($drafts as $draft) {
                $report = $this->applyDraft($draft, $write, $overwriteExisting);
                $summary['question_updates_planned'] += $report['updated_count'];
                $summary['questions_skipped_existing'] += $report['skipped_existing_count'];

                if ($write) {
                    $summary['question_updates_performed'] += $report['updated_count'];
                }

                match ($report['result_status']) {
                    QuestionExplanationDraft::STATUS_APPLIED => $summary['entries_fully_applicable']++,
                    QuestionExplanationDraft::STATUS_APPLIED_WITH_SKIPS => $summary['entries_partially_applicable']++,
                    QuestionExplanationDraft::STATUS_SKIPPED_EXISTING => $summary['entries_skipped_existing_only']++,
                    default => $summary['entries_without_targets']++,
                };

                if ($this->isExceptionReport($report)) {
                    $exceptionReports[] = $report;
                } else {
                    $regularReports[] = $report;
                }
            }

            $summary['exception_count'] = count($exceptionReports);
            $summary['exception_external_ids'] = array_values(array_map(
                static fn (array $report): string => (string) $report['external_id'],
                $exceptionReports,
            ));
            $summary['entry_reports'] = array_values([...$regularReports, ...$exceptionReports]);
            $summary['exception_entries'] = array_values($exceptionReports);
        });

        return $summary;
    }

    /**
     * @param  array<int, string>  $externalIds
     */
    protected function draftQuery(string $source, array $externalIds, ?int $limit)
    {
        $query = QuestionExplanationDraft::query()
            ->where('source', $source)
            ->whereIn('status', [
                QuestionExplanationDraft::STATUS_STAGED,
                QuestionExplanationDraft::STATUS_APPLIED_WITH_SKIPS,
                QuestionExplanationDraft::STATUS_SKIPPED_EXISTING,
            ])
            ->orderBy('id');

        $normalizedExternalIds = collect($externalIds)
            ->map(fn (string $externalId): string => trim($externalId))
            ->filter()
            ->values();

        if ($normalizedExternalIds->isNotEmpty()) {
            $query->whereIn('external_id', $normalizedExternalIds->all());
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function isExceptionReport(array $report): bool
    {
        return ($report['result_status'] ?? null) !== QuestionExplanationDraft::STATUS_APPLIED;
    }

    /**
     * @return array<string, mixed>
     */
    protected function applyDraft(QuestionExplanationDraft $draft, bool $write, bool $overwriteExisting): array
    {
        $targetQuestions = Question::query()
            ->whereKey($draft->local_question_ids ?? [])
            ->get(['id', 'explanation']);

        $updatedQuestionIds = [];
        $skippedExistingQuestionIds = [];

        foreach ($targetQuestions as $question) {
            if (! $overwriteExisting && filled($question->explanation)) {
                $skippedExistingQuestionIds[] = $question->id;

                continue;
            }

            $updatedQuestionIds[] = $question->id;

            if ($write) {
                $question->forceFill([
                    'explanation' => $draft->draft_text,
                ])->save();
            }
        }

        $resultStatus = $this->resolveResultStatus(
            $targetQuestions,
            $updatedQuestionIds,
            $skippedExistingQuestionIds,
        );

        $report = [
            'external_id' => $draft->external_id,
            'source_queue' => $draft->source_queue,
            'result_status' => $resultStatus,
            'target_question_ids' => $targetQuestions->pluck('id')->values()->all(),
            'updated_question_ids' => $updatedQuestionIds,
            'skipped_existing_question_ids' => $skippedExistingQuestionIds,
            'updated_count' => count($updatedQuestionIds),
            'skipped_existing_count' => count($skippedExistingQuestionIds),
        ];

        if ($write) {
            $draft->forceFill([
                'status' => $resultStatus,
                'applied_question_count' => count($updatedQuestionIds),
                'skipped_existing_question_count' => count($skippedExistingQuestionIds),
                'last_applied_at' => now(),
                'last_apply_report' => $report,
            ])->save();
        }

        return $report;
    }

    /**
     * @param  Collection<int, Question>  $targetQuestions
     * @param  array<int, int>  $updatedQuestionIds
     * @param  array<int, int>  $skippedExistingQuestionIds
     */
    protected function resolveResultStatus(Collection $targetQuestions, array $updatedQuestionIds, array $skippedExistingQuestionIds): string
    {
        if ($targetQuestions->isEmpty()) {
            return QuestionExplanationDraft::STATUS_STAGING_CONFLICT;
        }

        if ($updatedQuestionIds !== [] && $skippedExistingQuestionIds === []) {
            return QuestionExplanationDraft::STATUS_APPLIED;
        }

        if ($updatedQuestionIds !== [] && $skippedExistingQuestionIds !== []) {
            return QuestionExplanationDraft::STATUS_APPLIED_WITH_SKIPS;
        }

        return QuestionExplanationDraft::STATUS_SKIPPED_EXISTING;
    }
}
