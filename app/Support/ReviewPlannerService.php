<?php

namespace App\Support;

use App\Models\ReviewMemoryProgress;
use App\Models\StudySessionAnswer;
use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReviewPlannerService
{
    public const VERSION = 'review-planner-v2';

    public const COACH_VERSION = 'review-coach-v1';

    private const PREVIEW_LIMIT = 60;

    private const FALLBACK_SECONDS_PER_QUESTION = 45;

    private const NEW_CANDIDATE_DAILY_LIMIT = 15;

    private const NEW_CANDIDATE_MAX_SESSION_SHARE = 0.30;

    public function __construct(
        protected ReviewMemorySignalService $memorySignalService,
        protected ReviewMemoryVerifiedSignalService $verifiedMemorySignalService,
        protected ReviewMemoryLegacySignalMapper $legacySignalMapper,
        protected ReviewTrainerDailyPlanService $dailyPlanService,
    ) {}

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return array<string, mixed>
     */
    public function plan(User $user, ?int $categoryId = null, array $allowedCategoryIds = []): array
    {
        $today = today();
        $dailyContext = $this->dailyPlanService->context($user, $categoryId, $allowedCategoryIds);
        $sessionTarget = $this->sessionTarget(
            (int) $dailyContext['completed_today_count'],
            (int) $dailyContext['daily_remaining_count'],
        );
        $orderedProgressPool = $this->orderedProgress($user, $categoryId, $allowedCategoryIds);
        $selectedProgress = $this->selectedProgressForSession($orderedProgressPool, $sessionTarget, $today);
        $orderedProgress = $selectedProgress['progress'];
        $progressQuestionIds = $orderedProgress
            ->pluck('question_id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->values()
            ->all();

        $dueCount = (int) $selectedProgress['primary_count'];
        $boosterCount = (int) $selectedProgress['booster_count'];
        $actionableCount = (int) $selectedProgress['actionable_count'];
        $verificationCandidateCount = (int) $selectedProgress['verification_candidate_count'];
        $selectedVerificationCandidateCount = (int) $selectedProgress['selected_verification_candidate_count'];
        $newCandidateQuestionIds = $this->newCandidateQuestionIds(
            $user,
            $categoryId,
            $allowedCategoryIds,
            count($progressQuestionIds),
            $sessionTarget,
            $today,
        );
        $orderedQuestionIds = [
            ...$progressQuestionIds,
            ...$newCandidateQuestionIds,
        ];
        $candidateCount = count($orderedQuestionIds);
        $newCandidateCount = count($newCandidateQuestionIds);
        $candidateSourceCounts = [
            'primary' => $dueCount,
            'seen_booster' => $boosterCount,
            'new_candidate' => $newCandidateCount,
        ];
        $recommendedQuestionCount = $this->recommendedQuestionCount(
            $candidateCount,
            (int) $dailyContext['completed_today_count'],
            (int) $dailyContext['daily_remaining_count'],
        );
        $estimatedDurationSeconds = $this->estimatedDurationSeconds($user, $categoryId, $recommendedQuestionCount);
        $signalSummary = $this->signalSummary($orderedProgress->take($recommendedQuestionCount));

        return [
            'planner_version' => self::VERSION,
            ...$dailyContext,
            'memory_signal_version' => ReviewMemorySignalService::VERSION,
            'verified_memory_signal_version' => ReviewMemoryVerifiedSignalService::VERSION,
            'due_count' => $dueCount,
            'candidate_count' => $candidateCount,
            'booster_count' => $boosterCount,
            'actionable_count' => $actionableCount,
            'verification_candidate_count' => $verificationCandidateCount,
            'selected_verification_candidate_count' => $selectedVerificationCandidateCount,
            'new_candidate_count' => $newCandidateCount,
            'candidate_source_counts' => $candidateSourceCounts,
            'recommended_question_count' => $recommendedQuestionCount,
            'estimated_duration_seconds' => $estimatedDurationSeconds,
            'estimated_duration_label' => $this->durationLabel($estimatedDurationSeconds),
            'segment_counts' => $signalSummary['segment_counts'],
            'memory_state_counts' => $signalSummary['memory_state_counts'],
            'coach' => $this->coach(
                $dueCount,
                $recommendedQuestionCount,
                $this->durationLabel($estimatedDurationSeconds),
                $signalSummary,
            ),
            'ordered_question_ids' => $orderedQuestionIds,
            'preview_question_ids' => array_slice($orderedQuestionIds, 0, self::PREVIEW_LIMIT),
            'preview_limit' => self::PREVIEW_LIMIT,
            'preview_count' => min($candidateCount, self::PREVIEW_LIMIT),
            'has_more' => $candidateCount > self::PREVIEW_LIMIT,
        ];
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return array<int, int>
     */
    public function recommendedQuestionIds(User $user, int $categoryId, int $requestedCount, array $allowedCategoryIds = []): array
    {
        $plan = $this->plan($user, $categoryId, $allowedCategoryIds);

        return $this->recommendedQuestionIdsFromPlan($plan, $requestedCount);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<int, int>
     */
    public function recommendedQuestionIdsFromPlan(array $plan, int $requestedCount): array
    {
        $candidateCount = (int) ($plan['candidate_count'] ?? $plan['due_count'] ?? 0);

        $limit = max(0, min(
            $requestedCount,
            $candidateCount,
            (int) $plan['recommended_question_count'],
        ));

        return collect($plan['ordered_question_ids'] ?? [])
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->take($limit)
            ->values()
            ->all();
    }

    protected function recommendedQuestionCount(int $candidateCount, int $completedTodayCount, int $dailyRemainingCount): int
    {
        if ($candidateCount <= 0 || $dailyRemainingCount <= 0) {
            return 0;
        }

        $sessionTarget = $completedTodayCount <= 0
            ? min(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT, $dailyRemainingCount)
            : $dailyRemainingCount;

        return min($sessionTarget, $candidateCount);
    }

    protected function sessionTarget(int $completedTodayCount, int $dailyRemainingCount): int
    {
        if ($dailyRemainingCount <= 0) {
            return 0;
        }

        if ($completedTodayCount <= 0) {
            return min(ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT, $dailyRemainingCount);
        }

        return $dailyRemainingCount;
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return array<int, int>
     */
    protected function newCandidateQuestionIds(
        User $user,
        ?int $categoryId,
        array $allowedCategoryIds,
        int $existingCandidateCount,
        int $sessionTarget,
        Carbon $today,
    ): array {
        $shortage = max(0, $sessionTarget - $existingCandidateCount);

        if ($shortage <= 0) {
            return [];
        }

        $limitByShare = max(1, (int) floor($sessionTarget * self::NEW_CANDIDATE_MAX_SESSION_SHARE));
        $limit = min($shortage, self::NEW_CANDIDATE_DAILY_LIMIT, $limitByShare);

        if ($limit <= 0) {
            return [];
        }

        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));
        $reviewDayStart = $today->copy()->startOfDay();
        $reviewDayEnd = $reviewDayStart->copy()->addDay();

        return Question::query()
            ->where('questions.is_active', true)
            ->readyForDelivery()
            ->whereNotExists(function ($query) use ($user): void {
                $query
                    ->selectRaw('1')
                    ->from('user_question_progress as candidate_progress')
                    ->whereColumn('candidate_progress.question_id', 'questions.id')
                    ->where('candidate_progress.user_id', $user->getKey());
            })
            ->whereNotExists(function ($query) use ($user): void {
                $query
                    ->selectRaw('1')
                    ->from('review_memory_progress as candidate_memory')
                    ->whereColumn('candidate_memory.question_id', 'questions.id')
                    ->where('candidate_memory.user_id', $user->getKey());
            })
            ->whereNotExists(function ($query) use ($reviewDayEnd, $reviewDayStart, $user): void {
                $query
                    ->selectRaw('1')
                    ->from('study_session_answers as today_answers')
                    ->join('study_sessions as today_sessions', 'today_sessions.id', '=', 'today_answers.study_session_id')
                    ->whereColumn('today_answers.question_id', 'questions.id')
                    ->where('today_sessions.user_id', $user->getKey())
                    ->where('today_answers.answered_at', '>=', $reviewDayStart)
                    ->where('today_answers.answered_at', '<', $reviewDayEnd);
            })
            ->when($categoryId, fn ($query) => $query->where('questions.license_category_id', $categoryId))
            ->when($allowedCategoryIds !== [], fn ($query) => $query->whereIn('questions.license_category_id', $allowedCategoryIds))
            ->orderBy('questions.difficulty')
            ->orderByDesc('questions.published_at')
            ->orderBy('questions.id')
            ->limit($limit)
            ->pluck('questions.id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return Collection<int, UserQuestionProgress>
     */
    protected function orderedProgress(User $user, ?int $categoryId, array $allowedCategoryIds): Collection
    {
        $today = today();
        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));

        $progressRows = UserQuestionProgress::query()
            ->select([
                'user_question_progress.*',
                'questions.license_category_id as question_license_category_id',
                'questions.difficulty as question_difficulty',
                'review_memory_progress.id as review_memory_progress_id',
                'review_memory_progress.verified_attempts_count as review_verified_attempts_count',
                'review_memory_progress.verified_correct_count as review_verified_correct_count',
                'review_memory_progress.verified_unknown_count as review_verified_unknown_count',
                'review_memory_progress.verified_incorrect_count as review_verified_incorrect_count',
                'review_memory_progress.verified_correct_streak as review_verified_correct_streak',
                'review_memory_progress.last_verified_result as review_last_verified_result',
                'review_memory_progress.last_verified_at as review_last_verified_at',
                'review_memory_progress.last_study_session_answer_id as review_last_study_session_answer_id',
                'review_memory_progress.next_verified_review_at as review_next_verified_review_at',
                'review_memory_progress.verified_memory_state as review_verified_memory_state',
                'review_memory_progress.source_policy_version as review_source_policy_version',
            ])
            ->join('questions', 'questions.id', '=', 'user_question_progress.question_id')
            ->leftJoin('review_memory_progress', function ($join) use ($user): void {
                $join
                    ->on('review_memory_progress.question_id', '=', 'user_question_progress.question_id')
                    ->where('review_memory_progress.user_id', '=', $user->getKey());
            })
            ->where('user_question_progress.user_id', $user->getKey())
            ->where(function ($query) use ($today): void {
                $query
                    ->where(function ($memoryQuery) use ($today): void {
                        $memoryQuery
                            ->whereNotNull('review_memory_progress.id')
                            ->where(function ($stateQuery) use ($today): void {
                                $stateQuery
                                    ->where('review_memory_progress.verified_memory_state', ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
                                    ->orWhereDate('review_memory_progress.next_verified_review_at', '<=', $today);
                            });
                    })
                    ->orWhere(function ($classicQuery) use ($today): void {
                        $classicQuery
                            ->whereNull('review_memory_progress.id')
                            ->where(function ($classicStateQuery) use ($today): void {
                                $classicStateQuery
                                    ->whereDate('user_question_progress.next_review_at', '<=', $today)
                                    ->orWhere('user_question_progress.total_attempts', '>', 0);
                            });
                    });
            })
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->whereNotExists(function ($query) use ($today, $user): void {
                $query
                    ->selectRaw('1')
                    ->from('study_session_answers as today_review_answers')
                    ->join('study_sessions as today_review_sessions', 'today_review_sessions.id', '=', 'today_review_answers.study_session_id')
                    ->whereColumn('today_review_answers.question_id', 'user_question_progress.question_id')
                    ->where('today_review_sessions.user_id', $user->getKey())
                    ->where('today_review_sessions.mode', StudySessionManager::MODE_SR_REVIEW)
                    ->whereDate('today_review_answers.answered_at', $today);
            })
            ->when($categoryId, fn ($query) => $query->where('questions.license_category_id', $categoryId))
            ->when($allowedCategoryIds !== [], fn ($query) => $query->whereIn('questions.license_category_id', $allowedCategoryIds))
            ->get();

        return $progressRows
            ->concat($this->memoryOnlyProgressRows($user, $categoryId, $allowedCategoryIds, $today))
            ->map(fn (UserQuestionProgress $progress): array => $this->priorityRow($progress, $today))
            ->sort(fn (array $left, array $right): int => $this->comparePriorityRows($left, $right))
            ->map(fn (array $row): UserQuestionProgress => $row['progress'])
            ->values();
    }

    /**
     * @param  Collection<int, UserQuestionProgress>  $orderedProgressPool
     * @return array{
     *     progress: Collection<int, UserQuestionProgress>,
     *     primary_count: int,
     *     booster_count: int,
     *     actionable_count: int,
     *     verification_candidate_count: int,
     *     selected_verification_candidate_count: int
     * }
     */
    protected function selectedProgressForSession(Collection $orderedProgressPool, int $sessionTarget, Carbon $today): array
    {
        if ($sessionTarget <= 0) {
            return [
                'progress' => collect(),
                'primary_count' => 0,
                'booster_count' => 0,
                'actionable_count' => 0,
                'verification_candidate_count' => 0,
                'selected_verification_candidate_count' => 0,
            ];
        }

        $actionableProgress = $orderedProgressPool
            ->filter(fn (UserQuestionProgress $progress): bool => $this->hasVerifiedActionableProgress($progress, $today))
            ->values();
        $verificationCandidateProgress = $orderedProgressPool
            ->filter(fn (UserQuestionProgress $progress): bool => ! $this->hasVerifiedMemoryProgress($progress)
                && $this->isClassicDueProgress($progress, $today))
            ->values();
        $boosterProgress = $orderedProgressPool
            ->filter(fn (UserQuestionProgress $progress): bool => ! $this->hasVerifiedMemoryProgress($progress)
                && ! $this->isClassicDueProgress($progress, $today)
                && (int) ($progress->total_attempts ?? 0) > 0)
            ->values();

        $selectedActionableProgress = $actionableProgress
            ->take($sessionTarget)
            ->values();
        $remainingAfterActionable = max(0, $sessionTarget - $selectedActionableProgress->count());
        $selectedVerificationCandidateProgress = $verificationCandidateProgress
            ->take($remainingAfterActionable)
            ->values();
        $remainingAfterPrimary = max(0, $remainingAfterActionable - $selectedVerificationCandidateProgress->count());
        $selectedBoosterProgress = $boosterProgress
            ->take($remainingAfterPrimary)
            ->values();
        $progress = $selectedActionableProgress
            ->concat($selectedVerificationCandidateProgress)
            ->concat($selectedBoosterProgress)
            ->values();

        return [
            'progress' => $progress,
            'primary_count' => $selectedActionableProgress->count() + $selectedVerificationCandidateProgress->count(),
            'booster_count' => $selectedBoosterProgress->count(),
            'actionable_count' => $selectedActionableProgress->count(),
            'verification_candidate_count' => $verificationCandidateProgress->count(),
            'selected_verification_candidate_count' => $selectedVerificationCandidateProgress->count(),
        ];
    }

    /**
     * @param  array<int, int>  $allowedCategoryIds
     * @return Collection<int, UserQuestionProgress>
     */
    protected function memoryOnlyProgressRows(User $user, ?int $categoryId, array $allowedCategoryIds, Carbon $today): Collection
    {
        $allowedCategoryIds = array_values(array_unique(array_map('intval', $allowedCategoryIds)));

        return ReviewMemoryProgress::query()
            ->select([
                'review_memory_progress.*',
                'questions.difficulty as question_difficulty',
            ])
            ->join('questions', 'questions.id', '=', 'review_memory_progress.question_id')
            ->where('review_memory_progress.user_id', $user->getKey())
            ->where(function ($query) use ($today): void {
                $query
                    ->where('review_memory_progress.verified_memory_state', ReviewMemoryProgress::STATE_NEEDS_RECOVERY)
                    ->orWhereDate('review_memory_progress.next_verified_review_at', '<=', $today);
            })
            ->where('questions.is_active', true)
            ->whereNull('questions.delivery_issue')
            ->whereNotExists(function ($query) use ($user): void {
                $query
                    ->selectRaw('1')
                    ->from('user_question_progress as memory_only_classic_progress')
                    ->whereColumn('memory_only_classic_progress.question_id', 'review_memory_progress.question_id')
                    ->where('memory_only_classic_progress.user_id', $user->getKey());
            })
            ->whereNotExists(function ($query) use ($today, $user): void {
                $query
                    ->selectRaw('1')
                    ->from('study_session_answers as today_review_answers')
                    ->join('study_sessions as today_review_sessions', 'today_review_sessions.id', '=', 'today_review_answers.study_session_id')
                    ->whereColumn('today_review_answers.question_id', 'review_memory_progress.question_id')
                    ->where('today_review_sessions.user_id', $user->getKey())
                    ->where('today_review_sessions.mode', StudySessionManager::MODE_SR_REVIEW)
                    ->whereDate('today_review_answers.answered_at', $today);
            })
            ->when($categoryId, fn ($query) => $query->where('questions.license_category_id', $categoryId))
            ->when($allowedCategoryIds !== [], fn ($query) => $query->whereIn('questions.license_category_id', $allowedCategoryIds))
            ->get()
            ->map(fn (ReviewMemoryProgress $progress): UserQuestionProgress => $this->memoryOnlyProgressRow($progress));
    }

    protected function memoryOnlyProgressRow(ReviewMemoryProgress $memoryProgress): UserQuestionProgress
    {
        $progress = new UserQuestionProgress;
        $progress->forceFill([
            'user_id' => (int) $memoryProgress->user_id,
            'question_id' => (int) $memoryProgress->question_id,
            'next_review_at' => $memoryProgress->next_verified_review_at,
            'last_answered_at' => $memoryProgress->last_verified_at,
            'correct_count' => (int) ($memoryProgress->verified_correct_count ?? 0),
            'incorrect_count' => (int) ($memoryProgress->verified_unknown_count ?? 0)
                + (int) ($memoryProgress->verified_incorrect_count ?? 0),
            'correct_streak' => (int) ($memoryProgress->verified_correct_streak ?? 0),
            'last_quality' => $memoryProgress->last_verified_result === ReviewMemoryProgress::RESULT_CORRECT ? 5 : 1,
            'repetitions' => (int) ($memoryProgress->verified_attempts_count ?? 0),
            'total_attempts' => (int) ($memoryProgress->verified_attempts_count ?? 0),
        ]);

        $progress->setAttribute('question_license_category_id', (int) $memoryProgress->license_category_id);
        $progress->setAttribute('question_difficulty', (int) ($memoryProgress->getAttribute('question_difficulty') ?? 1));
        $progress->setAttribute('review_memory_progress_id', (int) $memoryProgress->getKey());
        $progress->setAttribute('review_verified_attempts_count', (int) ($memoryProgress->verified_attempts_count ?? 0));
        $progress->setAttribute('review_verified_correct_count', (int) ($memoryProgress->verified_correct_count ?? 0));
        $progress->setAttribute('review_verified_unknown_count', (int) ($memoryProgress->verified_unknown_count ?? 0));
        $progress->setAttribute('review_verified_incorrect_count', (int) ($memoryProgress->verified_incorrect_count ?? 0));
        $progress->setAttribute('review_verified_correct_streak', (int) ($memoryProgress->verified_correct_streak ?? 0));
        $progress->setAttribute('review_last_verified_result', $memoryProgress->last_verified_result);
        $progress->setAttribute('review_last_verified_at', $memoryProgress->last_verified_at);
        $progress->setAttribute('review_last_study_session_answer_id', $memoryProgress->last_study_session_answer_id);
        $progress->setAttribute('review_next_verified_review_at', $memoryProgress->next_verified_review_at);
        $progress->setAttribute('review_verified_memory_state', $memoryProgress->verified_memory_state);
        $progress->setAttribute('review_source_policy_version', $memoryProgress->source_policy_version);

        return $progress;
    }

    protected function compareProgress(UserQuestionProgress $left, UserQuestionProgress $right, Carbon $today): int
    {
        $leftScore = $this->priorityScore($left, $today);
        $rightScore = $this->priorityScore($right, $today);

        if ($leftScore !== $rightScore) {
            return $rightScore <=> $leftScore;
        }

        $leftReviewAt = $this->dateTimestamp($left->next_review_at);
        $rightReviewAt = $this->dateTimestamp($right->next_review_at);

        if ($leftReviewAt !== $rightReviewAt) {
            return $leftReviewAt <=> $rightReviewAt;
        }

        $leftLastAnsweredAt = $this->dateTimestamp($left->last_answered_at);
        $rightLastAnsweredAt = $this->dateTimestamp($right->last_answered_at);

        if ($leftLastAnsweredAt !== $rightLastAnsweredAt) {
            return $leftLastAnsweredAt <=> $rightLastAnsweredAt;
        }

        return (int) $left->question_id <=> (int) $right->question_id;
    }

    /**
     * @return array{progress: UserQuestionProgress, score: int, next_review_at: int, last_answered_at: int, question_id: int}
     */
    protected function priorityRow(UserQuestionProgress $progress, Carbon $today): array
    {
        return [
            'progress' => $progress,
            'score' => $this->priorityScore($progress, $today),
            'next_review_at' => $this->dateTimestamp($progress->next_review_at),
            'last_answered_at' => $this->dateTimestamp($progress->last_answered_at),
            'question_id' => (int) $progress->question_id,
        ];
    }

    /**
     * @param  array{progress: UserQuestionProgress, score: int, next_review_at: int, last_answered_at: int, question_id: int}  $left
     * @param  array{progress: UserQuestionProgress, score: int, next_review_at: int, last_answered_at: int, question_id: int}  $right
     */
    protected function comparePriorityRows(array $left, array $right): int
    {
        if ($left['score'] !== $right['score']) {
            return $right['score'] <=> $left['score'];
        }

        if ($left['next_review_at'] !== $right['next_review_at']) {
            return $left['next_review_at'] <=> $right['next_review_at'];
        }

        if ($left['last_answered_at'] !== $right['last_answered_at']) {
            return $left['last_answered_at'] <=> $right['last_answered_at'];
        }

        return $left['question_id'] <=> $right['question_id'];
    }

    protected function priorityScore(UserQuestionProgress $progress, Carbon $today): int
    {
        $nextReviewAt = $progress->next_review_at instanceof Carbon
            ? $progress->next_review_at->copy()
            : Carbon::parse($progress->next_review_at);

        $overdueDays = max(0, (int) $nextReviewAt->startOfDay()->diffInDays($today, false));
        $incorrectCount = (int) ($progress->incorrect_count ?? 0);
        $correctStreak = (int) ($progress->correct_streak ?? 0);
        $lastQuality = (int) ($progress->last_quality ?? 0);
        $difficulty = (int) ($progress->getAttribute('question_difficulty') ?? 1);
        $stabilityDebt = max(0, 3 - $correctStreak);
        $qualityDebt = max(0, 5 - $lastQuality);

        return $this->primaryPriorityScore($progress, $today)
            + $this->verifiedPriorityScore($progress, $today)
            + ($overdueDays * 1000)
            + ($incorrectCount * 120)
            + ($stabilityDebt * 45)
            + ($qualityDebt * 12)
            + ($difficulty * 8);
    }

    /**
     * @param  Collection<int, UserQuestionProgress>  $progressRows
     * @return array{
     *     segment_counts: array{overdue: int, risky: int, reinforce: int},
     *     memory_state_counts: array{new: int, learning: int, review: int, relearning: int, mastered: int, leech: int}
     * }
     */
    protected function signalSummary(Collection $progressRows): array
    {
        $segmentCounts = [
            ReviewMemorySignalService::SEGMENT_OVERDUE => 0,
            ReviewMemorySignalService::SEGMENT_RISKY => 0,
            ReviewMemorySignalService::SEGMENT_REINFORCE => 0,
        ];
        $memoryStateCounts = [
            ReviewMemorySignalService::STATE_NEW => 0,
            ReviewMemorySignalService::STATE_LEARNING => 0,
            ReviewMemorySignalService::STATE_REVIEW => 0,
            ReviewMemorySignalService::STATE_RELEARNING => 0,
            ReviewMemorySignalService::STATE_MASTERED => 0,
            ReviewMemorySignalService::STATE_LEECH => 0,
        ];
        $today = today();

        $progressRows->each(function (UserQuestionProgress $progress) use (&$memoryStateCounts, &$segmentCounts, $today): void {
            if ($this->hasVerifiedMemoryProgress($progress)) {
                $signal = $this->verifiedMemorySignalService->signal(
                    $this->verifiedMemoryProgressForRow($progress),
                    $today,
                );
                $segment = $this->legacySignalMapper->segment($signal);
                $memoryState = $this->legacySignalMapper->memoryState($signal);
            } else {
                $signal = $this->memorySignalService->signal($progress, $today);
                $segment = (string) $signal['plan_segment'];
                $memoryState = (string) $signal['memory_state'];
            }

            if (array_key_exists($segment, $segmentCounts)) {
                $segmentCounts[$segment]++;
            }

            if (array_key_exists($memoryState, $memoryStateCounts)) {
                $memoryStateCounts[$memoryState]++;
            }
        });

        return [
            'segment_counts' => $segmentCounts,
            'memory_state_counts' => $memoryStateCounts,
        ];
    }

    /**
     * @param  array{
     *     segment_counts: array{overdue: int, risky: int, reinforce: int},
     *     memory_state_counts: array{new: int, learning: int, review: int, relearning: int, mastered: int, leech: int}
     * }  $signalSummary
     * @return array{version: string, tone: string, headline: string, message: string, primary_action_label: string, supporting_label: string}
     */
    protected function coach(
        int $dueCount,
        int $recommendedQuestionCount,
        string $estimatedDurationLabel,
        array $signalSummary,
    ): array {
        $segmentCounts = $signalSummary['segment_counts'];
        $memoryStateCounts = $signalSummary['memory_state_counts'];
        $recoveryCount = $memoryStateCounts[ReviewMemorySignalService::STATE_LEECH]
            + $memoryStateCounts[ReviewMemorySignalService::STATE_RELEARNING];

        if ($recommendedQuestionCount <= 0) {
            return [
                'version' => self::COACH_VERSION,
                'tone' => 'empty',
                'headline' => 'Dzisiaj pamięć jest domknięta',
                'message' => 'Na teraz nie ma pytań w planie pamięci. Gdy system zaplanuje kolejny trening, ten ekran sam pokaże nowy zestaw.',
                'primary_action_label' => 'Wróć do nauki',
                'supporting_label' => 'Brak pytań zaplanowanych na teraz.',
            ];
        }

        $supportingLabel = "{$recommendedQuestionCount} pytań / około {$estimatedDurationLabel}";

        if ($recoveryCount > 0 || $segmentCounts[ReviewMemorySignalService::SEGMENT_RISKY] > 0) {
            return [
                'version' => self::COACH_VERSION,
                'tone' => 'recovery',
                'headline' => 'Najpierw odzyskujemy chwiejne pytania',
                'message' => "W tym zestawie: {$recoveryCount} do szybkiego powrotu. Zaczynamy od nich, potem domykamy utrwalenie.",
                'primary_action_label' => 'Zacznij od odzyskania',
                'supporting_label' => $supportingLabel,
            ];
        }

        if ($segmentCounts[ReviewMemorySignalService::SEGMENT_OVERDUE] > 0) {
            return [
                'version' => self::COACH_VERSION,
                'tone' => 'overdue',
                'headline' => 'Zdejmujemy zaległości',
                'message' => "Po terminie: {$segmentCounts[ReviewMemorySignalService::SEGMENT_OVERDUE]}. Krótka sesja wystarczy, żeby kolejka wróciła pod kontrolę.",
                'primary_action_label' => 'Rozpocznij powtórki',
                'supporting_label' => $supportingLabel,
            ];
        }

        return [
            'version' => self::COACH_VERSION,
            'tone' => 'steady',
            'headline' => 'Utrwalamy spokojny zestaw',
            'message' => 'To krótki trening wzmacniający pamięć. Nie zarządzasz kolejką ręcznie, system prowadzi Cię przez najważniejsze pytania.',
            'primary_action_label' => 'Rozpocznij trening pamięci',
            'supporting_label' => $supportingLabel,
        ];
    }

    protected function dateTimestamp(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if ($value instanceof Carbon) {
            return $value->getTimestamp();
        }

        return Carbon::parse($value)->getTimestamp();
    }

    protected function estimatedDurationSeconds(User $user, ?int $categoryId, int $questionCount): int
    {
        if ($questionCount <= 0) {
            return 0;
        }

        $averageMs = StudySessionAnswer::query()
            ->join('study_sessions', 'study_sessions.id', '=', 'study_session_answers.study_session_id')
            ->where('study_sessions.user_id', $user->getKey())
            ->where('study_sessions.mode', StudySessionManager::MODE_SR_REVIEW)
            ->when($categoryId, fn ($query) => $query->where('study_sessions.license_category_id', $categoryId))
            ->whereNotNull('study_session_answers.response_time_ms')
            ->whereBetween('study_session_answers.response_time_ms', [500, 120000])
            ->avg('study_session_answers.response_time_ms');

        if ($averageMs === null) {
            return $questionCount * self::FALLBACK_SECONDS_PER_QUESTION;
        }

        $secondsPerQuestion = (int) round(((float) $averageMs / 1000) + 20);
        $secondsPerQuestion = max(30, min(90, $secondsPerQuestion));

        return $questionCount * $secondsPerQuestion;
    }

    protected function durationLabel(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 min';
        }

        $minutes = (int) ceil($seconds / 60);

        return "{$minutes} min";
    }

    protected function verifiedPriorityScore(UserQuestionProgress $progress, Carbon $today): int
    {
        if (! $this->hasVerifiedMemoryProgress($progress)) {
            return 0;
        }

        $signal = $this->verifiedMemorySignalService->signal(
            $this->verifiedMemoryProgressForRow($progress),
            $today,
        );
        $segment = (string) $signal['plan_segment'];
        $baseScore = match ($segment) {
            ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY => 5000,
            ReviewMemoryVerifiedSignalService::SEGMENT_DUE => 2500,
            default => 500,
        };

        return $baseScore
            + ((int) $signal['recovery_score'] * 20)
            + ((int) $signal['leech_score'] * 15)
            + ((int) $signal['overdue_days'] * 100);
    }

    protected function primaryPriorityScore(UserQuestionProgress $progress, Carbon $today): int
    {
        return $this->isPrimaryProgress($progress, $today) ? 2000 : 0;
    }

    protected function isPrimaryProgress(UserQuestionProgress $progress, Carbon $today): bool
    {
        return $this->isClassicDueProgress($progress, $today)
            || $this->hasVerifiedActionableProgress($progress, $today);
    }

    protected function isClassicDueProgress(UserQuestionProgress $progress, Carbon $today): bool
    {
        $nextReviewAt = $progress->next_review_at;

        if ($nextReviewAt === null || $nextReviewAt === '') {
            return false;
        }

        $reviewAt = $nextReviewAt instanceof Carbon
            ? $nextReviewAt->copy()
            : Carbon::parse($nextReviewAt);

        return $reviewAt->startOfDay()->lte($today->copy()->startOfDay());
    }

    protected function hasVerifiedActionableProgress(UserQuestionProgress $progress, Carbon $today): bool
    {
        if (! $this->hasVerifiedMemoryProgress($progress)) {
            return false;
        }

        $signal = $this->verifiedMemorySignalService->signal(
            $this->verifiedMemoryProgressForRow($progress),
            $today,
        );

        return in_array((string) $signal['plan_segment'], [
            ReviewMemoryVerifiedSignalService::SEGMENT_RECOVERY,
            ReviewMemoryVerifiedSignalService::SEGMENT_DUE,
        ], true);
    }

    protected function hasVerifiedMemoryProgress(UserQuestionProgress $progress): bool
    {
        return (int) ($progress->getAttribute('review_memory_progress_id') ?? 0) > 0;
    }

    protected function verifiedMemoryProgressForRow(UserQuestionProgress $progress): ReviewMemoryProgress
    {
        $verifiedProgress = new ReviewMemoryProgress;
        $verifiedProgress->forceFill([
            'id' => (int) $progress->getAttribute('review_memory_progress_id'),
            'user_id' => (int) $progress->user_id,
            'question_id' => (int) $progress->question_id,
            'license_category_id' => (int) ($progress->getAttribute('question_license_category_id') ?? 0),
            'verified_attempts_count' => (int) ($progress->getAttribute('review_verified_attempts_count') ?? 0),
            'verified_correct_count' => (int) ($progress->getAttribute('review_verified_correct_count') ?? 0),
            'verified_unknown_count' => (int) ($progress->getAttribute('review_verified_unknown_count') ?? 0),
            'verified_incorrect_count' => (int) ($progress->getAttribute('review_verified_incorrect_count') ?? 0),
            'verified_correct_streak' => (int) ($progress->getAttribute('review_verified_correct_streak') ?? 0),
            'last_verified_result' => $progress->getAttribute('review_last_verified_result'),
            'last_verified_at' => $progress->getAttribute('review_last_verified_at'),
            'last_study_session_answer_id' => $progress->getAttribute('review_last_study_session_answer_id'),
            'next_verified_review_at' => $progress->getAttribute('review_next_verified_review_at'),
            'verified_memory_state' => (string) ($progress->getAttribute('review_verified_memory_state') ?? ReviewMemoryProgress::STATE_NEW),
            'source_policy_version' => (string) ($progress->getAttribute('review_source_policy_version') ?? ReviewMemoryProgressService::VERSION),
        ]);

        return $verifiedProgress;
    }
}
