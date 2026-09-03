<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryAnalyticsService
{
    public function __construct(
        protected UserReadinessService $userReadinessService,
        protected HardQuestionService $hardQuestionService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, LicenseCategory $category): array
    {
        $cacheKey = sprintf(
            'category-analytics:v1:user:%d:category:%d',
            $user->getKey(),
            $category->getKey(),
        );

        if (app()->environment('testing')) {
            return $this->buildPayload($user, $category);
        }

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(30),
            fn (): array => $this->buildPayload($user, $category),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(User $user, LicenseCategory $category): array
    {
        $progressEntries = UserQuestionProgress::query()
            ->join('questions', 'questions.id', '=', 'user_question_progress.question_id')
            ->select([
                'user_question_progress.id',
                'user_question_progress.question_id',
                'user_question_progress.next_review_at',
                'user_question_progress.last_quality',
                'user_question_progress.total_attempts',
                'user_question_progress.correct_count',
                'user_question_progress.incorrect_count',
                'user_question_progress.correct_streak',
            ])
            ->with('question:id,external_id,prompt,question_type,difficulty,points')
            ->where('user_id', $user->getKey())
            ->where('user_question_progress.total_attempts', '>', 0)
            ->where('questions.license_category_id', $category->getKey())
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->get();

        $activeQuestionsCount = $category->questions()
            ->where('is_active', true)
            ->readyForDelivery()
            ->count();

        $answerRows = StudySessionAnswer::query()
            ->whereExists(function ($query) use ($user): void {
                $query
                    ->selectRaw('1')
                    ->from('study_sessions')
                    ->whereColumn('study_sessions.id', 'study_session_answers.study_session_id')
                    ->where('study_sessions.user_id', $user->getKey())
                    ->where('study_sessions.mode', '!=', StudySessionManager::MODE_SR_REVIEW)
                    ->whereNull('study_sessions.question_collection_id')
                    ->whereNull('study_sessions.question_module_id')
                    ->where(function ($sessionQuery): void {
                        $sessionQuery
                            ->whereNull('study_sessions.payload->context->type')
                            ->orWhere('study_sessions.payload->context->type', '!=', 'question_module');
                    });
            })
            ->whereExists(function ($query) use ($category): void {
                $query
                    ->selectRaw('1')
                    ->from('questions')
                    ->whereColumn('questions.id', 'study_session_answers.question_id')
                    ->where('questions.license_category_id', $category->getKey())
                    ->where('questions.is_active', true)
                    ->whereNull('questions.delivery_issue');
            })
            ->get([
                'study_session_answers.is_correct',
                'study_session_answers.response_time_ms',
                'study_session_answers.created_at',
            ]);

        $answeredCount = $answerRows->count();
        $correctAnswersCount = $answerRows->where('is_correct', true)->count();
        $avgResponseTimeMs = $answerRows
            ->pluck('response_time_ms')
            ->filter(fn (mixed $value): bool => filled($value))
            ->avg();
        $lastAnsweredAt = $answerRows->max('created_at');

        $completedSessionsCount = $user->studySessions()
            ->regularCategory()
            ->where('license_category_id', $category->getKey())
            ->where('status', 'completed')
            ->where('mode', '!=', StudySessionManager::MODE_SR_REVIEW)
            ->count();

        $readyForReviewCount = $progressEntries
            ->filter(fn (UserQuestionProgress $progress): bool => $progress->next_review_at?->lessThanOrEqualTo(today()) ?? false)
            ->count();

        $hardQuestionsCount = $progressEntries
            ->filter(fn (UserQuestionProgress $progress): bool => $this->hardQuestionService->isHard($progress))
            ->count();

        $readinessScore = $progressEntries->isNotEmpty()
            ? round((float) $progressEntries->avg(
                fn (UserQuestionProgress $progress): float => $this->userReadinessService->mastery($progress),
            ), 1)
            : round((float) ($user->studySessions()
                ->regularCategory()
                ->where('status', 'completed')
                ->where('license_category_id', $category->getKey())
                ->where('mode', '!=', StudySessionManager::MODE_SR_REVIEW)
                ->avg('score_percent') ?? 0), 1);

        return [
            'category' => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
                'description' => $category->description,
            ],
            'summary' => [
                'questions_total' => $activeQuestionsCount,
                'tracked_questions_count' => $progressEntries->count(),
                'coverage_pct' => $activeQuestionsCount > 0
                    ? round(($progressEntries->count() / $activeQuestionsCount) * 100, 1)
                    : 0.0,
                'completed_sessions_count' => $completedSessionsCount,
                'answered_count' => $answeredCount,
                'correct_answers_count' => $correctAnswersCount,
                'accuracy_pct' => $answeredCount > 0
                    ? round(($correctAnswersCount / $answeredCount) * 100, 1)
                    : 0.0,
                'avg_response_time_ms' => $avgResponseTimeMs !== null ? (int) round((float) $avgResponseTimeMs) : null,
                'readiness_score' => $readinessScore,
                'ready_for_review_count' => $readyForReviewCount,
                'hard_questions_count' => $hardQuestionsCount,
                'last_answered_at' => filled($lastAnsweredAt)
                    ? Carbon::parse((string) $lastAnsweredAt)->toIso8601String()
                    : null,
            ],
            'breakdowns' => [
                'question_types' => $this->breakdown(
                    $progressEntries,
                    fn (UserQuestionProgress $progress): string => (string) ($progress->question?->question_type ?? 'unknown'),
                    fn (string $type): string => match ($type) {
                        'boolean' => 'Tak / nie',
                        'single_choice' => 'Jednokrotny wybor',
                        default => 'Inne',
                    },
                ),
                'points' => $this->breakdown(
                    $progressEntries,
                    fn (UserQuestionProgress $progress): string => (string) ($progress->question?->points ?? '0'),
                    fn (string $points): string => "{$points} pkt",
                ),
                'difficulty' => $this->breakdown(
                    $progressEntries,
                    fn (UserQuestionProgress $progress): string => (string) ($progress->question?->difficulty ?? '0'),
                    fn (string $difficulty): string => "Poziom {$difficulty}",
                ),
            ],
            'weak_spots' => $this->weakSpots($progressEntries),
            'recent_activity' => $this->recentActivity($answerRows),
        ];
    }

    /**
     * @param  Collection<int, UserQuestionProgress>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    protected function breakdown(Collection $entries, callable $groupBy, callable $labelResolver): Collection
    {
        return $entries
            ->groupBy($groupBy)
            ->map(function (Collection $group, string $key) use ($labelResolver): array {
                $attempts = max($group->sum('total_attempts'), 1);
                $correctCount = $group->sum('correct_count');
                $readinessScore = round((float) $group->avg(
                    fn (UserQuestionProgress $progress): float => $this->userReadinessService->mastery($progress),
                ), 1);

                return [
                    'key' => is_numeric($key) ? (int) $key : $key,
                    'label' => $labelResolver($key),
                    'tracked_questions_count' => $group->count(),
                    'accuracy_pct' => round(($correctCount / $attempts) * 100, 1),
                    'readiness_score' => $readinessScore,
                    'ready_for_review_count' => $group->filter(
                        fn (UserQuestionProgress $progress): bool => $progress->next_review_at?->lessThanOrEqualTo(today()) ?? false
                    )->count(),
                ];
            })
            ->sortBy('key')
            ->values();
    }

    /**
     * @param  Collection<int, UserQuestionProgress>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    protected function weakSpots(Collection $entries): Collection
    {
        return $entries
            ->map(function (UserQuestionProgress $progress): array {
                $attempts = max($progress->total_attempts, 1);
                $masteryScore = $this->userReadinessService->mastery($progress);

                return [
                    'question_id' => $progress->question_id,
                    'external_id' => $progress->question?->external_id,
                    'prompt' => $progress->question?->prompt,
                    'question_type' => $progress->question?->question_type,
                    'difficulty' => $progress->question?->difficulty,
                    'points' => $progress->question?->points,
                    'accuracy_pct' => round(($progress->correct_count / $attempts) * 100, 1),
                    'mastery_score' => round($masteryScore, 1),
                    'total_attempts' => $progress->total_attempts,
                    'incorrect_count' => $progress->incorrect_count,
                    'correct_streak' => $progress->correct_streak,
                    'last_quality' => $progress->last_quality,
                    'next_review_at' => $progress->next_review_at?->toDateString(),
                ];
            })
            ->sort(function (array $left, array $right): int {
                $comparisons = [
                    $left['mastery_score'] <=> $right['mastery_score'],
                    $right['incorrect_count'] <=> $left['incorrect_count'],
                    $right['total_attempts'] <=> $left['total_attempts'],
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return $left['question_id'] <=> $right['question_id'];
            })
            ->take(5)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function recentActivity(Collection $answerRows): Collection
    {
        $windowStart = today()->subDays(6)->startOfDay();

        $rows = $answerRows
            ->filter(fn (StudySessionAnswer $answer): bool => $answer->created_at !== null && $answer->created_at->greaterThanOrEqualTo($windowStart))
            ->groupBy(fn (StudySessionAnswer $answer): string => $answer->created_at?->toDateString() ?? '')
            ->map(function (Collection $group): array {
                $answeredCount = $group->count();
                $correctCount = $group->where('is_correct', true)->count();

                return [
                    'answered_count' => $answeredCount,
                    'correct_count' => $correctCount,
                ];
            });

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($rows): array {
                $day = today()->subDays($daysAgo)->toDateString();
                $row = $rows->get($day);
                $answeredCount = (int) ($row['answered_count'] ?? 0);
                $correctCount = (int) ($row['correct_count'] ?? 0);

                return [
                    'date' => $day,
                    'answered_count' => $answeredCount,
                    'correct_count' => $correctCount,
                    'accuracy_pct' => $answeredCount > 0
                        ? round(($correctCount / $answeredCount) * 100, 1)
                        : 0.0,
                ];
            })
            ->values();
    }
}
