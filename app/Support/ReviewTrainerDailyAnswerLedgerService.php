<?php

namespace App\Support;

use App\Models\Question;
use App\Models\ReviewTrainerDailyAnswer;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ReviewTrainerDailyAnswerLedgerService
{
    public function __construct(
        protected ReviewTrainerDailyPlanService $dailyPlanService,
    ) {}

    public function recordAnswer(
        StudySession $studySession,
        StudySessionAnswer $answer,
        Question $question,
    ): ?ReviewTrainerDailyAnswer {
        if (
            $studySession->mode !== StudySessionManager::MODE_SR_REVIEW
            || ! $answer->getKey()
            || ! $this->tableExists()
        ) {
            return null;
        }

        $answeredAt = $answer->answered_at instanceof Carbon
            ? $answer->answered_at->copy()
            : now();

        return ReviewTrainerDailyAnswer::query()->firstOrCreate([
            'study_session_answer_id' => $answer->getKey(),
        ], [
            'user_id' => $studySession->user_id,
            'license_category_id' => $studySession->license_category_id ?? $question->license_category_id,
            'question_id' => $question->getKey(),
            'study_session_id' => $studySession->getKey(),
            'review_day' => $this->dailyPlanService->reviewDay($answeredAt),
            'daily_plan_policy_version' => ReviewTrainerDailyPlanService::VERSION,
            'answer_kind' => (string) ($answer->answer_kind ?: StudySessionAnswerKind::CHOICE),
            'is_correct' => (bool) $answer->is_correct,
            'answered_at' => $answeredAt,
        ]);
    }

    protected function tableExists(): bool
    {
        return Schema::hasTable((new ReviewTrainerDailyAnswer)->getTable());
    }
}
