<?php

namespace App\Support;

use App\Models\TrafficSign;
use App\Models\TrafficSignLearningAnswer;
use App\Models\TrafficSignLearningSession;
use App\Models\User;
use App\Models\UserTrafficSignProgress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrafficSignLearningSessionService
{
    public function __construct(
        protected TrafficSignLearningCorpusService $corpusService,
        protected TrafficSignLearningPlannerService $plannerService,
        protected TrafficSignConfusionPairService $confusionPairService,
    ) {}

    /**
     * @param  list<string>|null  $categorySlugs
     */
    public function start(
        User $user,
        ?array $categorySlugs = null,
        string $mode = TrafficSignLearningSession::MODE_RECOGNITION,
    ): TrafficSignLearningSession {
        $trainableSigns = $this->corpusService->trainableSigns($categorySlugs);
        $progressBySignId = $this->corpusService->progressForSigns($user, $trainableSigns);
        $plannedSigns = $mode === TrafficSignLearningSession::MODE_SIMILAR_SIGNS
            ? $this->confusionPairService->signsWithConfusions(
                $categorySlugs,
                TrafficSignLearningPlannerService::SESSION_LIMIT,
                $trainableSigns,
            )
            : $this->plannerService->planFromSnapshot($trainableSigns, $progressBySignId);

        if ($plannedSigns->isEmpty()) {
            $plannedSigns = $this->plannerService->planFromSnapshot($trainableSigns, $progressBySignId);
            $mode = TrafficSignLearningSession::MODE_RECOGNITION;
        }

        $session = TrafficSignLearningSession::query()->create([
            'user_id' => $user->getKey(),
            'mode' => $mode,
            'status' => TrafficSignLearningSession::STATUS_IN_PROGRESS,
            'total_signs_count' => $plannedSigns->count(),
            'started_at' => now(),
            'payload' => [
                'traffic_sign_ids' => $plannedSigns->pluck('id')->values()->all(),
                'category_slugs' => array_values($categorySlugs ?? TrafficSignLearningCorpusService::MVP_CATEGORY_SLUGS),
            ],
        ]);

        $allSigns = $categorySlugs === null
            ? $trainableSigns
            : $this->corpusService->trainableSigns();
        $answerMode = $mode === TrafficSignLearningSession::MODE_DESCRIPTION_TO_SIGN
            ? TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN
            : TrafficSignLearningAnswer::MODE_SIGN_TO_MEANING;
        $timestamp = now();

        $answers = $plannedSigns
            ->values()
            ->map(function (TrafficSign $sign, int $index) use ($session, $user, $allSigns, $mode, $answerMode, $timestamp): array {
                return [
                    'traffic_sign_learning_session_id' => $session->getKey(),
                    'user_id' => $user->getKey(),
                    'traffic_sign_id' => $sign->getKey(),
                    'position' => $index + 1,
                    'answer_mode' => $answerMode,
                    'options' => json_encode($this->optionsForSign($sign, $allSigns, $mode), JSON_THROW_ON_ERROR),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->all();

        if ($answers !== []) {
            TrafficSignLearningAnswer::query()->insert($answers);
        }

        return $session;
    }

    public function activeSession(User $user): ?TrafficSignLearningSession
    {
        return TrafficSignLearningSession::query()
            ->where('user_id', $user->getKey())
            ->where('status', TrafficSignLearningSession::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();
    }

    public function currentAnswer(TrafficSignLearningSession $session, ?int $feedbackAnswerId = null): ?TrafficSignLearningAnswer
    {
        if ($feedbackAnswerId !== null) {
            $feedbackAnswer = $session
                ->answers()
                ->with('trafficSign.category')
                ->whereKey($feedbackAnswerId)
                ->whereNotNull('answered_at')
                ->first();

            if ($feedbackAnswer instanceof TrafficSignLearningAnswer) {
                return $feedbackAnswer;
            }
        }

        return $session
            ->answers()
            ->with('trafficSign.category')
            ->whereNull('answered_at')
            ->orderBy('position')
            ->first();
    }

    public function submitAnswer(User $user, int $answerId, int $selectedTrafficSignId, ?int $responseTimeMs): TrafficSignLearningAnswer
    {
        return $this->submitSingleAnswer($user, $answerId, $selectedTrafficSignId, $responseTimeMs);
    }

    /**
     * @param  list<array{answer_id:int, selected_traffic_sign_id:int, response_time_ms?:int|null}>  $answers
     * @return array{synced_answer_ids:list<int>, completed:bool, redirect_url:string|null, session:array{id:int, mode:string, status:string, total_signs_count:int}|null}
     */
    public function syncAnswers(User $user, array $answers): array
    {
        return DB::transaction(function () use ($user, $answers): array {
            $payloads = collect($answers)
                ->map(fn (array $answerPayload): array => [
                    'answer_id' => (int) $answerPayload['answer_id'],
                    'selected_traffic_sign_id' => (int) $answerPayload['selected_traffic_sign_id'],
                    'response_time_ms' => isset($answerPayload['response_time_ms'])
                        ? (int) $answerPayload['response_time_ms']
                        : null,
                ])
                ->values();
            $answerIds = $payloads
                ->pluck('answer_id')
                ->unique()
                ->values();

            /** @var Collection<int, TrafficSignLearningAnswer> $answersById */
            $answersById = TrafficSignLearningAnswer::query()
                ->with('learningSession')
                ->where('user_id', $user->getKey())
                ->whereIn('id', $answerIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($answersById->count() !== $answerIds->count()) {
                $missingAnswerIds = $answerIds
                    ->diff($answersById->keys()->map(fn (mixed $answerId): int => (int) $answerId))
                    ->values()
                    ->all();

                throw (new ModelNotFoundException)->setModel(TrafficSignLearningAnswer::class, $missingAnswerIds);
            }

            $timestamp = now();
            $answerRows = [];
            $progressEvents = collect();
            $syncedAnswerIds = [];
            $touchedSessions = collect();

            foreach ($payloads as $payload) {
                /** @var TrafficSignLearningAnswer $answer */
                $answer = $answersById->get($payload['answer_id']);
                $selectedTrafficSignId = (int) $payload['selected_traffic_sign_id'];

                $touchedSessions->put($answer->traffic_sign_learning_session_id, $answer->learningSession);

                if ($answer->answered_at !== null) {
                    if ((int) $answer->selected_traffic_sign_id === $selectedTrafficSignId) {
                        $syncedAnswerIds[] = $answer->getKey();

                        continue;
                    }

                    throw ValidationException::withMessages([
                        'answer_id' => 'Ta odpowiedź została już zapisana.',
                    ]);
                }

                $validOptionIds = collect($answer->options ?? [])
                    ->pluck('traffic_sign_id')
                    ->map(fn (mixed $trafficSignId): int => (int) $trafficSignId);

                if (! $validOptionIds->contains($selectedTrafficSignId)) {
                    throw ValidationException::withMessages([
                        'selected_traffic_sign_id' => 'Wybierz jedną z widocznych odpowiedzi.',
                    ]);
                }

                $isCorrect = (int) $answer->traffic_sign_id === $selectedTrafficSignId;

                $answerRows[] = [
                    'id' => $answer->getKey(),
                    'traffic_sign_learning_session_id' => $answer->traffic_sign_learning_session_id,
                    'user_id' => $answer->user_id,
                    'traffic_sign_id' => $answer->traffic_sign_id,
                    'selected_traffic_sign_id' => $selectedTrafficSignId,
                    'position' => $answer->position,
                    'answer_mode' => $answer->answer_mode,
                    'options' => json_encode($answer->options ?? [], JSON_THROW_ON_ERROR),
                    'is_correct' => $isCorrect,
                    'response_time_ms' => $payload['response_time_ms'],
                    'answered_at' => $timestamp,
                    'created_at' => $answer->created_at ?? $timestamp,
                    'updated_at' => $timestamp,
                ];

                $progressEvents->push([
                    'traffic_sign_id' => (int) $answer->traffic_sign_id,
                    'selected_traffic_sign_id' => $selectedTrafficSignId,
                    'is_correct' => $isCorrect,
                ]);

                $answer->forceFill([
                    'selected_traffic_sign_id' => $selectedTrafficSignId,
                    'is_correct' => $isCorrect,
                    'response_time_ms' => $payload['response_time_ms'],
                    'answered_at' => $timestamp,
                ]);

                $syncedAnswerIds[] = $answer->getKey();
            }

            if ($answerRows !== []) {
                TrafficSignLearningAnswer::query()->upsert(
                    $answerRows,
                    ['id'],
                    [
                        'selected_traffic_sign_id',
                        'is_correct',
                        'response_time_ms',
                        'answered_at',
                        'updated_at',
                    ],
                );
            }

            $this->updateProgressForBatch($user, $progressEvents, $timestamp);

            $freshSessionsById = collect();

            $touchedSessions->each(function (TrafficSignLearningSession $session) use ($freshSessionsById): void {
                if (! $session->answers()->whereNull('answered_at')->exists()) {
                    $session = $this->syncSessionScore($session);
                }

                $freshSessionsById->put($session->getKey(), $session->fresh());
            });

            $latestPayload = $payloads->last();
            $latestAnswer = $latestPayload !== null
                ? $answersById->get($latestPayload['answer_id'])
                : null;
            $latestSession = $latestAnswer instanceof TrafficSignLearningAnswer
                ? $freshSessionsById->get($latestAnswer->traffic_sign_learning_session_id)
                : null;

            if (! $latestSession instanceof TrafficSignLearningSession) {
                return [
                    'synced_answer_ids' => $syncedAnswerIds,
                    'completed' => false,
                    'redirect_url' => null,
                    'session' => null,
                ];
            }

            $completed = $latestSession->status === TrafficSignLearningSession::STATUS_COMPLETED;

            return [
                'synced_answer_ids' => $syncedAnswerIds,
                'completed' => $completed,
                'redirect_url' => $completed
                    ? route('traffic-sign-learning.results.show', $latestSession, absolute: false)
                    : null,
                'session' => [
                    'id' => $latestSession->getKey(),
                    'mode' => $latestSession->mode,
                    'status' => $latestSession->status,
                    'total_signs_count' => $latestSession->total_signs_count,
                ],
            ];
        });
    }

    public function submitSingleAnswer(
        User $user,
        int $answerId,
        int $selectedTrafficSignId,
        ?int $responseTimeMs,
        bool $allowAnsweredRetry = false,
    ): TrafficSignLearningAnswer {
        $answer = TrafficSignLearningAnswer::query()
            ->with('learningSession')
            ->where('user_id', $user->getKey())
            ->whereKey($answerId)
            ->firstOrFail();

        if ($answer->answered_at !== null) {
            if (
                $allowAnsweredRetry
                && (int) $answer->selected_traffic_sign_id === $selectedTrafficSignId
            ) {
                return $answer;
            }

            throw ValidationException::withMessages([
                'answer_id' => 'Ta odpowiedź została już zapisana.',
            ]);
        }

        $validOptionIds = collect($answer->options ?? [])
            ->pluck('traffic_sign_id')
            ->map(fn (mixed $trafficSignId): int => (int) $trafficSignId);

        if (! $validOptionIds->contains($selectedTrafficSignId)) {
            throw ValidationException::withMessages([
                'selected_traffic_sign_id' => 'Wybierz jedną z widocznych odpowiedzi.',
            ]);
        }

        $isCorrect = $answer->traffic_sign_id === $selectedTrafficSignId;

        $answer->forceFill([
            'selected_traffic_sign_id' => $selectedTrafficSignId,
            'is_correct' => $isCorrect,
            'response_time_ms' => $responseTimeMs,
            'answered_at' => now(),
        ])->save();

        $this->updateProgress($user, $answer, $selectedTrafficSignId, $isCorrect);
        $session = $answer->learningSession;

        if (! $session->answers()->whereNull('answered_at')->exists()) {
            $session = $this->syncSessionScore($session);
        }

        $answer->setRelation('learningSession', $session);

        return $answer;
    }

    public function syncSessionScore(TrafficSignLearningSession $session): TrafficSignLearningSession
    {
        $stats = $session->answers()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COUNT(answered_at) as answered_count')
            ->selectRaw('SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as correct_count')
            ->first();
        $answeredCount = (int) ($stats?->answered_count ?? 0);
        $correctCount = (int) ($stats?->correct_count ?? 0);
        $totalCount = (int) ($stats?->total_count ?? 0);

        $session->forceFill([
            'correct_answers_count' => $correctCount,
            'total_signs_count' => $totalCount,
            'score_percent' => $totalCount > 0 ? round(($correctCount / $totalCount) * 100, 2) : null,
            'status' => $answeredCount >= $totalCount
                ? TrafficSignLearningSession::STATUS_COMPLETED
                : $session->status,
            'completed_at' => $answeredCount >= $totalCount
                ? ($session->completed_at ?? now())
                : $session->completed_at,
        ])->save();

        return $session->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function questionPayload(TrafficSignLearningSession $session, TrafficSignLearningAnswer $answer): array
    {
        $answer->loadMissing('trafficSign.category');
        $optionSignsById = $this->optionSignsByIdForAnswers(collect([$answer]));

        return $this->questionPayloadFromAnswer($session, $answer, $optionSignsById);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sessionQuestionsPayload(TrafficSignLearningSession $session): array
    {
        $answers = $session
            ->answers()
            ->with('trafficSign.category')
            ->orderBy('position')
            ->get();
        $optionSignsById = $this->optionSignsByIdForAnswers($answers);

        return $answers
            ->map(fn (TrafficSignLearningAnswer $answer): array => $this->questionPayloadFromAnswer(
                $session,
                $answer,
                $optionSignsById,
            ))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TrafficSignLearningAnswer>  $answers
     * @return Collection<int, TrafficSign>
     */
    protected function optionSignsByIdForAnswers(Collection $answers): Collection
    {
        $optionIds = $answers
            ->flatMap(fn (TrafficSignLearningAnswer $answer): Collection => collect($answer->options ?? [])
                ->pluck('traffic_sign_id'))
            ->map(fn (mixed $trafficSignId): int => (int) $trafficSignId)
            ->filter()
            ->unique()
            ->values();

        if ($optionIds->isEmpty()) {
            return collect();
        }

        return TrafficSign::query()
            ->with('category:id,name,slug,sort_order')
            ->whereIn('id', $optionIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, TrafficSign>  $optionSignsById
     * @return array<string, mixed>
     */
    protected function questionPayloadFromAnswer(
        TrafficSignLearningSession $session,
        TrafficSignLearningAnswer $answer,
        Collection $optionSignsById,
    ): array {
        $sign = $answer->trafficSign;
        $answered = $answer->answered_at !== null;
        $hasMore = $answer->position < $session->total_signs_count;
        $explanation = $this->explanationForSign($sign);

        return [
            'answer_id' => $answer->getKey(),
            'mode' => $session->mode,
            'answer_mode' => $answer->answer_mode,
            'position' => $answer->position,
            'total' => $session->total_signs_count,
            'answered' => $answered,
            'has_more' => $hasMore,
            'sign' => $this->corpusService->signPayload($sign),
            'prompt' => $answer->answer_mode === TrafficSignLearningAnswer::MODE_MEANING_TO_SIGN
                ? $this->reversePromptForSign($sign)
                : null,
            'explanation' => $explanation,
            'options' => collect($answer->options ?? [])
                ->map(function (array $option) use ($optionSignsById): array {
                    $trafficSignId = (int) $option['traffic_sign_id'];
                    /** @var TrafficSign|null $optionSign */
                    $optionSign = $optionSignsById->get($trafficSignId);

                    return [
                        'traffic_sign_id' => $trafficSignId,
                        'label' => (string) ($option['label'] ?? $optionSign?->name ?? ''),
                        'code' => $optionSign?->code,
                        'image_url' => $optionSign !== null
                            ? $this->corpusService->signPayload($optionSign)['image_url']
                            : null,
                    ];
                })
                ->values()
                ->all(),
            'feedback' => $answered ? [
                'is_correct' => (bool) $answer->is_correct,
                'selected_traffic_sign_id' => $answer->selected_traffic_sign_id,
                'correct_traffic_sign_id' => $answer->traffic_sign_id,
                'correct_label' => $sign->name,
                'explanation' => $explanation,
            ] : null,
            'next_url' => $hasMore
                ? route('traffic-sign-learning.current', absolute: false)
                : route('traffic-sign-learning.results.show', $session, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function answerFeedbackPayload(
        TrafficSignLearningSession $session,
        TrafficSignLearningAnswer $answer,
    ): array {
        $answer->loadMissing('trafficSign');
        $sign = $answer->trafficSign;

        return [
            'answered' => true,
            'has_more' => $session->status !== TrafficSignLearningSession::STATUS_COMPLETED,
            'feedback' => [
                'is_correct' => (bool) $answer->is_correct,
                'selected_traffic_sign_id' => $answer->selected_traffic_sign_id,
                'correct_traffic_sign_id' => $answer->traffic_sign_id,
                'correct_label' => $sign->name,
                'explanation' => $this->explanationForSign($sign),
            ],
            'next_url' => $session->status !== TrafficSignLearningSession::STATUS_COMPLETED
                ? route('traffic-sign-learning.current', absolute: false)
                : route('traffic-sign-learning.results.show', $session, absolute: false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resultPayload(TrafficSignLearningSession $session): array
    {
        $session->loadMissing('answers.trafficSign.category', 'answers.selectedTrafficSign.category');

        return [
            'id' => $session->getKey(),
            'mode' => $session->mode,
            'status' => $session->status,
            'total_signs_count' => $session->total_signs_count,
            'correct_answers_count' => $session->correct_answers_count,
            'score_percent' => $session->score_percent !== null ? (float) $session->score_percent : 0,
            'confusions' => $this->confusionSummary($session),
            'answers' => $session->answers
                ->sortBy('position')
                ->map(fn (TrafficSignLearningAnswer $answer): array => [
                    'id' => $answer->getKey(),
                    'position' => $answer->position,
                    'is_correct' => (bool) $answer->is_correct,
                    'selected_traffic_sign_id' => $answer->selected_traffic_sign_id,
                    'traffic_sign' => $this->corpusService->signPayload($answer->trafficSign),
                    'correct_label' => $answer->trafficSign->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, TrafficSign>  $allSigns
     * @return list<array{traffic_sign_id: int, label: string}>
     */
    protected function optionsForSign(TrafficSign $sign, Collection $allSigns, string $mode): array
    {
        $confusingSigns = $mode === TrafficSignLearningSession::MODE_SIMILAR_SIGNS
            ? $this->confusionPairService->confusingSignsFor($sign, trainableSigns: $allSigns)
            : collect();
        $sameCategory = $allSigns
            ->where('traffic_sign_category_id', $sign->traffic_sign_category_id)
            ->reject(fn (TrafficSign $candidate): bool => $candidate->is($sign));
        $fallback = $allSigns->reject(fn (TrafficSign $candidate): bool => $candidate->is($sign));

        return collect([$sign])
            ->concat($confusingSigns)
            ->concat($sameCategory)
            ->concat($fallback)
            ->unique('id')
            ->take(4)
            ->shuffle()
            ->map(fn (TrafficSign $option): array => [
                'traffic_sign_id' => $option->getKey(),
                'label' => (string) $option->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{traffic_sign_id: int, code: string|null, name: string, image_url: string|null, count: int}>
     */
    protected function confusionSummary(TrafficSignLearningSession $session): array
    {
        return $session->answers
            ->filter(fn (TrafficSignLearningAnswer $answer): bool => $answer->is_correct === false && $answer->selectedTrafficSign !== null)
            ->groupBy('selected_traffic_sign_id')
            ->map(function (Collection $answers): array {
                /** @var TrafficSignLearningAnswer $firstAnswer */
                $firstAnswer = $answers->first();
                /** @var TrafficSign $selectedSign */
                $selectedSign = $firstAnswer->selectedTrafficSign;

                return [
                    'traffic_sign_id' => $selectedSign->getKey(),
                    'code' => $selectedSign->code,
                    'name' => $selectedSign->name,
                    'image_url' => $this->corpusService->signPayload($selectedSign)['image_url'],
                    'count' => $answers->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(3)
            ->values()
            ->all();
    }

    protected function updateProgress(
        User $user,
        TrafficSignLearningAnswer $answer,
        int $selectedTrafficSignId,
        bool $isCorrect,
    ): void {
        $progress = UserTrafficSignProgress::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'traffic_sign_id' => $answer->traffic_sign_id,
        ]);

        $attemptsCount = (int) $progress->attempts_count + 1;
        $correctCount = (int) $progress->correct_count + ($isCorrect ? 1 : 0);
        $incorrectCount = (int) $progress->incorrect_count + ($isCorrect ? 0 : 1);
        $correctStreak = $isCorrect ? (int) $progress->correct_streak + 1 : 0;

        $progress->forceFill([
            'state' => $this->nextProgressState($isCorrect, $correctStreak),
            'attempts_count' => $attemptsCount,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'correct_streak' => $correctStreak,
            'last_confused_with_traffic_sign_id' => $isCorrect ? null : $selectedTrafficSignId,
            'last_answered_at' => now(),
            'next_review_at' => $this->nextReviewAt($isCorrect, $correctStreak),
        ])->save();
    }

    /**
     * @param  Collection<int, array{traffic_sign_id:int, selected_traffic_sign_id:int, is_correct:bool}>  $events
     */
    protected function updateProgressForBatch(User $user, Collection $events, \DateTimeInterface $timestamp): void
    {
        if ($events->isEmpty()) {
            return;
        }

        $trafficSignIds = $events
            ->pluck('traffic_sign_id')
            ->unique()
            ->values();

        /** @var Collection<int, UserTrafficSignProgress> $existingProgressBySignId */
        $existingProgressBySignId = UserTrafficSignProgress::query()
            ->where('user_id', $user->getKey())
            ->whereIn('traffic_sign_id', $trafficSignIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('traffic_sign_id');
        $progressSnapshots = [];

        foreach ($events as $event) {
            $trafficSignId = (int) $event['traffic_sign_id'];
            $selectedTrafficSignId = (int) $event['selected_traffic_sign_id'];
            $isCorrect = (bool) $event['is_correct'];
            $current = $progressSnapshots[$trafficSignId] ?? null;

            if ($current === null) {
                /** @var UserTrafficSignProgress|null $existingProgress */
                $existingProgress = $existingProgressBySignId->get($trafficSignId);
                $current = [
                    'attempts_count' => (int) ($existingProgress?->attempts_count ?? 0),
                    'correct_count' => (int) ($existingProgress?->correct_count ?? 0),
                    'incorrect_count' => (int) ($existingProgress?->incorrect_count ?? 0),
                    'correct_streak' => (int) ($existingProgress?->correct_streak ?? 0),
                    'created_at' => $existingProgress?->created_at ?? $timestamp,
                ];
            }

            $correctStreak = $isCorrect
                ? (int) $current['correct_streak'] + 1
                : 0;

            $progressSnapshots[$trafficSignId] = [
                'user_id' => $user->getKey(),
                'traffic_sign_id' => $trafficSignId,
                'state' => $this->nextProgressState($isCorrect, $correctStreak),
                'attempts_count' => (int) $current['attempts_count'] + 1,
                'correct_count' => (int) $current['correct_count'] + ($isCorrect ? 1 : 0),
                'incorrect_count' => (int) $current['incorrect_count'] + ($isCorrect ? 0 : 1),
                'correct_streak' => $correctStreak,
                'last_confused_with_traffic_sign_id' => $isCorrect ? null : $selectedTrafficSignId,
                'last_answered_at' => $timestamp,
                'next_review_at' => $this->nextReviewAt($isCorrect, $correctStreak),
                'created_at' => $current['created_at'],
                'updated_at' => $timestamp,
            ];
        }

        UserTrafficSignProgress::query()->upsert(
            array_values($progressSnapshots),
            ['user_id', 'traffic_sign_id'],
            [
                'state',
                'attempts_count',
                'correct_count',
                'incorrect_count',
                'correct_streak',
                'last_confused_with_traffic_sign_id',
                'last_answered_at',
                'next_review_at',
                'updated_at',
            ],
        );
    }

    protected function nextProgressState(bool $isCorrect, int $correctStreak): string
    {
        if (! $isCorrect) {
            return UserTrafficSignProgress::STATE_NEEDS_REVIEW;
        }

        return $correctStreak >= 3
            ? UserTrafficSignProgress::STATE_MASTERED
            : UserTrafficSignProgress::STATE_LEARNING;
    }

    protected function nextReviewAt(bool $isCorrect, int $correctStreak): \DateTimeInterface
    {
        if (! $isCorrect) {
            return now();
        }

        if ($correctStreak >= 3) {
            return now()->addDays(7);
        }

        if ($correctStreak === 2) {
            return now()->addDays(2);
        }

        return now()->addHours(6);
    }

    protected function explanationForSign(TrafficSign $sign): string
    {
        return (string) ($sign->intro_definition ?: $sign->meaning ?: $sign->driver_behavior ?: $sign->name);
    }

    protected function reversePromptForSign(TrafficSign $sign): string
    {
        return (string) ($sign->meaning ?: $sign->driver_behavior ?: $sign->intro_definition ?: $sign->name);
    }
}
