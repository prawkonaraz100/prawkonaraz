<?php

namespace App\Console\Commands;

use App\Support\QuestionProgressManager;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditIncorrectQuestionListCommand extends Command
{
    protected $signature = 'questions:audit-incorrect-list {--batch= : Optional backfill batch UUID}';

    protected $description = 'Compare the managed incorrect-question list with the current legacy incorrect bucket';

    public function handle(): int
    {
        $batch = $this->option('batch');

        if ($batch && ! Str::isUuid((string) $batch)) {
            $this->error('--batch must be a valid UUID.');

            return self::INVALID;
        }

        $legacyCandidates = $this->legacyCandidateQuery();
        $activeList = DB::table('user_incorrect_questions as list')
            ->join('questions', 'questions.id', '=', 'list.question_id')
            ->whereNull('list.removed_at')
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue');

        $report = [
            'batch_id' => $batch ?: null,
            'legacy_candidates' => (clone $legacyCandidates)->count(),
            'active_list_entries' => (clone $activeList)->count(),
            'active_entries_from_batch' => $batch
                ? (clone $activeList)->where('list.backfill_batch_id', $batch)->count()
                : null,
            'legacy_missing_from_active_list' => (clone $legacyCandidates)
                ->whereNotExists(function (Builder $query): void {
                    $query
                        ->selectRaw('1')
                        ->from('user_incorrect_questions as list_match')
                        ->whereColumn('list_match.user_id', 'progress.user_id')
                        ->whereColumn('list_match.question_id', 'progress.question_id')
                        ->whereNull('list_match.removed_at');
                })
                ->count(),
            'generated_at' => now()->toIso8601String(),
        ];

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    protected function legacyCandidateQuery(): Builder
    {
        $masteredSql = sprintf(
            '(coalesce(progress.repetitions, 0) >= %d and coalesce(progress.correct_streak, 0) >= %d and coalesce(progress.last_quality, 0) >= %d and progress.next_review_at is not null and progress.next_review_at > ?)',
            QuestionProgressManager::MASTERED_MIN_REPETITIONS,
            QuestionProgressManager::MASTERED_MIN_CORRECT_STREAK,
            QuestionProgressManager::MASTERED_MIN_LAST_QUALITY,
        );

        return DB::table('user_question_progress as progress')
            ->join('questions', 'questions.id', '=', 'progress.question_id')
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->where('progress.total_attempts', '>', 0)
            ->where('progress.incorrect_count', '>', 0)
            ->whereRaw("not {$masteredSql}", [today()->toDateString()]);
    }
}
