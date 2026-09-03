<?php

namespace App\Support;

use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySessionAnswer;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ReviewTrainerDailyPlanService
{
    public const VERSION = 'review-daily-plan-v1';

    public const DAILY_TARGET_COUNT = 80;

    public const MINIMUM_SESSION_QUESTION_COUNT = 50;

    public function reviewDay(?CarbonInterface $now = null): string
    {
        return ($now ? Carbon::parse($now) : now())->toDateString();
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return array<string, int|string>
     */
    public function context(User $user, ?int $categoryId = null, array $allowedCategoryIds = [], ?CarbonInterface $now = null): array
    {
        $reviewDay = $this->reviewDay($now);
        $completedTodayCount = $this->completedTodayCount($user, $categoryId, $allowedCategoryIds, $now);

        return [
            'daily_plan_policy_version' => self::VERSION,
            'review_day' => $reviewDay,
            'daily_target_count' => self::DAILY_TARGET_COUNT,
            'minimum_session_question_count' => self::MINIMUM_SESSION_QUESTION_COUNT,
            'completed_today_count' => $completedTodayCount,
            'daily_remaining_count' => max(self::DAILY_TARGET_COUNT - $completedTodayCount, 0),
        ];
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     */
    public function completedTodayCount(User $user, ?int $categoryId = null, array $allowedCategoryIds = [], ?CarbonInterface $now = null): int
    {
        $reviewDay = $this->reviewDay($now);
        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));

        if ($this->dailyAnswerTableExists()) {
            return $this->ledgerCompletedTodayCount($user, $reviewDay, $categoryId, $allowedCategoryIds)
                + $this->rawCompletedTodayCount($user, $reviewDay, $categoryId, $allowedCategoryIds, true);
        }

        return $this->rawCompletedTodayCount($user, $reviewDay, $categoryId, $allowedCategoryIds);
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     */
    protected function ledgerCompletedTodayCount(User $user, string $reviewDay, ?int $categoryId, array $allowedCategoryIds): int
    {
        $reviewDayStart = Carbon::parse($reviewDay)->toDateString();
        $reviewDayEnd = Carbon::parse($reviewDay)->addDay()->toDateString();

        return (int) ReviewTrainerDailyAnswer::query()
            ->where('user_id', $user->getKey())
            ->where('review_day', '>=', $reviewDayStart)
            ->where('review_day', '<', $reviewDayEnd)
            ->where('daily_plan_policy_version', self::VERSION)
            ->when($categoryId, fn ($query) => $query->where('license_category_id', $categoryId))
            ->when(! $categoryId && $allowedCategoryIds !== [], fn ($query) => $query->whereIn('license_category_id', $allowedCategoryIds))
            ->count();
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     */
    protected function rawCompletedTodayCount(User $user, string $reviewDay, ?int $categoryId, array $allowedCategoryIds, bool $excludeLedgeredAnswers = false): int
    {
        $reviewDayStart = Carbon::parse($reviewDay)->startOfDay();
        $reviewDayEnd = $reviewDayStart->copy()->addDay();

        return (int) StudySessionAnswer::query()
            ->join('study_sessions', 'study_sessions.id', '=', 'study_session_answers.study_session_id')
            ->where('study_sessions.user_id', $user->getKey())
            ->where('study_sessions.mode', StudySessionManager::MODE_SR_REVIEW)
            ->when($categoryId, fn ($query) => $query->where('study_sessions.license_category_id', $categoryId))
            ->when(! $categoryId && $allowedCategoryIds !== [], fn ($query) => $query->whereIn('study_sessions.license_category_id', $allowedCategoryIds))
            ->where('study_session_answers.answered_at', '>=', $reviewDayStart)
            ->where('study_session_answers.answered_at', '<', $reviewDayEnd)
            ->when($excludeLedgeredAnswers, function ($query): void {
                $query->whereNotExists(function ($ledgerQuery): void {
                    $ledgerQuery
                        ->selectRaw('1')
                        ->from('review_trainer_daily_answers as ledgered_daily_answers')
                        ->whereColumn('ledgered_daily_answers.study_session_answer_id', 'study_session_answers.id');
                });
            })
            ->count();
    }

    protected function dailyAnswerTableExists(): bool
    {
        return Schema::hasTable((new ReviewTrainerDailyAnswer)->getTable());
    }
}
