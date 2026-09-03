<?php

namespace App\Support;

use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserIncorrectQuestion;
use App\Models\UserProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IncorrectQuestionListService
{
    public function recordAnswer(
        int $userId,
        Question $question,
        StudySession $studySession,
        StudySessionAnswer $answer,
        bool $isCorrect,
        CarbonInterface $answeredAt,
    ): void {
        if (! config('study.incorrect_question_list.write_enabled', false)) {
            return;
        }

        if (! $question->is_active || $question->hasDeliveryIssue()) {
            return;
        }

        if ($isCorrect) {
            $this->removeAfterCorrectAnswer($userId, $question, $answeredAt);

            return;
        }

        $now = now();

        DB::table('user_incorrect_questions')->upsert(
            [[
                'user_id' => $userId,
                'question_id' => $question->getKey(),
                'first_incorrect_at' => $answeredAt,
                'last_incorrect_at' => $answeredAt,
                'removed_at' => null,
                'removal_reason' => null,
                'latest_study_session_id' => $studySession->getKey(),
                'latest_answer_id' => $answer->getKey(),
                'created_source' => UserIncorrectQuestion::SOURCE_ANSWER,
                'backfill_batch_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['user_id', 'question_id'],
            [
                'last_incorrect_at',
                'removed_at',
                'removal_reason',
                'latest_study_session_id',
                'latest_answer_id',
                'created_source',
                'backfill_batch_id',
                'updated_at',
            ],
        );
    }

    public function activeCountForCategory(int $userId, int $categoryId): int
    {
        return UserIncorrectQuestion::query()
            ->active()
            ->where('user_id', $userId)
            ->whereHas('question', fn (Builder $query): Builder => $query
                ->where('license_category_id', $categoryId)
                ->where('is_active', true)
                ->readyForDelivery())
            ->count();
    }

    public function removeManually(User $user, UserIncorrectQuestion $incorrectQuestion): bool
    {
        if ((int) $incorrectQuestion->user_id !== (int) $user->getKey()) {
            return false;
        }

        return $incorrectQuestion->newQuery()
            ->whereKey($incorrectQuestion->getKey())
            ->where('user_id', $user->getKey())
            ->whereNull('removed_at')
            ->update([
                'removed_at' => now(),
                'removal_reason' => UserIncorrectQuestion::REMOVAL_REASON_MANUAL,
                'updated_at' => now(),
            ]) === 1;
    }

    public function applyActiveQuestionFilter(Builder $query, int $userId): Builder
    {
        return $query->whereExists(function ($subQuery) use ($userId): void {
            $subQuery
                ->selectRaw('1')
                ->from('user_incorrect_questions as active_incorrect_questions')
                ->whereColumn('active_incorrect_questions.question_id', 'questions.id')
                ->where('active_incorrect_questions.user_id', $userId)
                ->whereNull('active_incorrect_questions.removed_at');
        });
    }

    protected function removeAfterCorrectAnswer(
        int $userId,
        Question $question,
        CarbonInterface $answeredAt,
    ): void {
        $autoRemove = (bool) UserProfile::query()
            ->whereKey($userId)
            ->value('auto_remove_incorrect_questions_on_correct');

        if (! $autoRemove) {
            return;
        }

        UserIncorrectQuestion::query()
            ->where('user_id', $userId)
            ->where('question_id', $question->getKey())
            ->whereNull('removed_at')
            ->update([
                'removed_at' => $answeredAt,
                'removal_reason' => UserIncorrectQuestion::REMOVAL_REASON_CORRECT_ANSWER,
                'updated_at' => now(),
            ]);
    }
}
