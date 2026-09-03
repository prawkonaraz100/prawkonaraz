<?php

namespace App\Support;

use App\Models\QuestionDailyStat;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionDailyStatsAggregator
{
    /**
     * @return array{date:string, rows:int}
     */
    public function aggregateDay(CarbonInterface $date): array
    {
        $statsDate = $date->copy()->startOfDay();
        $windowStart = $statsDate->copy()->startOfDay();
        $windowEnd = $statsDate->copy()->endOfDay();

        $answerRows = DB::table('study_session_answers as answers')
            ->join('study_sessions as sessions', 'sessions.id', '=', 'answers.study_session_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where(function ($query) use ($windowStart, $windowEnd): void {
                $query
                    ->whereBetween('answers.answered_at', [$windowStart, $windowEnd])
                    ->orWhere(function ($innerQuery) use ($windowStart, $windowEnd): void {
                        $innerQuery
                            ->whereNull('answers.answered_at')
                            ->whereBetween('answers.created_at', [$windowStart, $windowEnd]);
                    });
            })
            ->get([
                'answers.question_id',
                'answers.is_correct',
                'answers.response_time_ms',
                'questions.license_category_id',
                'questions.question_topic_id',
                'sessions.user_id',
            ]);

        $progressRows = DB::table('user_question_progress as progress')
            ->join('questions', 'questions.id', '=', 'progress.question_id')
            ->whereBetween('progress.last_answered_at', [$windowStart, $windowEnd])
            ->get([
                'progress.question_id',
                'progress.total_attempts',
                'progress.repetitions',
                'progress.correct_streak',
                'progress.last_quality',
                'progress.next_review_at',
                'questions.license_category_id',
                'questions.question_topic_id',
            ]);

        $payload = $this->buildRows(
            $statsDate->toDateString(),
            $answerRows,
            $progressRows,
        );

        QuestionDailyStat::query()
            ->whereDate('stats_date', $statsDate)
            ->delete();

        if ($payload !== []) {
            QuestionDailyStat::query()->upsert(
                $payload,
                ['stats_date', 'question_id'],
                [
                    'license_category_id',
                    'question_topic_id',
                    'answers_count',
                    'users_count',
                    'correct_answers_count',
                    'incorrect_answers_count',
                    'response_time_count',
                    'response_time_avg_ms',
                    'response_time_median_ms',
                    'progress_users_count',
                    'mastered_progress_users_count',
                    'avg_total_attempts',
                    'updated_at',
                ],
            );
        }

        return [
            'date' => $statsDate->toDateString(),
            'rows' => count($payload),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(string $statsDate, Collection $answerRows, Collection $progressRows): array
    {
        $groupedAnswers = $answerRows->groupBy('question_id');
        $groupedProgress = $progressRows->groupBy('question_id');
        $questionIds = $groupedAnswers->keys()
            ->merge($groupedProgress->keys())
            ->unique()
            ->values();

        $rows = [];
        $now = now();

        foreach ($questionIds as $questionId) {
            $questionAnswerRows = $groupedAnswers->get($questionId, collect());
            $questionProgressRows = $groupedProgress->get($questionId, collect());
            $referenceRow = $questionAnswerRows->first() ?? $questionProgressRows->first();

            $responseTimes = $questionAnswerRows
                ->pluck('response_time_ms')
                ->filter(fn (mixed $value): bool => filled($value))
                ->map(fn (mixed $value): int => (int) $value)
                ->values();

            $masteredProgressCount = $questionProgressRows->filter(
                fn (object $progress): bool => QuestionProgressManager::isMasteredSnapshot(
                    (int) $progress->total_attempts,
                    (int) $progress->repetitions,
                    (int) $progress->correct_streak,
                    $progress->last_quality !== null ? (int) $progress->last_quality : null,
                    $progress->next_review_at,
                ),
            )->count();

            $rows[] = [
                'stats_date' => $statsDate,
                'question_id' => (int) $questionId,
                'license_category_id' => $referenceRow?->license_category_id ? (int) $referenceRow->license_category_id : null,
                'question_topic_id' => $referenceRow?->question_topic_id ? (int) $referenceRow->question_topic_id : null,
                'answers_count' => $questionAnswerRows->count(),
                'users_count' => $questionAnswerRows
                    ->pluck('user_id')
                    ->unique()
                    ->count(),
                'correct_answers_count' => $questionAnswerRows->where('is_correct', true)->count(),
                'incorrect_answers_count' => $questionAnswerRows->where('is_correct', false)->count(),
                'response_time_count' => $responseTimes->count(),
                'response_time_avg_ms' => $responseTimes->isNotEmpty()
                    ? (int) round((float) $responseTimes->avg())
                    : null,
                'response_time_median_ms' => $responseTimes->isNotEmpty()
                    ? (int) round((float) $responseTimes->median())
                    : null,
                'progress_users_count' => $questionProgressRows->count(),
                'mastered_progress_users_count' => $masteredProgressCount,
                'avg_total_attempts' => $questionProgressRows->isNotEmpty()
                    ? round((float) $questionProgressRows->avg('total_attempts'), 2)
                    : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }
}
