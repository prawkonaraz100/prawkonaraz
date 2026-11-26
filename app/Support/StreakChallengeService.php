<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StreakRecord;
use App\Models\StreakRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StreakChallengeService
{
    private const INACTIVITY_SECONDS = 300;

    public function __construct(
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected UserAvatarService $userAvatarService,
        protected QuestionAudioPayloadBuilder $questionAudioPayloadBuilder,
        protected QuestionExplanationSignReferencePayloadBuilder $questionExplanationSignReferencePayloadBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $user, LicenseCategory $category): array
    {
        $record = StreakRecord::query()
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $category->getKey())
            ->first();
        $activeRun = StreakRun::query()
            ->with(['currentQuestion.media', 'currentQuestion.questionTopic'])
            ->where('user_id', $user->getKey())
            ->where('license_category_id', $category->getKey())
            ->where('status', StreakRun::STATUS_IN_PROGRESS)
            ->where('updated_at', '>', now()->subSeconds(self::INACTIVITY_SECONDS))
            ->latest('id')
            ->first();
        $leaders = StreakRecord::query()
            ->with('user')
            ->where('license_category_id', $category->getKey())
            ->where('best_score', '>', 0)
            ->orderByDesc('best_score')
            ->orderByRaw('CASE WHEN best_duration_seconds IS NULL THEN 1 ELSE 0 END')
            ->orderBy('best_duration_seconds')
            ->orderByRaw('CASE WHEN best_achieved_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('best_achieved_at')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->values()
            ->map(function (StreakRecord $leader, int $index) use ($user): array {
                $leaderUser = $leader->user;
                $avatar = $leaderUser ? $this->userAvatarService->payload($leaderUser) : null;

                return [
                    'position' => $index + 1,
                    'name' => $this->publicName($leaderUser?->name),
                    'score' => $leader->best_score,
                    'duration_seconds' => $leader->best_duration_seconds,
                    'avatar_url' => $avatar['url'] ?? null,
                    'initials' => $avatar['initials'] ?? 'K',
                    'is_me' => $leader->user_id === $user->getKey(),
                ];
            })
            ->all();
        $bestScore = (int) ($record?->best_score ?? 0);

        return [
            'category' => [
                'id' => $category->getKey(),
                'code' => $category->code,
                'name' => $category->name,
            ],
            'question_count' => $this->eligibleQuestions($category)->count(),
            'personal_best' => $bestScore,
            'personal_best_duration_seconds' => $record?->best_duration_seconds,
            'personal_rank' => $bestScore > 0
                ? StreakRecord::query()
                    ->where('license_category_id', $category->getKey())
                    ->where(function (Builder $query) use ($bestScore, $record): void {
                        $query->where('best_score', '>', $bestScore)
                            ->orWhere(function (Builder $query) use ($bestScore, $record): void {
                                $query->where('best_score', $bestScore);
                                $this->whereBeforeRecord($query, $record);
                            });
                    })
                    ->count() + 1
                : null,
            'attempts_count' => (int) ($record?->attempts_count ?? 0),
            'leaderboard' => $leaders,
            'active_run' => $activeRun && $activeRun->currentQuestion
                ? $this->runPayload($activeRun, $activeRun->currentQuestion)
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function start(User $user, LicenseCategory $category): array
    {
        DB::transaction(function () use ($user, $category): void {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $question = $this->eligibleQuestions($category)->inRandomOrder()->first();

            if (! $question) {
                throw ValidationException::withMessages([
                    'category_id' => 'W tej kategorii nie ma dostępnych pytań.',
                ]);
            }

            StreakRun::query()
                ->where('user_id', $user->getKey())
                ->where('status', StreakRun::STATUS_IN_PROGRESS)
                ->update([
                    'status' => StreakRun::STATUS_ABANDONED,
                    'current_question_id' => null,
                    'finished_at' => now(),
                ]);

            StreakRun::query()->create([
                'user_id' => $user->getKey(),
                'license_category_id' => $category->getKey(),
                'current_question_id' => $question->getKey(),
                'status' => StreakRun::STATUS_IN_PROGRESS,
                'score' => 0,
                'started_at' => now(),
            ]);

            $record = StreakRecord::query()->firstOrCreate(
                ['user_id' => $user->getKey(), 'license_category_id' => $category->getKey()],
                ['best_score' => 0, 'attempts_count' => 0],
            );
            $record->increment('attempts_count');
        });

        return $this->overview($user, $category);
    }

    public function leave(User $user, StreakRun $run): void
    {
        abort_unless($run->user_id === $user->getKey(), 404);
        StreakRun::query()->whereKey($run->getKey())
            ->where('status', StreakRun::STATUS_IN_PROGRESS)
            ->update([
                'status' => StreakRun::STATUS_ABANDONED,
                'current_question_id' => null,
                'finished_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(User $user, StreakRun $run, int $questionId, string $selectedAnswer, bool $recoverable = false): array
    {
        $result = DB::transaction(function () use ($user, $run, $questionId, $selectedAnswer, $recoverable): ?array {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $lockedRun = StreakRun::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            $answeredAt = now();
            if ($lockedRun->status === StreakRun::STATUS_IN_PROGRESS
                && $lockedRun->updated_at->lte($answeredAt->copy()->subSeconds(self::INACTIVITY_SECONDS))) {
                $this->leave($user, $lockedRun);

                return null;
            }

            $selectedAnswer = Str::lower($selectedAnswer);
            if ($recoverable && $lockedRun->status !== StreakRun::STATUS_ABANDONED) {
                // Replay only the most recent identical answer. Older answers or a
                // different choice remain conflicts, never a second scoring write.
                $latestAnswer = $lockedRun->answers()->orderByDesc('sequence')->first();
                if ($latestAnswer && (int) $latestAnswer->question_id === $questionId
                    && $latestAnswer->selected_answer === $selectedAnswer) {
                    return $this->answerResult(
                        $lockedRun,
                        Question::query()->findOrFail($questionId),
                        (bool) $latestAnswer->is_correct,
                        $lockedRun->currentQuestion,
                    );
                }
            }

            abort_unless($lockedRun->status === StreakRun::STATUS_IN_PROGRESS, 409, 'Ta seria została już zakończona.');
            abort_unless($lockedRun->current_question_id === $questionId, 409, 'To pytanie nie jest już aktywne.');

            $question = Question::query()->findOrFail($questionId);
            $option = match ($selectedAnswer) {
                'a' => $question->option_a,
                'b' => $question->option_b,
                'c' => $question->option_c,
                default => null,
            };

            if (! is_string($option) || trim($option) === '') {
                throw ValidationException::withMessages([
                    'selected_answer' => 'Wybierz dostępną odpowiedź.',
                ]);
            }

            $isCorrect = $selectedAnswer === Str::lower((string) $question->correct_answer);
            $lockedRun->answers()->create([
                'question_id' => $question->getKey(),
                'sequence' => $lockedRun->score + 1,
                'selected_answer' => $selectedAnswer,
                'is_correct' => $isCorrect,
                'answered_at' => $answeredAt,
            ]);

            $nextQuestion = null;

            if ($isCorrect) {
                $lockedRun->score++;
                $nextQuestion = $this->nextQuestion($lockedRun);

                if ($nextQuestion) {
                    $lockedRun->current_question_id = $nextQuestion->getKey();
                } else {
                    $lockedRun->status = StreakRun::STATUS_COMPLETED;
                    $lockedRun->current_question_id = null;
                    $lockedRun->finished_at = now();
                }
            } else {
                $lockedRun->status = StreakRun::STATUS_FAILED;
                $lockedRun->current_question_id = null;
                $lockedRun->failed_question_id = $question->getKey();
                $lockedRun->finished_at = now();
            }

            $lockedRun->save();

            $result = $this->answerResult($lockedRun, $question, $isCorrect, $nextQuestion);
            if ($result['finished']) {
                $this->saveBestResult($lockedRun, $result['run']['duration_seconds']);
            }

            return $result;
        }, 3);

        abort_if($result === null, 409, 'Seria wygasła po przerwie. Rozpocznij nową próbę.');
        if ($result['finished']) {
            $result['overview'] = $this->overview($user, $run->licenseCategory);
        }

        return $result;
    }

    /** @return array<string, mixed> */
    protected function answerResult(StreakRun $run, Question $question, bool $isCorrect, ?Question $nextQuestion): array
    {
        return [
            'is_correct' => $isCorrect,
            'finished' => $run->status !== StreakRun::STATUS_IN_PROGRESS,
            'score' => $run->score,
            'run' => $this->runPayload($run, $nextQuestion, $this->runDuration($run)),
            'correct_answer' => $isCorrect ? null : Str::upper((string) $question->correct_answer),
            'explanation' => $isCorrect ? null : $question->explanation,
            'explanation_sign_references' => $isCorrect
                ? []
                : $this->questionExplanationSignReferencePayloadBuilder->forQuestion($question),
        ];
    }

    /**
     * @return Builder<Question>
     */
    protected function eligibleQuestions(LicenseCategory $category): Builder
    {
        return Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->where(function (Builder $query): void {
                foreach (['a', 'b', 'c'] as $answer) {
                    $query->orWhere(function (Builder $optionQuery) use ($answer): void {
                        $optionQuery
                            ->where('correct_answer', $answer)
                            ->whereNotNull('option_'.$answer)
                            ->where('option_'.$answer, '!=', '');
                    });
                }
            });
    }

    protected function nextQuestion(StreakRun $run): ?Question
    {
        return $this->eligibleQuestions($run->licenseCategory)
            ->whereNotIn('questions.id', function ($query) use ($run): void {
                $query
                    ->select('question_id')
                    ->from('streak_run_answers')
                    ->where('streak_run_id', $run->getKey());
            })
            ->inRandomOrder()
            ->first();
    }

    protected function saveBestResult(StreakRun $run, ?int $duration): void
    {
        $record = StreakRecord::query()
            ->where('user_id', $run->user_id)
            ->where('license_category_id', $run->license_category_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($run->score > $record->best_score || ($run->score > 0 && $run->score === $record->best_score
            && $duration !== null && ($record->best_duration_seconds === null || $duration < $record->best_duration_seconds))) {
            $record->update([
                'best_score' => $run->score,
                'best_duration_seconds' => $duration,
                'best_run_id' => $run->getKey(),
                'best_achieved_at' => $run->finished_at,
            ]);
        }
    }

    protected function runDuration(StreakRun $run): ?int
    {
        if (! $run->finished_at || $run->status === StreakRun::STATUS_ABANDONED) {
            return null;
        }
        $lastCorrectAt = $run->answers()->where('is_correct', true)->max('answered_at');
        if ($run->score > 0 && ! $lastCorrectAt) {
            return null;
        }
        $end = $lastCorrectAt ? Carbon::parse($lastCorrectAt) : $run->finished_at;

        return (int) max(0, $run->started_at->diffInSeconds($end, false));
    }

    // Same NULL-last, ascending tie-breakers as the leaderboard, including outside its top 20.
    protected function whereBeforeRecord(Builder $query, StreakRecord $record, int $index = 0): void
    {
        $column = ['best_duration_seconds', 'best_achieved_at', 'id'][$index];
        $value = $record->getAttribute($column);
        if ($column === 'id') {
            $query->where('id', '<', $value);

            return;
        }
        $query->where(function (Builder $query) use ($column, $value, $record, $index): void {
            if ($value === null) {
                $query->whereNotNull($column);
            } else {
                $query->where($column, '<', $value);
            }
            $query->orWhere(function (Builder $query) use ($column, $value, $record, $index): void {
                $value === null ? $query->whereNull($column) : $query->where($column, $value);
                $this->whereBeforeRecord($query, $record, $index + 1);
            });
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function runPayload(StreakRun $run, ?Question $question, ?int $duration = null): array
    {
        return [
            'id' => $run->getKey(),
            'status' => $run->status,
            'score' => $run->score,
            'duration_seconds' => $duration,
            'question' => $question ? $this->questionPayload($question) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function questionPayload(Question $question): array
    {
        $question->loadMissing(['media', 'questionTopic']);

        return [
            'id' => $question->getKey(),
            'prompt' => $question->prompt,
            'question_type' => $question->question_type,
            'topic' => $question->questionTopic?->name,
            'options' => collect([
                'a' => $question->option_a,
                'b' => $question->option_b,
                'c' => $question->option_c,
            ])
                ->filter(fn ($option) => is_string($option) && trim($option) !== '')
                ->map(fn (string $text, string $key): array => [
                    'key' => $key,
                    'label' => Str::upper($key),
                    'text' => $text,
                ])
                ->values()
                ->all(),
            'media' => $this->questionMediaPayloadBuilder->forQuestion($question->media),
            // Never expose correct-answer audio/transcripts before scoring.
            'audio' => ['question' => $this->questionAudioPayloadBuilder->forQuestionPrompt($question)],
        ];
    }

    protected function publicName(?string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim((string) $fullName)) ?: [];
        $first = $parts[0] ?? 'Kursant';
        $lastInitial = isset($parts[1]) ? ' '.mb_substr($parts[1], 0, 1).'.' : '';

        return $first.$lastInitial;
    }
}
