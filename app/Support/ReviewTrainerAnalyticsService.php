<?php

namespace App\Support;

use App\Models\ReviewTrainerDailyAnswer;
use App\Models\ReviewTrainerEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ReviewTrainerAnalyticsService
{
    private const RECENT_WINDOW_LIMIT = 100;

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return array<string, mixed>
     */
    public function summary(User $user, ?int $categoryId = null, array $allowedCategoryIds = []): array
    {
        $today = today();
        $todayDailyQuery = $this->dailyAnswerTableExists()
            ? $this->dailyAnswerBaseQuery($user, $categoryId, $allowedCategoryIds)->whereDate('review_day', $today)
            : null;

        $todayAnsweredCount = $todayDailyQuery ? (clone $todayDailyQuery)->count() : 0;
        $todayCorrectCount = $todayDailyQuery ? (clone $todayDailyQuery)->where('is_correct', true)->count() : 0;
        $todayUnknownCount = $todayDailyQuery
            ? (clone $todayDailyQuery)->where('answer_kind', StudySessionAnswerKind::UNKNOWN)->count()
            : 0;
        $todayChoiceIncorrectCount = $todayDailyQuery
            ? (clone $todayDailyQuery)
                ->where('answer_kind', StudySessionAnswerKind::CHOICE)
                ->where('is_correct', false)
                ->count()
            : 0;
        $todayNeedsRecoveryCount = $todayDailyQuery ? (clone $todayDailyQuery)->where('is_correct', false)->count() : 0;

        if (! $this->eventTableExists()) {
            return $this->emptySummary(
                $today->toDateString(),
                $todayAnsweredCount,
                $todayCorrectCount,
                $todayUnknownCount,
                $todayChoiceIncorrectCount,
                $todayNeedsRecoveryCount,
            );
        }

        $baseQuery = $this->baseQuery($user, $categoryId, $allowedCategoryIds);
        $startedSessionsCount = (clone $baseQuery)
            ->where('event_name', 'review.session_started')
            ->count();

        $completedQuery = (clone $baseQuery)
            ->where('event_name', 'review.completed');

        $completedSessionsCount = (clone $completedQuery)->count();
        $completedFullSessionsCount = (clone $completedQuery)
            ->where('payload->completion_type', 'auto_full')
            ->count();
        $completedPartialSessionsCount = (clone $completedQuery)
            ->where('payload->completion_type', 'manual_partial')
            ->count();
        $completedUnclassifiedSessionsCount = max(
            0,
            $completedSessionsCount - $completedFullSessionsCount - $completedPartialSessionsCount,
        );
        $lastCompletedEvent = (clone $completedQuery)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        $recentCompletedEvents = (clone $completedQuery)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_WINDOW_LIMIT)
            ->get();
        $recentFullCompletedEvents = (clone $completedQuery)
            ->where('payload->completion_type', 'auto_full')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_WINDOW_LIMIT)
            ->get();

        $averageScorePercent = $this->averagePayloadNumber($recentCompletedEvents, 'score_percent');
        $averageDurationSeconds = $this->averagePayloadNumber($recentCompletedEvents, 'duration_seconds');
        $totalAnsweredCount = $recentCompletedEvents
            ->sum(fn (ReviewTrainerEvent $event): int => (int) ($event->payload['answered_count'] ?? 0));
        $fullAverageScorePercent = $this->averagePayloadNumber($recentFullCompletedEvents, 'score_percent');
        $fullAverageDurationSeconds = $this->averagePayloadNumber($recentFullCompletedEvents, 'duration_seconds');
        $fullTotalAnsweredCount = $recentFullCompletedEvents
            ->sum(fn (ReviewTrainerEvent $event): int => (int) ($event->payload['answered_count'] ?? 0));

        return [
            'started_sessions_count' => $startedSessionsCount,
            'completed_sessions_count' => $completedSessionsCount,
            'completed_full_sessions_count' => $completedFullSessionsCount,
            'completed_partial_sessions_count' => $completedPartialSessionsCount,
            'completed_unclassified_sessions_count' => $completedUnclassifiedSessionsCount,
            'completion_rate_percent' => $startedSessionsCount > 0
                ? round(($completedSessionsCount / $startedSessionsCount) * 100, 1)
                : null,
            'full_completion_rate_percent' => $startedSessionsCount > 0
                ? round(($completedFullSessionsCount / $startedSessionsCount) * 100, 1)
                : null,
            'average_score_percent' => $averageScorePercent !== null ? round($averageScorePercent, 1) : null,
            'average_duration_seconds' => $averageDurationSeconds !== null ? (int) round($averageDurationSeconds) : null,
            'average_duration_label' => $averageDurationSeconds !== null
                ? $this->durationLabel((int) round($averageDurationSeconds))
                : null,
            'total_answered_count' => $totalAnsweredCount,
            'full_average_score_percent' => $fullAverageScorePercent !== null ? round($fullAverageScorePercent, 1) : null,
            'full_average_duration_seconds' => $fullAverageDurationSeconds !== null ? (int) round($fullAverageDurationSeconds) : null,
            'full_average_duration_label' => $fullAverageDurationSeconds !== null
                ? $this->durationLabel((int) round($fullAverageDurationSeconds))
                : null,
            'full_total_answered_count' => $fullTotalAnsweredCount,
            'last_completed_at' => $lastCompletedEvent?->occurred_at?->toIso8601String(),
            'recent_window_limit' => self::RECENT_WINDOW_LIMIT,
            'today_review_day' => $today->toDateString(),
            'today_answered_count' => $todayAnsweredCount,
            'today_correct_count' => $todayCorrectCount,
            'today_unknown_count' => $todayUnknownCount,
            'today_choice_incorrect_count' => $todayChoiceIncorrectCount,
            'today_needs_recovery_count' => $todayNeedsRecoveryCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptySummary(
        string $todayReviewDay,
        int $todayAnsweredCount = 0,
        int $todayCorrectCount = 0,
        int $todayUnknownCount = 0,
        int $todayChoiceIncorrectCount = 0,
        int $todayNeedsRecoveryCount = 0,
    ): array {
        return [
            'started_sessions_count' => 0,
            'completed_sessions_count' => 0,
            'completed_full_sessions_count' => 0,
            'completed_partial_sessions_count' => 0,
            'completed_unclassified_sessions_count' => 0,
            'completion_rate_percent' => null,
            'full_completion_rate_percent' => null,
            'average_score_percent' => null,
            'average_duration_seconds' => null,
            'average_duration_label' => null,
            'total_answered_count' => 0,
            'full_average_score_percent' => null,
            'full_average_duration_seconds' => null,
            'full_average_duration_label' => null,
            'full_total_answered_count' => 0,
            'last_completed_at' => null,
            'recent_window_limit' => self::RECENT_WINDOW_LIMIT,
            'today_review_day' => $todayReviewDay,
            'today_answered_count' => $todayAnsweredCount,
            'today_correct_count' => $todayCorrectCount,
            'today_unknown_count' => $todayUnknownCount,
            'today_choice_incorrect_count' => $todayChoiceIncorrectCount,
            'today_needs_recovery_count' => $todayNeedsRecoveryCount,
        ];
    }

    protected function eventTableExists(): bool
    {
        return Schema::hasTable((new ReviewTrainerEvent)->getTable());
    }

    protected function dailyAnswerTableExists(): bool
    {
        return Schema::hasTable((new ReviewTrainerDailyAnswer)->getTable());
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return Builder<ReviewTrainerEvent>
     */
    protected function baseQuery(User $user, ?int $categoryId, array $allowedCategoryIds): Builder
    {
        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));

        return ReviewTrainerEvent::query()
            ->where('user_id', $user->getKey())
            ->when($categoryId, fn (Builder $query) => $query->where('license_category_id', $categoryId))
            ->when($allowedCategoryIds !== [], fn (Builder $query) => $query->whereIn('license_category_id', $allowedCategoryIds));
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return Builder<ReviewTrainerDailyAnswer>
     */
    protected function dailyAnswerBaseQuery(User $user, ?int $categoryId, array $allowedCategoryIds): Builder
    {
        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));

        return ReviewTrainerDailyAnswer::query()
            ->where('user_id', $user->getKey())
            ->when($categoryId, fn (Builder $query) => $query->where('license_category_id', $categoryId))
            ->when($allowedCategoryIds !== [], fn (Builder $query) => $query->whereIn('license_category_id', $allowedCategoryIds));
    }

    /**
     * @param  Collection<int, ReviewTrainerEvent>  $events
     */
    protected function averagePayloadNumber(Collection $events, string $key): ?float
    {
        $values = $events
            ->map(fn (ReviewTrainerEvent $event): mixed => $event->payload[$key] ?? null)
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): float => (float) $value)
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        return (float) $values->avg();
    }

    protected function durationLabel(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 min';
        }

        $minutes = (int) ceil($seconds / 60);

        return "{$minutes} min";
    }
}
