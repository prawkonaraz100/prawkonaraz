<?php

namespace App\Support;

use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewMemoryProgressService
{
    public const VERSION = 'review-memory-progress-v1';

    public function recordAnswer(
        StudySession $studySession,
        StudySessionAnswer $answer,
        Question $question,
    ): ?ReviewMemoryProgress {
        if ($studySession->mode !== StudySessionManager::MODE_SR_REVIEW || ! $answer->getKey()) {
            return null;
        }

        return DB::transaction(function () use ($answer, $question, $studySession): ReviewMemoryProgress {
            $progress = ReviewMemoryProgress::query()
                ->where('user_id', $studySession->user_id)
                ->where('question_id', $question->getKey())
                ->lockForUpdate()
                ->first();

            if (! $progress) {
                $progress = new ReviewMemoryProgress([
                    'user_id' => $studySession->user_id,
                    'question_id' => $question->getKey(),
                    'license_category_id' => $studySession->license_category_id ?? $question->license_category_id,
                    'verified_attempts_count' => 0,
                    'verified_correct_count' => 0,
                    'verified_unknown_count' => 0,
                    'verified_incorrect_count' => 0,
                    'verified_correct_streak' => 0,
                    'verified_memory_state' => ReviewMemoryProgress::STATE_NEW,
                ]);
            }

            if ((int) $progress->last_study_session_answer_id === (int) $answer->getKey()) {
                return $progress;
            }

            $answeredAt = $answer->answered_at instanceof Carbon
                ? $answer->answered_at->copy()
                : now();
            $answerKind = $answer->answer_kind ?: StudySessionAnswerKind::CHOICE;
            $result = $this->resultFor($answerKind, (bool) $answer->is_correct);
            $isCorrect = $result === ReviewMemoryProgress::RESULT_CORRECT;
            $correctStreak = $isCorrect
                ? (int) ($progress->verified_correct_streak ?? 0) + 1
                : 0;

            $progress->forceFill([
                'license_category_id' => $studySession->license_category_id ?? $question->license_category_id,
                'verified_attempts_count' => (int) ($progress->verified_attempts_count ?? 0) + 1,
                'verified_correct_count' => (int) ($progress->verified_correct_count ?? 0) + ($isCorrect ? 1 : 0),
                'verified_unknown_count' => (int) ($progress->verified_unknown_count ?? 0) + ($result === ReviewMemoryProgress::RESULT_UNKNOWN ? 1 : 0),
                'verified_incorrect_count' => (int) ($progress->verified_incorrect_count ?? 0) + ($result === ReviewMemoryProgress::RESULT_INCORRECT ? 1 : 0),
                'verified_correct_streak' => $correctStreak,
                'last_verified_result' => $result,
                'last_verified_at' => $answeredAt,
                'last_study_session_answer_id' => $answer->getKey(),
                'next_verified_review_at' => $this->nextReviewDate($result, $correctStreak, $answeredAt),
                'verified_memory_state' => $this->memoryState($result, $correctStreak),
                'source_policy_version' => self::VERSION,
            ])->save();

            return $progress;
        });
    }

    protected function resultFor(string $answerKind, bool $isCorrect): string
    {
        return match ($answerKind) {
            StudySessionAnswerKind::UNKNOWN => ReviewMemoryProgress::RESULT_UNKNOWN,
            StudySessionAnswerKind::SKIPPED => ReviewMemoryProgress::RESULT_SKIPPED,
            default => $isCorrect
                ? ReviewMemoryProgress::RESULT_CORRECT
                : ReviewMemoryProgress::RESULT_INCORRECT,
        };
    }

    protected function nextReviewDate(string $result, int $correctStreak, Carbon $answeredAt): string
    {
        if ($result !== ReviewMemoryProgress::RESULT_CORRECT) {
            return $answeredAt->toDateString();
        }

        $intervalDays = match (true) {
            $correctStreak <= 1 => 1,
            $correctStreak === 2 => 3,
            default => 7,
        };

        return $answeredAt->copy()->addDays($intervalDays)->toDateString();
    }

    protected function memoryState(string $result, int $correctStreak): string
    {
        if ($result !== ReviewMemoryProgress::RESULT_CORRECT) {
            return ReviewMemoryProgress::STATE_NEEDS_RECOVERY;
        }

        return $correctStreak >= 3
            ? ReviewMemoryProgress::STATE_VERIFIED_MEMORY
            : ReviewMemoryProgress::STATE_REVIEW;
    }
}
