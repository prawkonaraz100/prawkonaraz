<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function __construct(
        protected UserReadinessService $userReadinessService,
        protected HardQuestionService $hardQuestionService,
        protected StudyContextService $studyContextService,
    ) {}

    /**
     * @return array{categories: Collection<int, array<string, mixed>>, recentSessions: Collection<int, array<string, mixed>>, stats: array<string, mixed>}
     */
    public function build(User $user): array
    {
        $visibleCategories = $this->studyContextService->activeCategories($user);
        $visibleCategoryIds = $visibleCategories
            ->pluck('id')
            ->map(fn ($categoryId) => (int) $categoryId)
            ->all();

        $reviewReadyByCategory = UserQuestionProgress::query()
            ->selectRaw('questions.license_category_id as license_category_id, COUNT(*) as review_ready_count')
            ->join('questions', 'questions.id', '=', 'user_question_progress.question_id')
            ->where('user_question_progress.user_id', $user->getKey())
            ->whereDate('user_question_progress.next_review_at', '<=', today())
            ->when($visibleCategoryIds !== [], function ($query) use ($visibleCategoryIds): void {
                $query->whereIn('questions.license_category_id', $visibleCategoryIds);
            })
            ->groupBy('questions.license_category_id')
            ->pluck('review_ready_count', 'license_category_id');

        $hardQuestionCountsByCategory = $this->hardQuestionService->countsByCategory($user);
        $visibleHardQuestionCount = collect($visibleCategoryIds)
            ->sum(fn (int $categoryId): int => (int) ($hardQuestionCountsByCategory[$categoryId] ?? 0));

        $questionCounts = LicenseCategory::query()
            ->whereKey($visibleCategoryIds)
            ->withCount([
                'questions' => fn ($query) => $query->where('is_active', true)->readyForDelivery(),
            ])
            ->get()
            ->keyBy('id');

        $categories = $visibleCategories
            ->map(fn (LicenseCategory $category) => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'questions_count' => (int) ($questionCounts->get($category->getKey())?->questions_count ?? 0),
                'review_ready_count' => (int) ($reviewReadyByCategory[$category->getKey()] ?? 0),
                'hard_questions_count' => (int) ($hardQuestionCountsByCategory[$category->getKey()] ?? 0),
            ])
            ->values();

        $recentSessions = $user->studySessions()
            ->with('licenseCategory')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (StudySession $studySession) => [
                'id' => $studySession->getKey(),
                'mode' => $studySession->mode,
                'status' => $studySession->status,
                'license_category_name' => $studySession->licenseCategory?->name,
                'score_percent' => $studySession->score_percent !== null ? (float) $studySession->score_percent : null,
                'completed_at' => $studySession->completed_at?->toIso8601String(),
            ])
            ->values();

        $sessionsTodayQuery = $user->studySessions()
            ->whereDate('created_at', today());
        $sessionsToday = (clone $sessionsTodayQuery)->count();
        $classicSessionsToday = (clone $sessionsTodayQuery)
            ->where('mode', '!=', StudySessionManager::MODE_SR_REVIEW)
            ->count();
        $memoryTrainerSessionsToday = (clone $sessionsTodayQuery)
            ->where('mode', StudySessionManager::MODE_SR_REVIEW)
            ->count();

        $answeredTodayQuery = StudySessionAnswer::query()
            ->whereHas('studySession', fn ($query) => $query->where('user_id', $user->getKey()))
            ->whereDate('created_at', today());
        $answeredToday = (clone $answeredTodayQuery)->count();
        $classicAnsweredToday = (clone $answeredTodayQuery)
            ->whereHas('studySession', fn ($query) => $query->where('mode', '!=', StudySessionManager::MODE_SR_REVIEW))
            ->count();
        $memoryTrainerAnsweredToday = (clone $answeredTodayQuery)
            ->whereHas('studySession', fn ($query) => $query->where('mode', StudySessionManager::MODE_SR_REVIEW))
            ->count();

        $lastSessionAt = $user->studySessions()
            ->latest('started_at')
            ->first()?->started_at;

        return [
            'categories' => $categories,
            'recentSessions' => $recentSessions,
            'stats' => [
                'sessions_today' => $sessionsToday,
                'answered_today' => $answeredToday,
                'classic_sessions_today' => $classicSessionsToday,
                'classic_answered_today' => $classicAnsweredToday,
                'memory_trainer_sessions_today' => $memoryTrainerSessionsToday,
                'memory_trainer_answered_today' => $memoryTrainerAnsweredToday,
                'ready_for_review_count' => (int) $reviewReadyByCategory->sum(),
                'hard_questions_count' => (int) $visibleHardQuestionCount,
                'readiness_score' => $this->userReadinessService->score($user),
                'study_streak' => $this->studyStreak($user),
                'last_session_at' => $lastSessionAt?->toIso8601String(),
            ],
        ];
    }

    protected function studyStreak(User $user): int
    {
        $activityDates = $user->studySessions()
            ->latest('created_at')
            ->get()
            ->map(fn (StudySession $studySession) => $studySession->created_at?->toDateString())
            ->filter()
            ->unique()
            ->values();

        if ($activityDates->isEmpty()) {
            return 0;
        }

        $expectedDate = today();
        $streak = 0;

        foreach ($activityDates as $activityDate) {
            if (! Carbon::parse($activityDate)->isSameDay($expectedDate)) {
                break;
            }

            $streak++;
            $expectedDate = $expectedDate->copy()->subDay();
        }

        return $streak;
    }
}
