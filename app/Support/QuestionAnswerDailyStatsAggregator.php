<?php

namespace App\Support;

use App\Models\QuestionAnswerDailyStat;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionAnswerDailyStatsAggregator
{
    /**
     * @return array{date:string, rows:int}
     */
    public function aggregateDay(CarbonInterface $date): array
    {
        $statsDate = $date->copy()->startOfDay();
        $windowStart = $statsDate->copy()->startOfDay();
        $windowEnd = $statsDate->copy()->addDay()->startOfDay();
        $rows = $this->answerRows($windowStart, $windowEnd);
        $payload = $this->buildRows($statsDate->toDateString(), $rows);

        DB::transaction(function () use ($payload, $statsDate): void {
            QuestionAnswerDailyStat::query()
                ->whereDate('stats_date', $statsDate)
                ->delete();

            if ($payload === []) {
                return;
            }

            QuestionAnswerDailyStat::query()->upsert(
                $payload,
                ['stats_date', 'question_id', 'selected_answer'],
                [
                    'license_category_id',
                    'question_topic_id',
                    'answers_count',
                    'users_count',
                    'updated_at',
                ],
            );
        });

        return [
            'date' => $statsDate->toDateString(),
            'rows' => count($payload),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    protected function answerRows(CarbonInterface $windowStart, CarbonInterface $windowEnd): Collection
    {
        $answeredRows = $this->baseAnswerRowsQuery()
            ->where('answers.answered_at', '>=', $windowStart)
            ->where('answers.answered_at', '<', $windowEnd);

        $createdFallbackRows = $this->baseAnswerRowsQuery()
            ->whereNull('answers.answered_at')
            ->where('answers.created_at', '>=', $windowStart)
            ->where('answers.created_at', '<', $windowEnd);

        return DB::query()
            ->fromSub($answeredRows->unionAll($createdFallbackRows), 'answer_rows')
            ->select([
                'question_id',
                'selected_answer',
                'license_category_id',
                'question_topic_id',
            ])
            ->selectRaw('COUNT(*) as answers_count')
            ->selectRaw('COUNT(DISTINCT user_id) as users_count')
            ->groupBy([
                'question_id',
                'selected_answer',
                'license_category_id',
                'question_topic_id',
            ])
            ->get();
    }

    protected function baseAnswerRowsQuery(): Builder
    {
        return DB::table('study_session_answers as answers')
            ->join('study_sessions as sessions', 'sessions.id', '=', 'answers.study_session_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('answers.answer_kind', StudySessionAnswerKind::CHOICE)
            ->whereNotNull('answers.selected_answer')
            ->whereRaw("LOWER(answers.selected_answer) in ('a', 'b', 'c')")
            ->select([
                'answers.question_id',
                'questions.license_category_id',
                'questions.question_topic_id',
                'sessions.user_id',
            ])
            ->selectRaw('LOWER(answers.selected_answer) as selected_answer');
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function buildRows(string $statsDate, Collection $rows): array
    {
        $now = now();

        return $rows
            ->map(fn (object $row): array => [
                'stats_date' => $statsDate,
                'question_id' => (int) $row->question_id,
                'license_category_id' => $row->license_category_id ? (int) $row->license_category_id : null,
                'question_topic_id' => $row->question_topic_id ? (int) $row->question_topic_id : null,
                'selected_answer' => (string) $row->selected_answer,
                'answers_count' => (int) $row->answers_count,
                'users_count' => (int) $row->users_count,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();
    }
}
