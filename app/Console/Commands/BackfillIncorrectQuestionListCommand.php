<?php

namespace App\Console\Commands;

use App\Models\UserIncorrectQuestion;
use App\Support\QuestionProgressManager;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackfillIncorrectQuestionListCommand extends Command
{
    protected $signature = 'questions:backfill-incorrect-list
        {--dry-run : Show the planned insert without writing data}
        {--apply : Insert missing legacy candidates}
        {--batch= : UUID identifying this backfill batch}
        {--chunk=500 : Number of rows processed per batch}';

    protected $description = 'Preview or idempotently seed the managed incorrect-question list from the legacy progress buckets';

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Use either --dry-run or --apply, not both.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $batch = (string) ($this->option('batch') ?: Str::uuid());
        $chunkSize = max((int) $this->option('chunk'), 1);

        if (! Str::isUuid($batch)) {
            $this->error('--batch must be a valid UUID.');

            return self::INVALID;
        }

        $candidateCount = (clone $this->candidateQuery())->count();
        $missingCount = (clone $this->missingCandidateQuery())->count();
        $categoryReport = $this->dimensionReport('questions.license_category_id');
        $userReport = $this->dimensionReport('progress.user_id');
        $insertedCount = 0;

        if ($apply) {
            $this->candidateQuery()
                ->select([
                    'progress.id as progress_id',
                    'progress.user_id',
                    'progress.question_id',
                ])
                ->chunkById($chunkSize, function ($rows) use ($batch, &$insertedCount): void {
                    $now = now();
                    $payload = $rows
                        ->map(fn (object $row): array => [
                            'user_id' => (int) $row->user_id,
                            'question_id' => (int) $row->question_id,
                            'first_incorrect_at' => null,
                            'last_incorrect_at' => null,
                            'removed_at' => null,
                            'removal_reason' => null,
                            'latest_study_session_id' => null,
                            'latest_answer_id' => null,
                            'created_source' => UserIncorrectQuestion::SOURCE_LEGACY_BACKFILL,
                            'backfill_batch_id' => $batch,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all();

                    if ($payload !== []) {
                        $insertedCount += DB::table('user_incorrect_questions')->insertOrIgnore($payload);
                    }
                }, 'progress.id', 'progress_id');
        }

        $report = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'batch_id' => $batch,
            'legacy_candidates' => $candidateCount,
            'missing_before_run' => $missingCount,
            'inserted' => $insertedCount,
            'already_present_or_skipped' => max($candidateCount - $missingCount, 0),
            'by_category' => $categoryReport,
            'by_user' => $userReport,
            'generated_at' => now()->toIso8601String(),
        ];

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    protected function candidateQuery(): Builder
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

    protected function missingCandidateQuery(): Builder
    {
        return $this->candidateQuery()
            ->whereNotExists(function (Builder $query): void {
                $query
                    ->selectRaw('1')
                    ->from('user_incorrect_questions as existing_incorrect_questions')
                    ->whereColumn('existing_incorrect_questions.user_id', 'progress.user_id')
                    ->whereColumn('existing_incorrect_questions.question_id', 'progress.question_id');
            });
    }

    /**
     * @return array<int, array{key:int,legacy_candidates:int,missing_before_run:int}>
     */
    protected function dimensionReport(string $column): array
    {
        $candidateCounts = $this->candidateQuery()
            ->selectRaw("{$column} as dimension_key, count(*) as aggregate")
            ->groupBy($column)
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->dimension_key);
        $missingCounts = $this->missingCandidateQuery()
            ->selectRaw("{$column} as dimension_key, count(*) as aggregate")
            ->groupBy($column)
            ->get()
            ->keyBy(fn (object $row): int => (int) $row->dimension_key);

        return $candidateCounts
            ->map(fn (object $row, int|string $key): array => [
                'key' => (int) $key,
                'legacy_candidates' => (int) $row->aggregate,
                'missing_before_run' => (int) ($missingCounts->get((int) $key)?->aggregate ?? 0),
            ])
            ->values()
            ->all();
    }
}
