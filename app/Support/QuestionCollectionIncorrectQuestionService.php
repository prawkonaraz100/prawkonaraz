<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionCollection;
use App\Models\QuestionCollectionIncorrectQuestion;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuestionCollectionIncorrectQuestionService
{
    public function recordAnswer(
        StudySession $studySession,
        Question $question,
        StudySessionAnswer $answer,
        bool $isCorrect,
        CarbonInterface $answeredAt,
    ): void {
        $collectionId = $studySession->question_collection_id;

        if (! $collectionId) {
            return;
        }

        if ($isCorrect) {
            $this->removeAfterCorrectAnswer(
                (int) $studySession->user_id,
                (int) $collectionId,
                $question,
                $answeredAt,
            );

            return;
        }

        DB::transaction(function () use ($answer, $answeredAt, $collectionId, $question, $studySession): void {
            $entry = QuestionCollectionIncorrectQuestion::query()
                ->where('user_id', $studySession->user_id)
                ->where('question_collection_id', $collectionId)
                ->where('question_id', $question->getKey())
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                QuestionCollectionIncorrectQuestion::query()->create([
                    'user_id' => $studySession->user_id,
                    'question_collection_id' => $collectionId,
                    'question_id' => $question->getKey(),
                    'incorrect_count' => 1,
                    'first_incorrect_at' => $answeredAt,
                    'last_incorrect_at' => $answeredAt,
                    'latest_study_session_id' => $studySession->getKey(),
                    'latest_answer_id' => $answer->getKey(),
                ]);

                return;
            }

            $entry->forceFill([
                'incorrect_count' => (int) $entry->incorrect_count + 1,
                'last_incorrect_at' => $answeredAt,
                'removed_at' => null,
                'removal_reason' => null,
                'latest_study_session_id' => $studySession->getKey(),
                'latest_answer_id' => $answer->getKey(),
            ])->save();
        });
    }

    public function activeCountFor(User $user, QuestionCollection $collection): int
    {
        return $this->activeEntriesQuery($user, $collection)->count();
    }

    /**
     * @return Collection<int, int>
     */
    public function activeQuestionIdsFor(User $user, QuestionCollection $collection): Collection
    {
        return $this->activeEntriesQuery($user, $collection)
            ->orderByDesc('last_incorrect_at')
            ->orderByDesc('id')
            ->pluck('question_id')
            ->map(fn (mixed $questionId): int => (int) $questionId)
            ->values();
    }

    /**
     * @return Builder<QuestionCollectionIncorrectQuestion>
     */
    public function activeEntriesQuery(User $user, QuestionCollection $collection): Builder
    {
        return QuestionCollectionIncorrectQuestion::query()
            ->where('user_id', $user->getKey())
            ->where('question_collection_id', $collection->getKey())
            ->active()
            ->whereHas('question.modules', fn (Builder $query): Builder => $query
                ->where('question_collection_id', $collection->getKey())
                ->where('is_active', true));
    }

    public function removeManually(User $user, QuestionCollectionIncorrectQuestion $entry): bool
    {
        if ((int) $entry->user_id !== (int) $user->getKey()) {
            return false;
        }

        return QuestionCollectionIncorrectQuestion::query()
            ->whereKey($entry->getKey())
            ->where('user_id', $user->getKey())
            ->whereNull('removed_at')
            ->update([
                'removed_at' => now(),
                'removal_reason' => QuestionCollectionIncorrectQuestion::REMOVAL_REASON_MANUAL,
                'updated_at' => now(),
            ]) === 1;
    }

    protected function removeAfterCorrectAnswer(
        int $userId,
        int $collectionId,
        Question $question,
        CarbonInterface $answeredAt,
    ): void {
        $autoRemove = (bool) UserProfile::query()
            ->whereKey($userId)
            ->value('auto_remove_incorrect_questions_on_correct');

        if (! $autoRemove) {
            return;
        }

        QuestionCollectionIncorrectQuestion::query()
            ->where('user_id', $userId)
            ->where('question_collection_id', $collectionId)
            ->where('question_id', $question->getKey())
            ->active()
            ->update([
                'removed_at' => $answeredAt,
                'removal_reason' => QuestionCollectionIncorrectQuestion::REMOVAL_REASON_CORRECT_ANSWER,
                'updated_at' => now(),
            ]);
    }
}
