<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedMatchAnswer;
use App\Models\RankedMatchEvent;
use App\Models\RankedMatchPlayer;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RankedMatchService
{
    public const MAX_CONCURRENT_PLAYERS = 100;

    public const HEARTBEAT_SECONDS = 10;

    public const DISCONNECT_TIMEOUT_SECONDS = 30;

    public const RECONNECT_GRACE_SECONDS = 25;

    public const MATCHMAKING_TIMEOUT_SECONDS = 90;

    public const MATCH_READY_COUNTDOWN_SECONDS = 3;

    public function __construct(
        protected StudyContextService $studyContextService,
        protected RankedMatchPresenceStore $presenceStore,
    ) {}

    /**
     * @return array{rating: RankedPlayerRating, queue: RankedQueueEntry|null, active_match: RankedMatch|null, capacity: array{current_players:int, max_players:int, available_slots:int, waiting_users:int, is_full:bool}}
     */
    public function overview(User $user): array
    {
        $rating = $this->ensureRating($user);
        $this->reconcileQueueAvailability($user);
        $activeMatch = $this->activeMatch($user);

        return [
            'rating' => $rating,
            'queue' => $this->activeQueueEntry($user),
            'active_match' => $activeMatch,
            'capacity' => $this->capacitySnapshot(),
        ];
    }

    public function recentMatch(User $user): ?RankedMatch
    {
        $this->syncLatestActiveMatchForUser($user);

        return $this->recentMatchesBaseQuery($user)->first();
    }

    public function recentMatches(User $user, int $limit = 5)
    {
        $this->syncLatestActiveMatchForUser($user);

        return $this->recentMatchesBaseQuery($user)
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{rating: RankedPlayerRating, queue: RankedQueueEntry|null, active_match: RankedMatch|null, capacity: array{current_players:int, max_players:int, available_slots:int, waiting_users:int, is_full:bool}, state: string, joined: bool}
     */
    public function joinQueue(User $user, LicenseCategory $category): array
    {
        $allowedCategory = $this->studyContextService->visibleCategoriesQuery()
            ->whereKey($category->getKey())
            ->first();

        if (! $allowedCategory) {
            throw ValidationException::withMessages([
                'category_id' => 'Wybrana kategoria rankingowa nie jest dostępna do kolejki.',
            ]);
        }

        $joined = false;
        $state = 'queued';

        $result = $this->runWithSqliteBusyRetry(function () use ($category, &$joined, &$state, $user): array {
            $this->expireTimedOutQueuedEntries();
            $rating = $this->ensureRating($user);
            $activeMatch = $this->findActiveMatchForUser($user);

            if ($activeMatch) {
                $activeMatch = $this->applyPresenceTransitions($activeMatch, now());

                if ($this->isActiveMatch($activeMatch)) {
                    $state = 'matched';

                    return [
                        'rating' => $rating,
                        'queue' => $this->activeQueueEntry($user),
                        'active_match' => $activeMatch,
                        'capacity' => $this->capacitySnapshot(),
                    ];
                }
            }

            $currentQueueEntry = $this->activeQueueEntry($user);

            if ($currentQueueEntry && $currentQueueEntry->license_category_id === $category->getKey()) {
                if ($currentQueueEntry->status === 'server_full') {
                    $currentQueueEntry = $this->refreshServerFullQueueEntry($currentQueueEntry);
                }

                $state = $currentQueueEntry->status === 'matched' ? 'matched' : 'queued';

                if ($currentQueueEntry->status === 'server_full') {
                    $state = 'server_full';
                }

                return [
                    'rating' => $rating,
                    'queue' => $currentQueueEntry->fresh(['licenseCategory', 'match']) ?? $currentQueueEntry,
                    'active_match' => $this->findActiveMatchForUser($user),
                    'capacity' => $this->capacitySnapshot(),
                ];
            }

            if ($currentQueueEntry) {
                $currentQueueEntry->forceFill([
                    'status' => 'cancelled',
                    'left_at' => now(),
                ])->save();
            }

            $joined = true;

            if ($this->isConcurrentCapacityFull()) {
                $state = 'server_full';
                $queueEntry = $this->storeServerFullQueueEntry($user, $category);

                return [
                    'rating' => $rating->fresh() ?? $rating,
                    'queue' => $queueEntry,
                    'active_match' => null,
                    'capacity' => $this->capacitySnapshot(),
                ];
            }

            $queueEntry = RankedQueueEntry::create([
                'user_id' => $user->getKey(),
                'license_category_id' => $category->getKey(),
                'status' => 'queued',
                'joined_at' => now(),
                'payload' => [
                    'source' => 'api',
                ],
            ]);

            $queueEntry = $this->matchQueueEntryIfPossible($queueEntry);
            $state = $queueEntry->status === 'matched' ? 'matched' : $queueEntry->status;

            return [
                'rating' => $rating->fresh() ?? $rating,
                'queue' => $queueEntry,
                'active_match' => $queueEntry->status === 'matched'
                    ? $this->findActiveMatchForUser($user)
                    : null,
                'capacity' => $this->capacitySnapshot(),
            ];
        });

        return [
            ...$result,
            'state' => $state,
            'joined' => $joined,
        ];
    }

    /**
     * @return array{rating: RankedPlayerRating, queue: RankedQueueEntry|null, active_match: RankedMatch|null, capacity: array{current_players:int, max_players:int, available_slots:int, waiting_users:int, is_full:bool}, left_queue: bool}
     */
    public function leaveQueue(User $user): array
    {
        $leftQueue = false;

        $result = $this->runWithSqliteBusyRetry(function () use (&$leftQueue, $user): array {
            $this->expireTimedOutQueuedEntries();
            $rating = $this->ensureRating($user);
            $activeMatch = $this->findActiveMatchForUser($user);

            if ($activeMatch) {
                $activeMatch = $this->applyPresenceTransitions($activeMatch, now());

                if ($this->isActiveMatch($activeMatch)) {
                    return [
                        'rating' => $rating,
                        'queue' => $this->activeQueueEntry($user),
                        'active_match' => $activeMatch,
                        'capacity' => $this->capacitySnapshot(),
                    ];
                }
            }

            $queueEntry = $this->activeQueueEntry($user);

            if ($queueEntry && in_array($queueEntry->status, ['queued', 'server_full'], true)) {
                $queueEntry->forceFill([
                    'status' => 'cancelled',
                    'left_at' => now(),
                ])->save();

                $leftQueue = true;
            }

            return [
                'rating' => $rating->fresh() ?? $rating,
                'queue' => $this->activeQueueEntry($user),
                'active_match' => null,
                'capacity' => $this->capacitySnapshot(),
            ];
        });

        return [
            ...$result,
            'left_queue' => $leftQueue,
        ];
    }

    public function ensureRating(User $user): RankedPlayerRating
    {
        return RankedPlayerRating::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            [
                'rating' => 1500,
                'peak_rating' => 1500,
                'matches_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
            ],
        );
    }

    public function activeQueueEntry(User $user): ?RankedQueueEntry
    {
        return RankedQueueEntry::query()
            ->with(['licenseCategory', 'match'])
            ->where('user_id', $user->getKey())
            ->whereIn('status', ['queued', 'matched', 'server_full'])
            ->latest('id')
            ->first();
    }

    /**
     * @return array{current_players:int, max_players:int, available_slots:int, waiting_users:int, is_full:bool}
     */
    public function capacitySnapshot(): array
    {
        $currentPlayers = $this->activeConcurrentUsersCount();
        $maxPlayers = self::MAX_CONCURRENT_PLAYERS;

        return [
            'current_players' => $currentPlayers,
            'max_players' => $maxPlayers,
            'available_slots' => max($maxPlayers - $currentPlayers, 0),
            'waiting_users' => $this->waitingServerFullUsersCount(),
            'is_full' => $currentPlayers >= $maxPlayers,
        ];
    }

    public function activeMatch(User $user): ?RankedMatch
    {
        $match = $this->findActiveMatchForUser($user);

        if (! $match) {
            return null;
        }

        $match = $this->syncMatchPresence($match);

        return $this->isActiveMatch($match) ? $match : null;
    }

    public function matchForUser(User $user, string $publicId): RankedMatch
    {
        $match = RankedMatch::query()
            ->with($this->defaultMatchRelations())
            ->where('public_id', $publicId)
            ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
            ->firstOrFail();

        return $this->syncMatchPresence($match);
    }

    public function recordPong(RankedMatch $rankedMatch, User $user): RankedMatch
    {
        return $this->runWithSqliteBusyRetry(function () use ($rankedMatch, $user): RankedMatch {
            $match = RankedMatch::query()
                ->with($this->defaultMatchRelations())
                ->whereKey($rankedMatch->getKey())
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
                ->firstOrFail();

            $match = $this->applyPresenceTransitions($match, now());

            if ($this->isActiveMatch($match)) {
                $player = $match->players->firstWhere('user_id', $user->getKey());

                abort_unless($player !== null, 403);

                $this->markPlayerOnline($match, $player, now());
                $match = $this->freshMatch($match) ?? $match;
                $match = $this->applyPresenceTransitions($match, now());
            }

            return $this->freshMatch($match) ?? $match;
        });
    }

    public function recordReady(RankedMatch $rankedMatch, User $user): RankedMatch
    {
        return $this->runWithSqliteBusyRetry(function () use ($rankedMatch, $user): RankedMatch {
            /** @var RankedMatch $match */
            $match = RankedMatch::query()
                ->with($this->defaultMatchRelations())
                ->whereKey($rankedMatch->getKey())
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
                ->firstOrFail();

            $match = $this->applyPresenceTransitions($match, now());

            if (! in_array($match->status, ['matched', 'in_progress'], true)) {
                return $this->freshMatch($match) ?? $match;
            }

            $player = $match->players->firstWhere('user_id', $user->getKey());

            abort_unless($player !== null, 403);

            $seenAt = now();
            $this->markPlayerOnline($match, $player, $seenAt);

            if ($match->status === 'matched' && $player->ready_at === null) {
                $player->forceFill([
                    'ready_at' => $seenAt,
                ])->save();
            }

            $match = $this->freshMatch($match) ?? $match;

            if ($match->status === 'matched') {
                $this->startCountdownIfPlayersReady($match, $seenAt);
                $match = $this->freshMatch($match) ?? $match;
                $match = $this->applyPresenceTransitions($match, now());
            }

            return $this->freshMatch($match) ?? $match;
        });
    }

    public function abandonMatchByUser(RankedMatch $rankedMatch, User $user): RankedMatch
    {
        return $this->runWithSqliteBusyRetry(function () use ($rankedMatch, $user): RankedMatch {
            /** @var RankedMatch $match */
            $match = RankedMatch::query()
                ->with($this->defaultMatchRelations())
                ->whereKey($rankedMatch->getKey())
                ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
                ->firstOrFail();

            $match = $this->applyPresenceTransitions($match, now());

            if (! in_array($match->status, ['matched', 'in_progress'], true)) {
                return $this->freshMatch($match) ?? $match;
            }

            $loser = $match->players->firstWhere('user_id', $user->getKey());
            $winner = $match->players->first(
                fn (RankedMatchPlayer $player) => $player->user_id !== $user->getKey()
            );

            if ($loser && $winner) {
                $this->abandonMatchAsWalkover($match, $winner, $loser, 'player_left_match', now());
            } else {
                $this->abandonMatchWithoutWinner($match, 'player_left_match', now());
            }

            return $this->freshMatch($match) ?? $match;
        });
    }

    public function orderedQuestions(RankedMatch $match)
    {
        $questionIds = collect($match->payload['question_ids'] ?? [])
            ->map(fn ($questionId) => (int) $questionId)
            ->filter()
            ->values();

        $questions = Question::query()
            ->with('media', 'questionTopic', 'referenceExplanationAsset', 'explanationAnnotations')
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        return $questionIds
            ->map(fn (int $questionId) => $questions->get($questionId))
            ->filter()
            ->values();
    }

    public function recordAnswer(
        RankedMatch $rankedMatch,
        User $user,
        Question $question,
        string $selectedAnswer,
        ?int $responseTimeMs = null,
    ): RankedMatchAnswer {
        [$answer] = $this->runWithSqliteBusyRetry(function () use ($question, $rankedMatch, $responseTimeMs, $selectedAnswer, $user): array {
            /** @var RankedMatch $match */
            $match = RankedMatch::query()
                ->with(['players', 'players.user', 'answers', 'queueEntries'])
                ->whereKey($rankedMatch->getKey())
                ->firstOrFail();

            $match = $this->applyPresenceTransitions($match, now());

            if (! in_array($match->status, ['matched', 'in_progress'], true)) {
                return [null, $this->freshMatch($match) ?? $match];
            }

            $player = $match->players->firstWhere('user_id', $user->getKey());

            abort_unless($player !== null, 403);

            $this->markPlayerOnline($match, $player, now());
            $match = $this->freshMatch($match) ?? $match;

            if ($match->status === 'matched') {
                throw ValidationException::withMessages([
                    'match_not_started' => 'Mecz jeszcze się nie rozpoczął. Poczekaj na wspólny countdown obu graczy.',
                ]);
            }

            $questionIds = collect($match->payload['question_ids'] ?? [])
                ->map(fn ($questionId) => (int) $questionId)
                ->values();
            $questionIndex = $questionIds->search($question->getKey());

            if ($questionIndex === false) {
                throw ValidationException::withMessages([
                    'question_id' => 'To pytanie nie należy do tego meczu rankingowego.',
                ]);
            }

            $existingAnswer = RankedMatchAnswer::query()
                ->where('ranked_match_id', $match->getKey())
                ->where('user_id', $user->getKey())
                ->where('question_id', $question->getKey())
                ->first();

            if ($existingAnswer) {
                return [$existingAnswer, $match];
            }

            $answer = RankedMatchAnswer::create([
                'ranked_match_id' => $match->getKey(),
                'user_id' => $user->getKey(),
                'question_id' => $question->getKey(),
                'question_number' => $questionIndex + 1,
                'selected_answer' => strtolower($selectedAnswer),
                'is_correct' => strtolower($selectedAnswer) === strtolower((string) $question->correct_answer),
                'response_time_ms' => $responseTimeMs,
                'answered_at' => now(),
            ]);

            $this->appendMatchEvent(
                $match,
                'match.opponent_answered',
                [
                    'question_id' => $question->getKey(),
                    'question_number' => $questionIndex + 1,
                    'selected_answer' => strtolower($selectedAnswer),
                ],
                $answer->answered_at ?? now(),
                $user->getKey(),
                'match.opponent_answered.answer_'.$answer->getKey(),
            );

            $this->refreshPlayerAggregates($match);
            $this->maybeFinishMatch($match);

            return [$answer, $this->freshMatch($match) ?? $match];
        });

        if (! $answer instanceof RankedMatchAnswer) {
            throw ValidationException::withMessages([
                'match' => 'Ten mecz rankingowy został już zakończony albo porzucony.',
            ]);
        }

        return $answer;
    }

    /**
     * @return list<int>
     */
    protected function buildMatchQuestionIds(LicenseCategory $category): array
    {
        return Question::query()
            ->where('license_category_id', $category->getKey())
            ->where('is_active', true)
            ->readyForDelivery()
            ->inRandomOrder()
            ->limit(40)
            ->pluck('id')
            ->map(fn ($questionId) => (int) $questionId)
            ->values()
            ->all();
    }

    protected function refreshPlayerAggregates(RankedMatch $match): void
    {
        $match->load('players', 'answers');

        foreach ($match->players as $player) {
            $answers = $match->answers->where('user_id', $player->user_id)->values();

            $player->forceFill([
                'correct_answers' => $answers->where('is_correct', true)->count(),
                'total_answered' => $answers->count(),
                'points' => $answers->where('is_correct', true)->count(),
                'sum_response_time_ms' => (int) $answers->sum('response_time_ms'),
            ])->save();
        }
    }

    protected function maybeFinishMatch(RankedMatch $match): void
    {
        $match->load('players', 'players.user', 'answers');

        $allPlayersFinished = $match->players->every(
            fn (RankedMatchPlayer $player) => $player->total_answered >= $match->total_questions
        );

        if (! $allPlayersFinished || $match->status === 'finished') {
            return;
        }

        $this->finishMatch($match, now());
    }

    protected function finishExpiredMatchIfNeeded(RankedMatch $match, Carbon $now): RankedMatch
    {
        $finishedAt = $this->scheduledMatchFinishAt($match);

        if (! $finishedAt || $now->lessThan($finishedAt)) {
            return $match;
        }

        $this->finishMatch($match, $finishedAt);

        return $this->freshMatch($match) ?? $match;
    }

    protected function scheduledMatchFinishAt(RankedMatch $match): ?Carbon
    {
        if ($match->status !== 'in_progress' || ! $match->started_at) {
            return null;
        }

        return $match->started_at->copy()->addSeconds(max($match->duration_seconds, 1));
    }

    protected function finishMatch(RankedMatch $match, Carbon $resolvedAt): void
    {
        $match->load('players', 'players.user', 'answers', 'queueEntries');

        if ($match->status === 'finished') {
            return;
        }

        $players = $match->players->sortBy('slot')->values();
        $playerOne = $players->get(0);
        $playerTwo = $players->get(1);

        if (! $playerOne || ! $playerTwo) {
            return;
        }

        $reason = 'draw';
        $winner = null;
        $loser = null;
        $scoreOne = 0.5;
        $scoreTwo = 0.5;

        if ($playerOne->correct_answers !== $playerTwo->correct_answers) {
            $reason = 'more_correct_answers';
            $winner = $playerOne->correct_answers > $playerTwo->correct_answers ? $playerOne : $playerTwo;
            $loser = $winner->is($playerOne) ? $playerTwo : $playerOne;
            $scoreOne = $winner->is($playerOne) ? 1.0 : 0.0;
            $scoreTwo = $winner->is($playerTwo) ? 1.0 : 0.0;
        } elseif ($playerOne->sum_response_time_ms !== $playerTwo->sum_response_time_ms) {
            $reason = 'faster_time';
            $winner = $playerOne->sum_response_time_ms < $playerTwo->sum_response_time_ms ? $playerOne : $playerTwo;
            $loser = $winner->is($playerOne) ? $playerTwo : $playerOne;
            $scoreOne = $winner->is($playerOne) ? 1.0 : 0.0;
            $scoreTwo = $winner->is($playerTwo) ? 1.0 : 0.0;
        }

        $ratingOne = $this->ensureRating($playerOne->user);
        $ratingTwo = $this->ensureRating($playerTwo->user);

        [$newRatingOne, $newRatingTwo] = $this->calculateEloPair(
            $ratingOne->rating,
            $ratingTwo->rating,
            $scoreOne,
            $scoreTwo,
        );

        $this->applyRatingResult($ratingOne, $newRatingOne, $scoreOne);
        $this->applyRatingResult($ratingTwo, $newRatingTwo, $scoreTwo);

        $playerOne->forceFill([
            'elo_after' => $newRatingOne,
            'elo_change' => $newRatingOne - $playerOne->elo_before,
            'status' => 'finished',
        ])->save();

        $playerTwo->forceFill([
            'elo_after' => $newRatingTwo,
            'elo_change' => $newRatingTwo - $playerTwo->elo_before,
            'status' => 'finished',
        ])->save();

        $match->forceFill([
            'status' => 'finished',
            'finished_at' => $resolvedAt,
            'reason' => $reason,
            'payload' => [
                ...($match->payload ?? []),
                'winner_user_id' => $winner?->user_id,
                'loser_user_id' => $loser?->user_id,
            ],
        ])->save();

        $this->appendMatchEvent(
            $match,
            'match.finished',
            [
                'reason' => $reason,
            ],
            $match->finished_at ?? $resolvedAt,
            null,
            'match.finished',
        );

        $match->queueEntries()->update([
            'status' => 'completed',
            'left_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $this->clearMatchPresence($match);
    }

    protected function applyRatingResult(RankedPlayerRating $rating, int $newRating, float $score): void
    {
        $matchesPlayed = $rating->matches_played + 1;
        $wins = $rating->wins;
        $losses = $rating->losses;
        $draws = $rating->draws;
        $currentStreak = $rating->current_streak;

        if ($score === 1.0) {
            $wins++;
            $currentStreak = max($currentStreak, 0) + 1;
        } elseif ($score === 0.0) {
            $losses++;
            $currentStreak = min($currentStreak, 0) - 1;
        } else {
            $draws++;
            $currentStreak = 0;
        }

        $rating->forceFill([
            'rating' => $newRating,
            'peak_rating' => max($rating->peak_rating, $newRating),
            'matches_played' => $matchesPlayed,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'current_streak' => $currentStreak,
            'best_streak' => max($rating->best_streak, max($currentStreak, 0)),
        ])->save();
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function calculateEloPair(int $ratingOne, int $ratingTwo, float $scoreOne, float $scoreTwo): array
    {
        $kFactor = 20;
        $expectedOne = 1 / (1 + (10 ** (($ratingTwo - $ratingOne) / 400)));
        $expectedTwo = 1 / (1 + (10 ** (($ratingOne - $ratingTwo) / 400)));

        $newOne = (int) round($ratingOne + ($kFactor * ($scoreOne - $expectedOne)));
        $newTwo = (int) round($ratingTwo + ($kFactor * ($scoreTwo - $expectedTwo)));

        return [$newOne, $newTwo];
    }

    protected function recentMatchesBaseQuery(User $user)
    {
        return RankedMatch::query()
            ->with(['licenseCategory', 'players', 'players.user'])
            ->whereIn('status', ['finished', 'abandoned'])
            ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
            ->orderByRaw('COALESCE(finished_at, abandoned_at, updated_at, created_at) DESC')
            ->orderByDesc('id');
    }

    protected function reconcileQueueAvailability(User $user): void
    {
        $this->runWithSqliteBusyRetry(function () use ($user): void {
            $this->expireTimedOutQueuedEntries();
            $queueEntry = $this->activeQueueEntry($user);

            if (! $queueEntry || $queueEntry->status !== 'server_full') {
                return;
            }

            $this->refreshServerFullQueueEntry($queueEntry);
        });
    }

    protected function refreshServerFullQueueEntry(RankedQueueEntry $queueEntry): RankedQueueEntry
    {
        if ($queueEntry->status !== 'server_full') {
            return $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;
        }

        $currentCapacity = $this->activeConcurrentUsersCount();

        if ($currentCapacity >= self::MAX_CONCURRENT_PLAYERS) {
            $this->updateServerFullQueuePayload($queueEntry, $currentCapacity);

            return $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;
        }

        $payload = $queueEntry->payload ?? [];
        $payload['resumed_at'] = now()->toIso8601String();

        $queueEntry->forceFill([
            'status' => 'queued',
            'payload' => $payload,
        ])->save();

        return $this->matchQueueEntryIfPossible($queueEntry);
    }

    protected function storeServerFullQueueEntry(User $user, LicenseCategory $category): RankedQueueEntry
    {
        $queueEntry = RankedQueueEntry::create([
            'user_id' => $user->getKey(),
            'license_category_id' => $category->getKey(),
            'status' => 'server_full',
            'joined_at' => now(),
            'payload' => [
                'source' => 'api',
            ],
        ]);

        $this->updateServerFullQueuePayload($queueEntry, $this->activeConcurrentUsersCount());

        return $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;
    }

    protected function updateServerFullQueuePayload(RankedQueueEntry $queueEntry, int $currentCapacity): void
    {
        $positionInQueue = $this->serverFullPositionForQueueEntry($queueEntry);
        $normalizedCapacity = min(max($currentCapacity, self::MAX_CONCURRENT_PLAYERS), self::MAX_CONCURRENT_PLAYERS);

        $queueEntry->forceFill([
            'payload' => [
                ...($queueEntry->payload ?? []),
                'error_code' => 'SERVER_FULL',
                'message' => sprintf(
                    'Server full (%d/%d). Waiting for space...',
                    $normalizedCapacity,
                    self::MAX_CONCURRENT_PLAYERS,
                ),
                'current_capacity' => $normalizedCapacity,
                'max_concurrent_players' => self::MAX_CONCURRENT_PLAYERS,
                'position_in_queue' => $positionInQueue,
                'estimated_wait_minutes' => $this->estimatedWaitMinutesForServerFull($positionInQueue),
                'recorded_at' => now()->toIso8601String(),
            ],
        ])->save();
    }

    protected function serverFullPositionForQueueEntry(RankedQueueEntry $queueEntry): int
    {
        $olderServerFullUsers = RankedQueueEntry::query()
            ->where('status', 'server_full')
            ->where(function ($query) use ($queueEntry): void {
                $query
                    ->where('joined_at', '<', $queueEntry->joined_at)
                    ->orWhere(function ($nestedQuery) use ($queueEntry): void {
                        $nestedQuery
                            ->where('joined_at', $queueEntry->joined_at)
                            ->where('id', '<', $queueEntry->getKey());
                    });
            })
            ->count();

        return self::MAX_CONCURRENT_PLAYERS + $olderServerFullUsers + 1;
    }

    protected function estimatedWaitMinutesForServerFull(int $positionInQueue): int
    {
        $overflowPlayers = max($positionInQueue - self::MAX_CONCURRENT_PLAYERS, 1);

        return max((int) ceil($overflowPlayers / 2), 1);
    }

    protected function matchQueueEntryIfPossible(RankedQueueEntry $queueEntry): RankedQueueEntry
    {
        $this->expireTimedOutQueuedEntries();
        $queueEntry = $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;

        if ($queueEntry->status !== 'queued') {
            return $queueEntry;
        }

        $opponentQueueEntry = RankedQueueEntry::query()
            ->where('license_category_id', $queueEntry->license_category_id)
            ->where('status', 'queued')
            ->where('user_id', '!=', $queueEntry->user_id)
            ->orderBy('joined_at')
            ->orderBy('id')
            ->first();

        if (! $opponentQueueEntry) {
            return $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;
        }

        $match = $this->createMatchFromQueueEntries($queueEntry, $opponentQueueEntry);

        return $queueEntry->fresh(['licenseCategory', 'match']) ?? $queueEntry;
    }

    protected function createMatchFromQueueEntries(
        RankedQueueEntry $queueEntry,
        RankedQueueEntry $opponentQueueEntry,
    ): RankedMatch {
        $currentUser = User::query()->findOrFail($queueEntry->user_id);
        $opponent = User::query()->findOrFail($opponentQueueEntry->user_id);
        $currentRating = $this->ensureRating($currentUser);
        $opponentRating = $this->ensureRating($opponent);
        $category = LicenseCategory::query()->findOrFail($queueEntry->license_category_id);
        $matchedAt = now();

        $match = RankedMatch::create([
            'public_id' => (string) Str::uuid(),
            'license_category_id' => $category->getKey(),
            'status' => 'matched',
            'api_version' => '1.0',
            'duration_seconds' => 90,
            'total_questions' => 40,
            'matched_at' => $matchedAt,
            'countdown_seconds' => self::MATCH_READY_COUNTDOWN_SECONDS,
            'payload' => [
                'queue_channel_pattern' => 'queue:{user_id}',
                'match_channel_pattern' => 'match:{match_id}',
                'question_ids' => $this->buildMatchQuestionIds($category),
                'heartbeat_seconds' => self::HEARTBEAT_SECONDS,
                'disconnect_timeout_seconds' => self::DISCONNECT_TIMEOUT_SECONDS,
                'reconnect_grace_seconds' => self::RECONNECT_GRACE_SECONDS,
                'ready_countdown_seconds' => self::MATCH_READY_COUNTDOWN_SECONDS,
            ],
        ]);

        $match->forceFill([
            'total_questions' => count($match->payload['question_ids'] ?? []),
        ])->save();

        RankedMatchPlayer::create([
            'ranked_match_id' => $match->getKey(),
            'user_id' => $opponent->getKey(),
            'slot' => 'player1',
            'status' => 'matched',
            'presence_state' => 'online',
            'username_snapshot' => $opponent->name,
            'elo_before' => $opponentRating->rating,
            'last_seen_at' => $matchedAt,
        ]);

        RankedMatchPlayer::create([
            'ranked_match_id' => $match->getKey(),
            'user_id' => $currentUser->getKey(),
            'slot' => 'player2',
            'status' => 'matched',
            'presence_state' => 'online',
            'username_snapshot' => $currentUser->name,
            'elo_before' => $currentRating->rating,
            'last_seen_at' => $matchedAt,
        ]);

        $this->presenceStore->touchMany(
            $match,
            [$opponent->getKey(), $currentUser->getKey()],
            $matchedAt,
            $this->presenceTtlSeconds(),
        );

        $this->appendMatchEvent(
            $match,
            'queue.matched',
            [
                'starting_in_seconds' => 0,
            ],
            $matchedAt,
            null,
            'queue.matched',
        );

        $opponentQueueEntry->forceFill([
            'ranked_match_id' => $match->getKey(),
            'status' => 'matched',
            'matched_at' => $matchedAt,
        ])->save();

        $queueEntry->forceFill([
            'ranked_match_id' => $match->getKey(),
            'status' => 'matched',
            'matched_at' => $matchedAt,
        ])->save();

        return $this->freshMatch($match) ?? $match;
    }

    protected function defaultMatchRelations(): array
    {
        return [
            'licenseCategory',
            'players',
            'players.user',
            'answers',
            'answers.question',
            'answers.user',
            'queueEntries',
            'events',
        ];
    }

    protected function findActiveMatchForUser(User $user): ?RankedMatch
    {
        return RankedMatch::query()
            ->with($this->defaultMatchRelations())
            ->whereIn('status', ['matched', 'in_progress'])
            ->whereHas('players', fn ($query) => $query->where('user_id', $user->getKey()))
            ->latest('id')
            ->first();
    }

    protected function syncLatestActiveMatchForUser(User $user): ?RankedMatch
    {
        $match = $this->findActiveMatchForUser($user);

        if (! $match) {
            return null;
        }

        return $this->syncMatchPresence($match);
    }

    protected function syncMatchPresence(RankedMatch $rankedMatch): RankedMatch
    {
        return $this->runWithSqliteBusyRetry(function () use ($rankedMatch): RankedMatch {
            $match = RankedMatch::query()
                ->with($this->defaultMatchRelations())
                ->whereKey($rankedMatch->getKey())
                ->firstOrFail();

            $match = $this->applyPresenceTransitions($match, now());

            return $this->freshMatch($match) ?? $match;
        });
    }

    protected function applyPresenceTransitions(RankedMatch $match, Carbon $now): RankedMatch
    {
        $match->loadMissing($this->defaultMatchRelations());

        if (! $this->isActiveMatch($match)) {
            return $match;
        }

        $match = $this->startMatchIfCountdownElapsed($match, $now);

        if (! $this->isActiveMatch($match)) {
            return $match;
        }

        $match = $this->finishExpiredMatchIfNeeded($match, $now);

        if (! $this->isActiveMatch($match)) {
            return $match;
        }

        foreach ($match->players as $player) {
            if ($player->presence_state === 'disconnected') {
                continue;
            }

            $lastSeenAt = $this->presenceReferenceTime($match, $player);
            $disconnectThreshold = $lastSeenAt->copy()->addSeconds(self::DISCONNECT_TIMEOUT_SECONDS);

            if ($now->greaterThan($disconnectThreshold)) {
                $player->forceFill([
                    'presence_state' => 'disconnected',
                    'disconnected_at' => $disconnectThreshold,
                    'reconnect_deadline_at' => $disconnectThreshold->copy()->addSeconds(self::RECONNECT_GRACE_SECONDS),
                ])->save();

                $this->appendMatchEvent(
                    $match,
                    'match.opponent_disconnected',
                    [
                        'disconnected_at' => $disconnectThreshold->toIso8601String(),
                        'reconnect_deadline_at' => $disconnectThreshold->copy()->addSeconds(self::RECONNECT_GRACE_SECONDS)->toIso8601String(),
                    ],
                    $disconnectThreshold,
                    $player->user_id,
                    'match.opponent_disconnected.user_'.$player->user_id.'.'.$disconnectThreshold->getTimestamp(),
                );
            }
        }

        $match = $this->freshMatch($match) ?? $match;

        if (! $this->isActiveMatch($match)) {
            return $match;
        }

        $expiredPlayers = $match->players
            ->filter(
                fn (RankedMatchPlayer $player) => $player->presence_state === 'disconnected'
                    && $player->reconnect_deadline_at !== null
                    && $now->greaterThan($player->reconnect_deadline_at)
            )
            ->values();

        if ($expiredPlayers->isEmpty()) {
            return $match;
        }

        if ($expiredPlayers->count() > 1) {
            $this->abandonMatchWithoutWinner($match, 'both_players_inactive', $now);

            return $this->freshMatch($match) ?? $match;
        }

        $loser = $expiredPlayers->first();
        $winner = $match->players->first(fn (RankedMatchPlayer $player) => ! $player->is($loser));

        if ($winner) {
            $this->abandonMatchAsWalkover($match, $winner, $loser, 'opponent_disconnect_no_return', $now);
        }

        return $this->freshMatch($match) ?? $match;
    }

    protected function markPlayerOnline(RankedMatch $match, RankedMatchPlayer $player, Carbon $seenAt): void
    {
        $wasDisconnected = $player->presence_state === 'disconnected';

        $this->presenceStore->touch(
            $match,
            $player->user_id,
            $seenAt,
            $this->presenceTtlSeconds(),
        );

        $player->forceFill([
            'presence_state' => 'online',
            'last_seen_at' => $seenAt,
            'disconnected_at' => null,
            'reconnect_deadline_at' => null,
        ])->save();

        if ($wasDisconnected) {
            $this->appendMatchEvent(
                $match,
                'match.opponent_reconnected',
                [
                    'reconnected_at' => $seenAt->toIso8601String(),
                ],
                $seenAt,
                $player->user_id,
                'match.opponent_reconnected.user_'.$player->user_id.'.'.$seenAt->getTimestamp(),
            );
        }
    }

    protected function presenceReferenceTime(RankedMatch $match, RankedMatchPlayer $player): Carbon
    {
        $cachedLastSeenAt = $this->presenceStore->lastSeenAt($match, $player->user_id);

        if ($cachedLastSeenAt) {
            return $cachedLastSeenAt;
        }

        return $player->last_seen_at
            ?? $match->started_at
            ?? $match->matched_at
            ?? $player->created_at
            ?? $match->created_at
            ?? now();
    }

    protected function abandonMatchAsWalkover(
        RankedMatch $match,
        RankedMatchPlayer $winner,
        RankedMatchPlayer $loser,
        string $reason,
        Carbon $resolvedAt,
    ): void {
        $match->loadMissing('players', 'players.user', 'queueEntries');

        $winnerRating = $this->ensureRating($winner->user);
        $loserRating = $this->ensureRating($loser->user);

        [$winnerNewRating, $loserNewRating] = $this->calculateEloPair(
            $winnerRating->rating,
            $loserRating->rating,
            1.0,
            0.0,
        );

        $this->applyRatingResult($winnerRating, $winnerNewRating, 1.0);
        $this->applyRatingResult($loserRating, $loserNewRating, 0.0);

        $winner->forceFill([
            'status' => 'finished',
            'elo_after' => $winnerNewRating,
            'elo_change' => $winnerNewRating - $winner->elo_before,
        ])->save();

        $loser->forceFill([
            'status' => 'abandoned',
            'elo_after' => $loserNewRating,
            'elo_change' => $loserNewRating - $loser->elo_before,
        ])->save();

        $match->forceFill([
            'status' => 'abandoned',
            'abandoned_at' => $resolvedAt,
            'reason' => $reason,
            'payload' => [
                ...($match->payload ?? []),
                'winner_user_id' => $winner->user_id,
                'loser_user_id' => $loser->user_id,
                'abandoned_user_id' => $loser->user_id,
            ],
        ])->save();

        $this->appendMatchEvent(
            $match,
            'match.abandoned',
            [
                'reason' => $reason,
            ],
            $resolvedAt,
            $loser->user_id,
            'match.abandoned',
        );

        $match->queueEntries()->update([
            'status' => 'completed',
            'left_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $this->clearMatchPresence($match);
    }

    protected function abandonMatchWithoutWinner(RankedMatch $match, string $reason, Carbon $resolvedAt): void
    {
        $match->loadMissing('players', 'queueEntries');

        foreach ($match->players as $player) {
            $player->forceFill([
                'status' => 'abandoned',
            ])->save();
        }

        $match->forceFill([
            'status' => 'abandoned',
            'abandoned_at' => $resolvedAt,
            'reason' => $reason,
            'payload' => [
                ...($match->payload ?? []),
                'winner_user_id' => null,
                'loser_user_id' => null,
            ],
        ])->save();

        $this->appendMatchEvent(
            $match,
            'match.abandoned',
            [
                'reason' => $reason,
            ],
            $resolvedAt,
            null,
            'match.abandoned',
        );

        $match->queueEntries()->update([
            'status' => 'completed',
            'left_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $this->clearMatchPresence($match);
    }

    protected function isActiveMatch(RankedMatch $match): bool
    {
        return in_array($match->status, ['matched', 'in_progress'], true);
    }

    protected function startCountdownIfPlayersReady(RankedMatch $match, Carbon $resolvedAt): void
    {
        $match->loadMissing('players');

        if ($match->status !== 'matched' || $match->countdown_started_at !== null) {
            return;
        }

        $playersReady = $match->players->count() >= 2
            && $match->players->every(fn (RankedMatchPlayer $player) => $player->ready_at !== null);

        if (! $playersReady) {
            return;
        }

        $countdownSeconds = max((int) ($match->countdown_seconds ?? self::MATCH_READY_COUNTDOWN_SECONDS), 1);
        $startsAt = $resolvedAt->copy()->addSeconds($countdownSeconds);

        $match->forceFill([
            'countdown_started_at' => $resolvedAt,
            'countdown_seconds' => $countdownSeconds,
        ])->save();

        $this->appendMatchEvent(
            $match,
            'match.countdown_started',
            [
                'countdown_started_at' => $resolvedAt->toIso8601String(),
                'countdown_seconds' => $countdownSeconds,
                'starts_at' => $startsAt->toIso8601String(),
            ],
            $resolvedAt,
            null,
            'match.countdown_started',
        );
    }

    protected function startMatchIfCountdownElapsed(RankedMatch $match, Carbon $now): RankedMatch
    {
        if (
            $match->status !== 'matched'
            || ! $match->countdown_started_at
            || (int) ($match->countdown_seconds ?? 0) < 1
        ) {
            return $match;
        }

        $startsAt = $match->countdown_started_at
            ->copy()
            ->addSeconds(max((int) $match->countdown_seconds, 1));

        if ($now->lessThan($startsAt)) {
            return $match;
        }

        $match->forceFill([
            'status' => 'in_progress',
            'started_at' => $startsAt,
        ])->save();

        $match->players->each(function (RankedMatchPlayer $player) {
            $player->forceFill([
                'status' => 'in_progress',
            ])->save();
        });

        $this->appendMatchEvent(
            $match,
            'match.started',
            [],
            $startsAt,
            null,
            'match.started',
        );

        return $this->freshMatch($match) ?? $match;
    }

    protected function activeConcurrentUsersCount(): int
    {
        return RankedQueueEntry::query()
            ->whereIn('status', ['queued', 'matched'])
            ->distinct()
            ->count('user_id');
    }

    protected function expireTimedOutQueuedEntries(?Carbon $resolvedAt = null): void
    {
        $resolvedAt ??= now();
        $timeoutThreshold = $resolvedAt->copy()->subSeconds(self::MATCHMAKING_TIMEOUT_SECONDS);

        RankedQueueEntry::query()
            ->where('status', 'queued')
            ->where('joined_at', '<=', $timeoutThreshold)
            ->update([
                'status' => 'cancelled',
                'left_at' => $resolvedAt,
                'updated_at' => $resolvedAt,
            ]);
    }

    protected function waitingServerFullUsersCount(): int
    {
        return RankedQueueEntry::query()
            ->where('status', 'server_full')
            ->distinct()
            ->count('user_id');
    }

    protected function isConcurrentCapacityFull(): bool
    {
        return $this->activeConcurrentUsersCount() >= self::MAX_CONCURRENT_PLAYERS;
    }

    protected function freshMatch(RankedMatch $match): ?RankedMatch
    {
        return $match->fresh($this->defaultMatchRelations());
    }

    protected function appendMatchEvent(
        RankedMatch $match,
        string $eventName,
        array $payload = [],
        ?Carbon $occurredAt = null,
        ?int $actorUserId = null,
        ?string $fingerprint = null,
    ): void {
        $occurredAt ??= now();
        $eventId = sprintf(
            'evt_%s_match_%s_%s',
            str_replace('.', '_', $eventName),
            $match->public_id,
            $fingerprint ?? Str::uuid()->toString(),
        );

        RankedMatchEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'ranked_match_id' => $match->getKey(),
                'actor_user_id' => $actorUserId,
                'event_name' => $eventName,
                'payload' => $payload,
                'occurred_at' => $occurredAt,
            ],
        );
    }

    protected function clearMatchPresence(RankedMatch $match): void
    {
        $match->loadMissing('players');

        $this->presenceStore->forgetMany(
            $match,
            $match->players->pluck('user_id')->all(),
        );
    }

    protected function presenceTtlSeconds(): int
    {
        return max(
            (int) config(
                'ranked.presence_ttl_seconds',
                self::DISCONNECT_TIMEOUT_SECONDS + self::RECONNECT_GRACE_SECONDS + self::HEARTBEAT_SECONDS
            ),
            self::DISCONNECT_TIMEOUT_SECONDS + self::RECONNECT_GRACE_SECONDS + 1,
        );
    }

    protected function runWithSqliteBusyRetry(callable $callback): mixed
    {
        $attempts = 5;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return DB::transaction($callback);
            } catch (QueryException $exception) {
                $message = strtolower($exception->getMessage());

                if (! str_contains($message, 'database is locked') || $attempt === $attempts) {
                    throw $exception;
                }

                usleep(50000 * $attempt);
            }
        }

        throw new \RuntimeException('Unexpected SQLite retry flow exit.');
    }
}
