<?php

namespace App\Support;

use App\Models\ReviewTrainerEvent;
use App\Models\StudySession;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReviewTrainerEventLogger
{
    public function __construct(
        protected ReviewTrainerDailyPlanService $dailyPlanService,
    ) {}

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<int, int>  $selectedQuestionIds
     */
    public function sessionStarted(StudySession $session, array $plan, array $selectedQuestionIds): ?ReviewTrainerEvent
    {
        if (! $this->eventTableExists()) {
            return null;
        }

        return ReviewTrainerEvent::query()->firstOrCreate([
            'study_session_id' => $session->getKey(),
            'event_name' => 'review.session_started',
        ], [
            'event_id' => (string) Str::uuid(),
            'user_id' => $session->user_id,
            'license_category_id' => $session->license_category_id,
            'planner_version' => (string) ($plan['planner_version'] ?? ReviewPlannerService::VERSION),
            'payload' => [
                'daily_plan_policy_version' => (string) ($plan['daily_plan_policy_version'] ?? ReviewTrainerDailyPlanService::VERSION),
                'review_day' => (string) ($plan['review_day'] ?? $this->dailyPlanService->reviewDay($session->started_at)),
                'daily_target_count' => (int) ($plan['daily_target_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
                'minimum_session_question_count' => (int) ($plan['minimum_session_question_count'] ?? ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT),
                'completed_today_count' => (int) ($plan['completed_today_count'] ?? 0),
                'daily_remaining_count' => (int) ($plan['daily_remaining_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
                'due_count' => (int) ($plan['due_count'] ?? 0),
                'candidate_count' => (int) ($plan['candidate_count'] ?? $plan['due_count'] ?? 0),
                'booster_count' => (int) ($plan['booster_count'] ?? 0),
                'new_candidate_count' => (int) ($plan['new_candidate_count'] ?? 0),
                'candidate_source_counts' => $this->candidateSourceCounts($plan),
                'recommended_question_count' => (int) ($plan['recommended_question_count'] ?? 0),
                'selected_question_count' => count($selectedQuestionIds),
                'selected_question_ids' => array_values(array_map('intval', $selectedQuestionIds)),
                'estimated_duration_seconds' => (int) ($plan['estimated_duration_seconds'] ?? 0),
            ],
            'occurred_at' => now(),
        ]);
    }

    public function sessionCompleted(StudySession $session): ?ReviewTrainerEvent
    {
        if (! $this->eventTableExists()) {
            return null;
        }

        $session->loadMissing('answers');

        $payload = is_array($session->payload) ? $session->payload : [];
        $reviewPlan = is_array($payload['review_plan'] ?? null) ? $payload['review_plan'] : [];
        $completedAt = $session->completed_at ?? now();
        $startedAt = $session->started_at;
        $answeredCount = $session->answers->count();
        $totalQuestionsCount = (int) $session->total_questions_count;
        $completedTodayCount = $session->user
            ? $this->dailyPlanService->completedTodayCount($session->user, $session->license_category_id, [], $completedAt)
            : null;
        $completionType = $totalQuestionsCount > 0 && $answeredCount >= $totalQuestionsCount
            ? 'auto_full'
            : 'manual_partial';

        return ReviewTrainerEvent::query()->firstOrCreate([
            'study_session_id' => $session->getKey(),
            'event_name' => 'review.completed',
        ], [
            'event_id' => (string) Str::uuid(),
            'user_id' => $session->user_id,
            'license_category_id' => $session->license_category_id,
            'planner_version' => (string) ($reviewPlan['planner_version'] ?? ReviewPlannerService::VERSION),
            'payload' => [
                'daily_plan_policy_version' => (string) ($reviewPlan['daily_plan_policy_version'] ?? ReviewTrainerDailyPlanService::VERSION),
                'started_review_day' => (string) ($reviewPlan['review_day'] ?? $this->dailyPlanService->reviewDay($startedAt)),
                'completed_review_day' => $this->dailyPlanService->reviewDay($completedAt),
                'daily_target_count' => (int) ($reviewPlan['daily_target_count'] ?? ReviewTrainerDailyPlanService::DAILY_TARGET_COUNT),
                'minimum_session_question_count' => (int) ($reviewPlan['minimum_session_question_count'] ?? ReviewTrainerDailyPlanService::MINIMUM_SESSION_QUESTION_COUNT),
                'completed_today_count_at_start' => (int) ($reviewPlan['completed_today_count'] ?? 0),
                'completed_today_count_after_session' => $completedTodayCount,
                'due_count' => (int) ($reviewPlan['due_count'] ?? 0),
                'candidate_count' => (int) ($reviewPlan['candidate_count'] ?? $reviewPlan['due_count'] ?? 0),
                'booster_count' => (int) ($reviewPlan['booster_count'] ?? 0),
                'new_candidate_count' => (int) ($reviewPlan['new_candidate_count'] ?? 0),
                'candidate_source_counts' => $this->candidateSourceCounts($reviewPlan),
                'recommended_question_count' => (int) ($reviewPlan['recommended_question_count'] ?? 0),
                'selected_question_count' => (int) ($reviewPlan['selected_question_count'] ?? $session->total_questions_count),
                'completion_type' => $completionType,
                'answered_count' => $answeredCount,
                'correct_answers_count' => (int) $session->correct_answers_count,
                'total_questions_count' => $totalQuestionsCount,
                'score_percent' => (float) $session->score_percent,
                'duration_seconds' => $startedAt ? (int) $startedAt->diffInSeconds($completedAt, false) : null,
            ],
            'occurred_at' => $completedAt,
        ]);
    }

    public function sessionReplaced(
        StudySession $session,
        string $replacementMode,
        ?int $replacementLicenseCategoryId = null,
        ?CarbonInterface $occurredAt = null,
        string $replacementReason = 'new_session_started',
    ): ?ReviewTrainerEvent {
        if (! $this->eventTableExists() || $session->mode !== StudySessionManager::MODE_SR_REVIEW) {
            return null;
        }

        $payload = is_array($session->payload) ? $session->payload : [];
        $reviewPlan = is_array($payload['review_plan'] ?? null) ? $payload['review_plan'] : [];
        $resolvedOccurredAt = $occurredAt ? Carbon::parse($occurredAt) : now();

        return ReviewTrainerEvent::query()->firstOrCreate([
            'study_session_id' => $session->getKey(),
            'event_name' => 'review.replaced',
        ], [
            'event_id' => (string) Str::uuid(),
            'user_id' => $session->user_id,
            'license_category_id' => $session->license_category_id,
            'planner_version' => (string) ($reviewPlan['planner_version'] ?? ReviewPlannerService::VERSION),
            'payload' => [
                'daily_plan_policy_version' => (string) ($reviewPlan['daily_plan_policy_version'] ?? ReviewTrainerDailyPlanService::VERSION),
                'started_review_day' => (string) ($reviewPlan['review_day'] ?? $this->dailyPlanService->reviewDay($session->started_at)),
                'replacement_review_day' => $this->dailyPlanService->reviewDay($resolvedOccurredAt),
                'replacement_mode' => $replacementMode,
                'replacement_license_category_id' => $replacementLicenseCategoryId,
                'replacement_reason' => $replacementReason,
                'answered_count' => $session->relationLoaded('answers')
                    ? $session->answers->count()
                    : (int) $session->answers()->count(),
                'total_questions_count' => (int) $session->total_questions_count,
                'selected_question_count' => (int) ($reviewPlan['selected_question_count'] ?? $session->total_questions_count),
            ],
            'occurred_at' => $resolvedOccurredAt,
        ]);
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array{primary: int, seen_booster: int, new_candidate: int}
     */
    protected function candidateSourceCounts(array $plan): array
    {
        $sourceCounts = is_array($plan['candidate_source_counts'] ?? null)
            ? $plan['candidate_source_counts']
            : [];

        return [
            'primary' => (int) ($sourceCounts['primary'] ?? $plan['due_count'] ?? 0),
            'seen_booster' => (int) ($sourceCounts['seen_booster'] ?? $plan['booster_count'] ?? 0),
            'new_candidate' => (int) ($sourceCounts['new_candidate'] ?? $plan['new_candidate_count'] ?? 0),
        ];
    }

    protected function eventTableExists(): bool
    {
        return Schema::hasTable((new ReviewTrainerEvent)->getTable());
    }
}
