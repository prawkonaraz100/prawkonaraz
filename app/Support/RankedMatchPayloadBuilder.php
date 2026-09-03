<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\RankedMatch;
use App\Models\RankedMatchAnswer;
use App\Models\RankedMatchPlayer;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\User;
use Illuminate\Support\Str;

class RankedMatchPayloadBuilder
{
    public function __construct(
        protected StudySessionApiPayloadBuilder $studySessionApiPayloadBuilder,
        protected RankedMatchPresenceStore $presenceStore,
    ) {}

    public function rating(RankedPlayerRating $rating): array
    {
        return [
            'user_id' => $rating->user_id,
            'rating' => $rating->rating,
            'peak_rating' => $rating->peak_rating,
            'matches_played' => $rating->matches_played,
            'wins' => $rating->wins,
            'losses' => $rating->losses,
            'draws' => $rating->draws,
            'current_streak' => $rating->current_streak,
            'best_streak' => $rating->best_streak,
            'updated_at' => $rating->updated_at?->toIso8601String(),
        ];
    }

    public function queueEntry(?RankedQueueEntry $queueEntry): ?array
    {
        if (! $queueEntry) {
            return null;
        }

        return [
            'id' => $queueEntry->getKey(),
            'status' => $queueEntry->status,
            'joined_at' => $queueEntry->joined_at?->toIso8601String(),
            'matched_at' => $queueEntry->matched_at?->toIso8601String(),
            'left_at' => $queueEntry->left_at?->toIso8601String(),
            'category' => $queueEntry->licenseCategory
                ? $this->category($queueEntry->licenseCategory)
                : null,
            'match_public_id' => $queueEntry->match?->public_id,
            'server_full' => $queueEntry->status === 'server_full'
                ? [
                    'error_code' => data_get($queueEntry->payload, 'error_code'),
                    'message' => data_get($queueEntry->payload, 'message'),
                    'current_capacity' => data_get($queueEntry->payload, 'current_capacity'),
                    'max_concurrent_players' => data_get($queueEntry->payload, 'max_concurrent_players'),
                    'position_in_queue' => data_get($queueEntry->payload, 'position_in_queue'),
                    'estimated_wait_minutes' => data_get($queueEntry->payload, 'estimated_wait_minutes'),
                    'recorded_at' => data_get($queueEntry->payload, 'recorded_at'),
                    'resumed_at' => data_get($queueEntry->payload, 'resumed_at'),
                ]
                : null,
        ];
    }

    /**
     * @param  array{current_players:int, max_players:int, available_slots:int, waiting_users:int, is_full:bool}  $capacity
     */
    public function capacity(array $capacity): array
    {
        return [
            'current_players' => $capacity['current_players'],
            'max_players' => $capacity['max_players'],
            'available_slots' => $capacity['available_slots'],
            'waiting_users' => $capacity['waiting_users'],
            'is_full' => $capacity['is_full'],
        ];
    }

    public function overviewState(?RankedMatch $match, ?RankedQueueEntry $queueEntry, User $currentUser): string
    {
        if ($match) {
            return $this->matchState($match, $currentUser);
        }

        return $queueEntry?->status ?? 'idle';
    }

    public function matchState(RankedMatch $match, User $currentUser): string
    {
        $match->loadMissing('players');

        if (! in_array($match->status, ['matched', 'in_progress'], true)) {
            return $match->status;
        }

        $opponent = $match->players->first(fn (RankedMatchPlayer $player) => $player->user_id !== $currentUser->getKey());

        if ($opponent?->presence_state === 'disconnected') {
            return 'opponent_disconnected';
        }

        return $match->status;
    }

    public function match(?RankedMatch $match, User $currentUser): ?array
    {
        if (! $match) {
            return null;
        }

        $match->loadMissing([
            'licenseCategory',
            'players',
            'players.user',
        ]);

        $players = $match->players
            ->map(function (RankedMatchPlayer $player) use ($currentUser): array {
                return [
                    'user_id' => $player->user_id,
                    'username' => $player->username_snapshot,
                    'slot' => $player->slot,
                    'status' => $player->status,
                    'ready_at' => $player->ready_at?->toIso8601String(),
                    'is_ready' => $player->ready_at !== null,
                    'presence_state' => $player->presence_state,
                    'elo_before' => $player->elo_before,
                    'elo_after' => $player->elo_after,
                    'elo_change' => $player->elo_change,
                    'correct_answers' => $player->correct_answers,
                    'total_answered' => $player->total_answered,
                    'points' => $player->points,
                    'sum_response_time_ms' => $player->sum_response_time_ms,
                    'is_current_user' => $player->user_id === $currentUser->getKey(),
                ];
            })
            ->values();

        $currentPlayer = $match->players->firstWhere('user_id', $currentUser->getKey());
        $opponentPlayer = $match->players->first(fn (RankedMatchPlayer $player) => $player->user_id !== $currentUser->getKey());
        $opponent = $players->firstWhere('is_current_user', false);

        return [
            'id' => $match->getKey(),
            'public_id' => $match->public_id,
            'status' => $match->status,
            'state' => $this->matchState($match, $currentUser),
            'api_version' => $match->api_version,
            'duration_seconds' => $match->duration_seconds,
            'total_questions' => $match->total_questions,
            'reason' => $match->reason,
            'matched_at' => $match->matched_at?->toIso8601String(),
            'countdown_started_at' => $match->countdown_started_at?->toIso8601String(),
            'countdown_seconds' => $match->countdown_seconds,
            'starts_at' => $match->countdown_started_at && $match->countdown_seconds
                ? $match->countdown_started_at->copy()->addSeconds(max($match->countdown_seconds, 1))->toIso8601String()
                : null,
            'started_at' => $match->started_at?->toIso8601String(),
            'finished_at' => $match->finished_at?->toIso8601String(),
            'abandoned_at' => $match->abandoned_at?->toIso8601String(),
            'category' => $this->category($match->licenseCategory),
            'channels' => [
                'queue' => sprintf('queue:%d', $currentUser->getKey()),
                'match' => sprintf('match:%s', $match->public_id),
            ],
            'players' => $players->all(),
            'opponent' => $opponent,
            'ready' => [
                'current_user_ready' => $currentPlayer?->ready_at !== null,
                'current_user_ready_at' => $currentPlayer?->ready_at?->toIso8601String(),
                'opponent_ready' => $opponentPlayer?->ready_at !== null,
                'opponent_ready_at' => $opponentPlayer?->ready_at?->toIso8601String(),
            ],
            'presence' => [
                'heartbeat_seconds' => RankedMatchService::HEARTBEAT_SECONDS,
                'disconnect_timeout_seconds' => RankedMatchService::DISCONNECT_TIMEOUT_SECONDS,
                'reconnect_grace_seconds' => RankedMatchService::RECONNECT_GRACE_SECONDS,
                'current_user' => $currentPlayer ? $this->playerPresence($match, $currentPlayer, $currentUser) : null,
                'opponent' => $opponentPlayer ? $this->playerPresence($match, $opponentPlayer, $currentUser) : null,
            ],
        ];
    }

    public function matchSummary(RankedMatch $match, User $currentUser): array
    {
        return $this->match($match, $currentUser) ?? [];
    }

    public function historyEntry(?RankedMatch $match, User $currentUser): ?array
    {
        if (! $match) {
            return null;
        }

        $match->loadMissing([
            'licenseCategory',
            'players',
            'players.user',
        ]);

        $player = $match->players->firstWhere('user_id', $currentUser->getKey());
        $playedAt = $match->finished_at ?? $match->abandoned_at ?? $match->matched_at;
        $outcome = $this->historyOutcome($match, $currentUser);

        return [
            ...($this->match($match, $currentUser) ?? []),
            'played_at' => $playedAt?->toIso8601String(),
            'outcome' => $outcome,
            'result_label' => $this->historyResultLabel($outcome),
            'current_user' => [
                'correct_answers' => $player?->correct_answers ?? 0,
                'total_answered' => $player?->total_answered ?? 0,
                'points' => $player?->points ?? 0,
                'elo_after' => $player?->elo_after,
                'elo_change' => $player?->elo_change,
            ],
        ];
    }

    public function outcomeForUser(RankedMatch $match, User $currentUser): string
    {
        return $this->historyOutcome($match, $currentUser);
    }

    public function outcomeLabel(string $outcome): string
    {
        return $this->historyResultLabel($outcome);
    }

    public function matchDetails(RankedMatch $match, User $currentUser): array
    {
        $match->loadMissing([
            'licenseCategory',
            'players',
            'players.user',
            'answers',
            'answers.question',
            'answers.user',
        ]);

        $orderedQuestions = app(RankedMatchService::class)->orderedQuestions($match);
        $answers = $match->answers;
        $answerMap = $answers
            ->where('user_id', $currentUser->getKey())
            ->keyBy('question_id');
        $isCompleted = in_array($match->status, ['finished', 'abandoned'], true);

        return [
            'match' => $this->matchSummary($match, $currentUser),
            'progress' => $this->playerProgress($match, $currentUser),
            'questions' => $orderedQuestions
                ->map(function ($question, int $index) use ($answerMap, $isCompleted): array {
                    /** @var RankedMatchAnswer|null $answer */
                    $answer = $answerMap->get($question->getKey());

                    return [
                        ...$this->studySessionApiPayloadBuilder->question($question, null, $isCompleted),
                        'question_number' => $index + 1,
                        'selected_answer' => $answer?->selected_answer ? Str::upper($answer->selected_answer) : null,
                        'is_answered' => $answer !== null,
                        'is_correct' => $isCompleted ? $answer?->is_correct : null,
                        'response_time_ms' => $answer?->response_time_ms,
                    ];
                })
                ->values()
                ->all(),
            'answer_breakdown' => $this->answerBreakdown($match),
        ];
    }

    public function playerProgress(RankedMatch $match, User $currentUser): array
    {
        $player = $match->players->firstWhere('user_id', $currentUser->getKey());
        $totalQuestions = max($match->total_questions, 1);

        return [
            'answered' => $player?->total_answered ?? 0,
            'remaining' => max($match->total_questions - ($player?->total_answered ?? 0), 0),
            'correct_answers' => $player?->correct_answers ?? 0,
            'points' => $player?->points ?? 0,
            'total' => $match->total_questions,
            'completion_percent' => round((($player?->total_answered ?? 0) / $totalQuestions) * 100, 2),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function answerBreakdown(RankedMatch $match): array
    {
        if ($match->answers->isEmpty()) {
            return [];
        }

        $playersBySlot = $match->players->keyBy('slot');
        $playerOneId = $playersBySlot->get('player1')?->user_id;
        $playerTwoId = $playersBySlot->get('player2')?->user_id;

        return $match->answers
            ->groupBy('question_id')
            ->map(function ($answers, $questionId) use ($playerOneId, $playerTwoId): array {
                $playerOneAnswer = $answers->firstWhere('user_id', $playerOneId);
                $playerTwoAnswer = $answers->firstWhere('user_id', $playerTwoId);

                return [
                    'question_id' => (int) $questionId,
                    'question_number' => $playerOneAnswer?->question_number ?? $playerTwoAnswer?->question_number,
                    'player1_correct' => $playerOneAnswer?->is_correct,
                    'player1_response_time_ms' => $playerOneAnswer?->response_time_ms,
                    'player2_correct' => $playerTwoAnswer?->is_correct,
                    'player2_response_time_ms' => $playerTwoAnswer?->response_time_ms,
                ];
            })
            ->sortBy('question_number')
            ->values()
            ->all();
    }

    public function category(LicenseCategory $category): array
    {
        return [
            'id' => $category->getKey(),
            'code' => $category->code,
            'slug' => $category->slug,
            'name' => $category->name,
            'short_name' => app(StudyContextService::class)->shortCategoryName($category),
        ];
    }

    protected function playerPresence(RankedMatch $match, RankedMatchPlayer $player, User $currentUser): array
    {
        $now = now();
        $lastSeenAt = $this->presenceStore->lastSeenAt($match, $player->user_id)
            ?? $player->last_seen_at
            ?? $match->started_at
            ?? $match->matched_at
            ?? $player->created_at
            ?? $match->created_at
            ?? $now;

        $secondsSinceLastSeen = (int) $lastSeenAt->diffInSeconds($now);
        $secondsUntilDisconnect = max(RankedMatchService::DISCONNECT_TIMEOUT_SECONDS - $secondsSinceLastSeen, 0);
        $secondsUntilForfeit = $player->reconnect_deadline_at
            ? max((int) $now->diffInSeconds($player->reconnect_deadline_at, false), 0)
            : null;

        return [
            'user_id' => $player->user_id,
            'is_current_user' => $player->user_id === $currentUser->getKey(),
            'presence_state' => $player->presence_state,
            'is_connected' => $player->presence_state !== 'disconnected',
            'last_seen_at' => $lastSeenAt?->toIso8601String(),
            'disconnected_at' => $player->disconnected_at?->toIso8601String(),
            'reconnect_deadline_at' => $player->reconnect_deadline_at?->toIso8601String(),
            'last_seen_seconds_ago' => $secondsSinceLastSeen,
            'seconds_until_disconnect' => $player->presence_state === 'disconnected' ? 0 : $secondsUntilDisconnect,
            'seconds_until_forfeit' => $secondsUntilForfeit,
        ];
    }

    protected function historyOutcome(RankedMatch $match, User $currentUser): string
    {
        $winnerUserId = data_get($match->payload, 'winner_user_id');
        $loserUserId = data_get($match->payload, 'loser_user_id');

        if ($winnerUserId !== null) {
            return (int) $winnerUserId === $currentUser->getKey() ? 'win' : 'loss';
        }

        if ($loserUserId !== null) {
            return (int) $loserUserId === $currentUser->getKey() ? 'loss' : 'win';
        }

        if ($match->reason === 'draw') {
            return 'draw';
        }

        return $match->status === 'abandoned' ? 'abandoned' : 'draw';
    }

    protected function historyResultLabel(string $outcome): string
    {
        return match ($outcome) {
            'win' => 'Wygrana',
            'loss' => 'Przegrana',
            'draw' => 'Remis',
            default => 'Przerwany mecz',
        };
    }
}
