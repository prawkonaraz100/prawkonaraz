<?php

namespace App\Support;

use App\Models\QuestionDailyStat;
use App\Models\QuestionMonthlyStat;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionMonthlyStatsRollupService
{
    /**
     * @return array{months:int, rows:int, deleted_daily_rows:int, last_month:?string}
     */
    public function rollupBefore(CarbonInterface $cutoffDate): array
    {
        $cutoff = $cutoffDate->copy()->startOfDay();
        $months = $this->eligibleMonths($cutoff);
        $totalRows = 0;
        $totalDeletedDailyRows = 0;
        $lastMonth = null;

        foreach ($months as $month) {
            $result = DB::transaction(function () use ($cutoff, $month): array {
                $monthStart = Carbon::parse($month)->startOfMonth();
                $monthEnd = $monthStart->copy()->endOfMonth();
                $rows = QuestionDailyStat::query()
                    ->whereBetween('stats_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->whereDate('stats_date', '<', $cutoff->toDateString())
                    ->orderBy('question_id')
                    ->get();

                if ($rows->isEmpty()) {
                    return [
                        'rows' => 0,
                        'deleted_daily_rows' => 0,
                    ];
                }

                $payload = $this->buildPayload($monthStart, $rows);

                QuestionMonthlyStat::query()->upsert(
                    $payload,
                    ['stats_month', 'question_id'],
                    [
                        'license_category_id',
                        'question_topic_id',
                        'days_covered_count',
                        'answers_count',
                        'user_days_count',
                        'correct_answers_count',
                        'incorrect_answers_count',
                        'response_time_count',
                        'response_time_avg_ms',
                        'progress_user_days_count',
                        'mastered_progress_user_days_count',
                        'avg_total_attempts',
                        'updated_at',
                    ],
                );

                $deletedDailyRows = QuestionDailyStat::query()
                    ->whereKey($rows->pluck('id')->all())
                    ->delete();

                return [
                    'rows' => count($payload),
                    'deleted_daily_rows' => $deletedDailyRows,
                ];
            });

            $totalRows += $result['rows'];
            $totalDeletedDailyRows += $result['deleted_daily_rows'];
            $lastMonth = $month;
        }

        return [
            'months' => $months->count(),
            'rows' => $totalRows,
            'deleted_daily_rows' => $totalDeletedDailyRows,
            'last_month' => $lastMonth,
        ];
    }

    /**
     * @return Collection<int, string>
     */
    protected function eligibleMonths(CarbonInterface $cutoff): Collection
    {
        $expression = match ((string) config('database.default')) {
            'mysql', 'mariadb' => "DATE_FORMAT(stats_date, '%Y-%m-01')",
            'pgsql' => "TO_CHAR(stats_date, 'YYYY-MM-01')",
            default => "strftime('%Y-%m-01', stats_date)",
        };

        return QuestionDailyStat::query()
            ->whereDate('stats_date', '<', $cutoff->toDateString())
            ->selectRaw("{$expression} as stats_month")
            ->groupBy('stats_month')
            ->orderBy('stats_month')
            ->pluck('stats_month')
            ->map(fn (mixed $value): string => (string) $value)
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildPayload(CarbonInterface $monthStart, Collection $rows): array
    {
        $now = now();
        $grouped = $rows->groupBy('question_id');
        $existing = QuestionMonthlyStat::query()
            ->whereDate('stats_month', $monthStart->toDateString())
            ->whereIn('question_id', $grouped->keys()->all())
            ->get()
            ->keyBy('question_id');

        $payload = [];

        foreach ($grouped as $questionId => $questionRows) {
            $newStats = $this->aggregateQuestionRows($questionRows);
            $current = $existing->get($questionId);

            if ($current !== null) {
                $newStats = $this->mergeMonthlyStats($current, $newStats);
            }

            $payload[] = [
                'stats_month' => $monthStart->toDateString(),
                'question_id' => (int) $questionId,
                'license_category_id' => $newStats['license_category_id'],
                'question_topic_id' => $newStats['question_topic_id'],
                'days_covered_count' => $newStats['days_covered_count'],
                'answers_count' => $newStats['answers_count'],
                'user_days_count' => $newStats['user_days_count'],
                'correct_answers_count' => $newStats['correct_answers_count'],
                'incorrect_answers_count' => $newStats['incorrect_answers_count'],
                'response_time_count' => $newStats['response_time_count'],
                'response_time_avg_ms' => $newStats['response_time_avg_ms'],
                'progress_user_days_count' => $newStats['progress_user_days_count'],
                'mastered_progress_user_days_count' => $newStats['mastered_progress_user_days_count'],
                'avg_total_attempts' => $newStats['avg_total_attempts'],
                'created_at' => $current?->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, int|float|null>
     */
    protected function aggregateQuestionRows(Collection $rows): array
    {
        $firstRow = $rows->first();
        $daysCoveredCount = $rows->pluck('stats_date')->unique()->count();
        $answersCount = (int) $rows->sum('answers_count');
        $userDaysCount = (int) $rows->sum('users_count');
        $correctAnswersCount = (int) $rows->sum('correct_answers_count');
        $incorrectAnswersCount = (int) $rows->sum('incorrect_answers_count');
        $responseTimeCount = (int) $rows->sum('response_time_count');
        $progressUserDaysCount = (int) $rows->sum('progress_users_count');
        $masteredProgressUserDaysCount = (int) $rows->sum('mastered_progress_users_count');

        $responseTimeWeightedSum = $rows->reduce(
            fn (float $carry, QuestionDailyStat $row): float => $carry + ((float) ($row->response_time_avg_ms ?? 0) * (int) $row->response_time_count),
            0.0,
        );

        $attemptsWeightedSum = $rows->reduce(
            fn (float $carry, QuestionDailyStat $row): float => $carry + ((float) $row->avg_total_attempts * (int) $row->progress_users_count),
            0.0,
        );

        return [
            'license_category_id' => $firstRow?->license_category_id ? (int) $firstRow->license_category_id : null,
            'question_topic_id' => $firstRow?->question_topic_id ? (int) $firstRow->question_topic_id : null,
            'days_covered_count' => $daysCoveredCount,
            'answers_count' => $answersCount,
            'user_days_count' => $userDaysCount,
            'correct_answers_count' => $correctAnswersCount,
            'incorrect_answers_count' => $incorrectAnswersCount,
            'response_time_count' => $responseTimeCount,
            'response_time_avg_ms' => $responseTimeCount > 0
                ? (int) round($responseTimeWeightedSum / $responseTimeCount)
                : null,
            'progress_user_days_count' => $progressUserDaysCount,
            'mastered_progress_user_days_count' => $masteredProgressUserDaysCount,
            'avg_total_attempts' => $progressUserDaysCount > 0
                ? round($attemptsWeightedSum / $progressUserDaysCount, 2)
                : 0.0,
        ];
    }

    /**
     * @param  array<string, int|float|null>  $newStats
     * @return array<string, int|float|null>
     */
    protected function mergeMonthlyStats(QuestionMonthlyStat $current, array $newStats): array
    {
        $responseTimeCount = (int) $current->response_time_count + (int) $newStats['response_time_count'];
        $responseTimeWeightedSum = ((float) ($current->response_time_avg_ms ?? 0) * (int) $current->response_time_count)
            + ((float) ($newStats['response_time_avg_ms'] ?? 0) * (int) $newStats['response_time_count']);
        $progressUserDaysCount = (int) $current->progress_user_days_count + (int) $newStats['progress_user_days_count'];
        $attemptsWeightedSum = ((float) $current->avg_total_attempts * (int) $current->progress_user_days_count)
            + ((float) $newStats['avg_total_attempts'] * (int) $newStats['progress_user_days_count']);

        return [
            'license_category_id' => $current->license_category_id ?? $newStats['license_category_id'],
            'question_topic_id' => $current->question_topic_id ?? $newStats['question_topic_id'],
            'days_covered_count' => (int) $current->days_covered_count + (int) $newStats['days_covered_count'],
            'answers_count' => (int) $current->answers_count + (int) $newStats['answers_count'],
            'user_days_count' => (int) $current->user_days_count + (int) $newStats['user_days_count'],
            'correct_answers_count' => (int) $current->correct_answers_count + (int) $newStats['correct_answers_count'],
            'incorrect_answers_count' => (int) $current->incorrect_answers_count + (int) $newStats['incorrect_answers_count'],
            'response_time_count' => $responseTimeCount,
            'response_time_avg_ms' => $responseTimeCount > 0
                ? (int) round($responseTimeWeightedSum / $responseTimeCount)
                : null,
            'progress_user_days_count' => $progressUserDaysCount,
            'mastered_progress_user_days_count' => (int) $current->mastered_progress_user_days_count + (int) $newStats['mastered_progress_user_days_count'],
            'avg_total_attempts' => $progressUserDaysCount > 0
                ? round($attemptsWeightedSum / $progressUserDaysCount, 2)
                : 0.0,
        ];
    }
}
