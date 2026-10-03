<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\StreakRecord;
use App\Models\StreakRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StreakChallengeService
{
    public function __construct(
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected UserAvatarService $userAvatarService,
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
            ->latest('id')
            ->first();
        $lastScore = null;
        $rank = 0;
        $leaders = StreakRecord::query()
            ->with('user')
            ->where('license_category_id', $category->getKey())
            ->where('best_score', '>', 0)
            ->orderByDesc('best_score')
            ->orderBy('best_achieved_at')
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->values()
            ->map(function (StreakRecord $leader, int $index) use ($user, &$lastScore, &$rank): array {
                if ($leader->best_score !== $lastScore) {
                    $rank = $index + 1;
                    $lastScore = $leader->best_score;
                }

                $leaderUser = $leader->user;
                $avatar = $leaderUser ? $this->userAvatarService->payload($leaderUser) : null;

                return [
                    'position' => $rank,
                    'name' => $this->publicName($leaderUser?->name),
                    'score' => $leader->best_score,
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
            'personal_rank' => $bestScore > 0
                ? StreakRecord::query()
                    ->where('license_category_id', $category->getKey())
                    ->where('best_score', '>', $bestScore)
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

    /**
     * @return array<string, mixed>
     */
    public function answer(User $user, StreakRun $run, int $questionId, string $selectedAnswer): array
    {
        $result = DB::transaction(function () use ($user, $run, $questionId, $selectedAnswer): array {
            $lockedRun = StreakRun::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            abort_unless($lockedRun->status === StreakRun::STATUS_IN_PROGRESS, 409, 'Ta seria została już zakończona.');
            abort_unless($lockedRun->current_question_id === $questionId, 409, 'To pytanie nie jest już aktywne.');

            $question = Question::query()->findOrFail($questionId);
            $selectedAnswer = Str::lower($selectedAnswer);
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
                'answered_at' => now(),
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

            if ($lockedRun->status !== StreakRun::STATUS_IN_PROGRESS) {
                $this->saveBestResult($lockedRun);
            }

            return [
                'is_correct' => $isCorrect,
                'finished' => $lockedRun->status !== StreakRun::STATUS_IN_PROGRESS,
                'score' => $lockedRun->score,
                'run' => $this->runPayload($lockedRun, $nextQuestion),
                'correct_answer' => $isCorrect ? null : Str::upper((string) $question->correct_answer),
                'explanation' => $isCorrect ? null : $question->explanation,
            ];
        });

        if ($result['finished']) {
            $result['overview'] = $this->overview($user, $run->licenseCategory);
        }

        return $result;
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

    protected function saveBestResult(StreakRun $run): void
    {
        $record = StreakRecord::query()
            ->where('user_id', $run->user_id)
            ->where('license_category_id', $run->license_category_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($run->score > $record->best_score) {
            $record->update([
                'best_score' => $run->score,
                'best_run_id' => $run->getKey(),
                'best_achieved_at' => $run->finished_at,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function runPayload(StreakRun $run, ?Question $question): array
    {
        return [
            'id' => $run->getKey(),
            'status' => $run->status,
            'score' => $run->score,
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
