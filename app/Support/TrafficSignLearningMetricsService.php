<?php

namespace App\Support;

use App\Models\TrafficSignLearningAnswer;
use App\Models\TrafficSignLearningSession;
use Illuminate\Support\Carbon;

class TrafficSignLearningMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $days = 7): array
    {
        $since = $days !== null && $days > 0
            ? now()->subDays($days)
            : null;

        $startedSessions = $this->sessionQuery($since)->count();
        $completedSessions = $this->sessionQuery($since)
            ->where('status', TrafficSignLearningSession::STATUS_COMPLETED)
            ->count();
        $averageScore = $this->sessionQuery($since)
            ->where('status', TrafficSignLearningSession::STATUS_COMPLETED)
            ->whereNotNull('score_percent')
            ->avg('score_percent');
        $averageResponseTime = $this->answerQuery($since)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return [
            'period_days' => $days,
            'started_sessions' => $startedSessions,
            'completed_sessions' => $completedSessions,
            'completion_rate_percent' => $startedSessions > 0
                ? round(($completedSessions / $startedSessions) * 100, 1)
                : 0.0,
            'average_score_percent' => $averageScore !== null ? round((float) $averageScore, 1) : null,
            'average_response_time_ms' => $averageResponseTime !== null ? (int) round((float) $averageResponseTime) : null,
            'mode_breakdown' => $this->modeBreakdown($since),
            'top_confusions' => $this->topConfusions($since),
            'weakest_categories' => $this->weakestCategories($since),
        ];
    }

    /**
     * @return list<array{mode: string, started_sessions: int, completed_sessions: int, average_score_percent: float|null}>
     */
    protected function modeBreakdown(?Carbon $since): array
    {
        return $this->sessionQuery($since)
            ->select('mode')
            ->selectRaw('COUNT(*) as started_sessions')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_sessions', [TrafficSignLearningSession::STATUS_COMPLETED])
            ->selectRaw('AVG(score_percent) as average_score_percent')
            ->groupBy('mode')
            ->orderBy('mode')
            ->get()
            ->map(fn (TrafficSignLearningSession $row): array => [
                'mode' => (string) $row->mode,
                'started_sessions' => (int) $row->started_sessions,
                'completed_sessions' => (int) $row->completed_sessions,
                'average_score_percent' => $row->average_score_percent !== null
                    ? round((float) $row->average_score_percent, 1)
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{traffic_sign_id: int, code: string|null, name: string|null, count: int}>
     */
    protected function topConfusions(?Carbon $since): array
    {
        return $this->answerQuery($since)
            ->join('traffic_signs as selected_signs', 'selected_signs.id', '=', 'traffic_sign_learning_answers.selected_traffic_sign_id')
            ->where('traffic_sign_learning_answers.is_correct', false)
            ->whereNotNull('traffic_sign_learning_answers.selected_traffic_sign_id')
            ->select([
                'selected_signs.id as traffic_sign_id',
                'selected_signs.code',
                'selected_signs.name',
            ])
            ->selectRaw('COUNT(*) as count')
            ->groupBy('selected_signs.id', 'selected_signs.code', 'selected_signs.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => [
                'traffic_sign_id' => (int) $row->traffic_sign_id,
                'code' => $row->code !== null ? (string) $row->code : null,
                'name' => $row->name !== null ? (string) $row->name : null,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{category_id: int, slug: string|null, name: string|null, answers_count: int, correct_count: int, accuracy_percent: float}>
     */
    protected function weakestCategories(?Carbon $since): array
    {
        return $this->answerQuery($since)
            ->join('traffic_signs', 'traffic_signs.id', '=', 'traffic_sign_learning_answers.traffic_sign_id')
            ->join('traffic_sign_categories', 'traffic_sign_categories.id', '=', 'traffic_signs.traffic_sign_category_id')
            ->whereNotNull('traffic_sign_learning_answers.answered_at')
            ->select([
                'traffic_sign_categories.id as category_id',
                'traffic_sign_categories.slug',
                'traffic_sign_categories.name',
            ])
            ->selectRaw('COUNT(*) as answers_count')
            ->selectRaw('SUM(CASE WHEN traffic_sign_learning_answers.is_correct THEN 1 ELSE 0 END) as correct_count')
            ->groupBy('traffic_sign_categories.id', 'traffic_sign_categories.slug', 'traffic_sign_categories.name')
            ->get()
            ->map(function (object $row): array {
                $answersCount = (int) $row->answers_count;
                $correctCount = (int) $row->correct_count;

                return [
                    'category_id' => (int) $row->category_id,
                    'slug' => $row->slug !== null ? (string) $row->slug : null,
                    'name' => $row->name !== null ? (string) $row->name : null,
                    'answers_count' => $answersCount,
                    'correct_count' => $correctCount,
                    'accuracy_percent' => $answersCount > 0
                        ? round(($correctCount / $answersCount) * 100, 1)
                        : 0.0,
                ];
            })
            ->sortBy('accuracy_percent')
            ->take(5)
            ->values()
            ->all();
    }

    protected function sessionQuery(?Carbon $since): \Illuminate\Database\Eloquent\Builder
    {
        return TrafficSignLearningSession::query()
            ->when($since !== null, fn ($query) => $query->where('created_at', '>=', $since));
    }

    protected function answerQuery(?Carbon $since): \Illuminate\Database\Eloquent\Builder
    {
        return TrafficSignLearningAnswer::query()
            ->when($since !== null, fn ($query) => $query->where('traffic_sign_learning_answers.answered_at', '>=', $since));
    }
}
