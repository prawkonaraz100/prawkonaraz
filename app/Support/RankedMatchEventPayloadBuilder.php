<?php

namespace App\Support;

use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedMatchEvent;
use App\Models\RankedMatchPlayer;
use App\Models\User;

class RankedMatchEventPayloadBuilder
{
    protected const QUESTION_REVEAL_INTERVAL_SECONDS = 2;

    public function __construct(
        protected RankedMatchPayloadBuilder $rankedMatchPayloadBuilder,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function eventsForUser(RankedMatch $match, User $currentUser, ?string $afterEventId = null): array
    {
        $match->loadMissing([
            'licenseCategory',
            'players',
            'players.user',
            'answers',
            'events',
        ]);

        $events = $match->events;

        if ($afterEventId) {
            $afterIndex = $events->search(
                fn (RankedMatchEvent $event) => $event->event_id === $afterEventId
            );

            if ($afterIndex !== false) {
                $events = $events->slice($afterIndex + 1)->values();
            }
        }

        return $events
            ->map(fn (RankedMatchEvent $event) => $this->transformEvent($match, $event, $currentUser))
            ->filter()
            ->values()
            ->all();
    }

    public function firstEventForUser(RankedMatch $match, User $currentUser, string $eventName): ?array
    {
        $match->loadMissing([
            'licenseCategory',
            'players',
            'players.user',
            'answers',
            'events',
        ]);

        $event = $match->events->firstWhere('event_name', $eventName);

        if (! $event instanceof RankedMatchEvent) {
            return null;
        }

        return $this->transformEvent($match, $event, $currentUser);
    }

    protected function transformEvent(RankedMatch $match, RankedMatchEvent $event, User $currentUser): ?array
    {
        return match ($event->event_name) {
            'queue.matched' => $this->queueMatchedEvent($match, $event, $currentUser),
            'match.countdown_started' => $this->matchCountdownStartedEvent($match, $event),
            'match.started' => $this->matchStartedEvent($match, $event),
            'match.opponent_answered' => $this->opponentAnsweredEvent($match, $event, $currentUser),
            'match.opponent_disconnected' => $this->opponentPresenceEvent($match, $event, $currentUser, 'match.opponent_disconnected'),
            'match.opponent_reconnected' => $this->opponentPresenceEvent($match, $event, $currentUser, 'match.opponent_reconnected'),
            'match.finished' => $this->finalMatchEvent($match, $event, $currentUser, 'match.finished'),
            'match.abandoned' => $this->finalMatchEvent($match, $event, $currentUser, 'match.abandoned'),
            default => null,
        };
    }

    protected function queueMatchedEvent(RankedMatch $match, RankedMatchEvent $event, User $currentUser): array
    {
        $opponent = $match->players->first(fn (RankedMatchPlayer $player) => $player->user_id !== $currentUser->getKey());

        return $this->baseEvent($match, $event, [
            'user_id' => $currentUser->getKey(),
            'match_id' => $match->public_id,
            'opponent' => $opponent ? [
                'user_id' => $opponent->user_id,
                'username' => $opponent->username_snapshot,
                'elo' => $opponent->elo_before,
            ] : null,
            'starting_in_seconds' => data_get($event->payload, 'starting_in_seconds', 0),
        ]);
    }

    protected function matchCountdownStartedEvent(RankedMatch $match, RankedMatchEvent $event): array
    {
        return $this->baseEvent($match, $event, [
            'match_id' => $match->public_id,
            'countdown_started_at' => data_get($event->payload, 'countdown_started_at'),
            'countdown_seconds' => data_get($event->payload, 'countdown_seconds'),
            'starts_at' => data_get($event->payload, 'starts_at'),
        ]);
    }

    protected function matchStartedEvent(RankedMatch $match, RankedMatchEvent $event): array
    {
        $playersBySlot = $match->players->keyBy('slot');

        return $this->baseEvent($match, $event, [
            'match_id' => $match->public_id,
            'status' => 'in_progress',
            'started_at' => $match->started_at?->toIso8601String(),
            'duration_seconds' => $match->duration_seconds,
            'total_questions' => $match->total_questions,
            'player1' => $this->matchParticipant($playersBySlot->get('player1')),
            'player2' => $this->matchParticipant($playersBySlot->get('player2')),
            'questions' => $this->startedQuestions($match),
        ]);
    }

    protected function opponentAnsweredEvent(RankedMatch $match, RankedMatchEvent $event, User $currentUser): ?array
    {
        if ($event->actor_user_id === $currentUser->getKey()) {
            return null;
        }

        $opponent = $match->players->firstWhere('user_id', $event->actor_user_id);

        if (! $opponent) {
            return null;
        }

        $opponentAnswers = $match->answers
            ->where('user_id', $opponent->user_id)
            ->values();
        $questionId = (int) data_get($event->payload, 'question_id');
        $questionNumber = (int) data_get($event->payload, 'question_number');
        $lastAnswer = $opponentAnswers->first(
            fn ($answer) => (int) $answer->question_id === $questionId
        );
        $correctAnswers = $opponentAnswers->where('is_correct', true)->count();
        $totalAnswered = $opponentAnswers->count();

        return $this->baseEvent($match, $event, [
            'match_id' => $match->public_id,
            'opponent' => [
                'user_id' => $opponent->user_id,
                'username' => $opponent->username_snapshot,
            ],
            'question_id' => $questionId,
            'question_number' => $questionNumber,
            'current_question' => $questionNumber,
            'total_answered' => $totalAnswered,
            'correct_answers' => $correctAnswers,
            'opponent_score' => [
                'correct_answers' => $correctAnswers,
                'total_answered' => $totalAnswered,
                'points' => $correctAnswers,
            ],
            'last_answer' => [
                'question_id' => $questionId,
                'answer_correct' => $lastAnswer?->is_correct,
                'response_time_ms' => $lastAnswer?->response_time_ms,
            ],
            'answered_at' => $lastAnswer?->answered_at?->toIso8601String() ?? $event->occurred_at?->toIso8601String(),
        ]);
    }

    protected function opponentPresenceEvent(
        RankedMatch $match,
        RankedMatchEvent $event,
        User $currentUser,
        string $eventName,
    ): ?array {
        if ($event->actor_user_id === $currentUser->getKey()) {
            return null;
        }

        $opponent = $match->players->firstWhere('user_id', $event->actor_user_id);

        if (! $opponent) {
            return null;
        }

        $currentQuestion = min(max($opponent->total_answered + 1, 1), max($match->total_questions, 1));
        $reconnectDeadline = data_get($event->payload, 'reconnect_deadline_at');

        return $this->baseEvent($match, $event, [
            'match_id' => $match->public_id,
            'opponent' => [
                'user_id' => $opponent->user_id,
                'username' => $opponent->username_snapshot,
            ],
            'disconnected_at' => data_get($event->payload, 'disconnected_at'),
            'reconnect_deadline_at' => $reconnectDeadline,
            'reconnect_deadline' => $reconnectDeadline,
            'timeout_seconds' => $eventName === 'match.opponent_disconnected'
                ? RankedMatchService::RECONNECT_GRACE_SECONDS
                : null,
            'reconnected_at' => data_get($event->payload, 'reconnected_at'),
            'opponent_current_state' => $eventName === 'match.opponent_reconnected'
                ? [
                    'current_question' => $currentQuestion,
                    'correct_answers' => $opponent->correct_answers,
                    'points' => $opponent->points,
                    'total_answered' => $opponent->total_answered,
                ]
                : null,
        ], $eventName);
    }

    protected function finalMatchEvent(
        RankedMatch $match,
        RankedMatchEvent $event,
        User $currentUser,
        string $eventName,
    ): array
    {
        $playersBySlot = $match->players->keyBy('slot');
        $playerOne = $playersBySlot->get('player1');
        $playerTwo = $playersBySlot->get('player2');
        $currentPlayer = $match->players->firstWhere('user_id', $currentUser->getKey());
        $opponentPlayer = $match->players->first(
            fn (RankedMatchPlayer $player) => $player->user_id !== $currentUser->getKey()
        );
        $winner = $match->players->firstWhere('user_id', data_get($match->payload, 'winner_user_id'));
        $loser = $match->players->firstWhere('user_id', data_get($match->payload, 'loser_user_id'));
        $outcome = $this->rankedMatchPayloadBuilder->outcomeForUser($match, $currentUser);

        return $this->baseEvent($match, $event, [
            'match_id' => $match->public_id,
            'status' => $eventName === 'match.finished' ? 'finished' : 'abandoned',
            'finished_at' => $match->finished_at?->toIso8601String(),
            'abandoned_at' => $match->abandoned_at?->toIso8601String(),
            'total_questions' => $match->total_questions,
            'category' => $match->licenseCategory
                ? $this->rankedMatchPayloadBuilder->category($match->licenseCategory)
                : null,
            'reason' => $match->reason,
            'outcome' => $outcome,
            'result_label' => $this->rankedMatchPayloadBuilder->outcomeLabel($outcome),
            'winner_user_id' => $winner?->user_id,
            'loser_user_id' => $loser?->user_id,
            'winner' => $eventName === 'match.finished'
                ? $this->playerIdentity($winner)
                : null,
            'winner_by_default' => $eventName === 'match.abandoned'
                ? $this->playerIdentity($winner)
                : null,
            'loser' => $this->playerIdentity($loser),
            'result' => $eventName === 'match.finished'
                ? $this->finishedResult($winner, $playerOne, $playerTwo, $match)
                : null,
            'scores' => [
                'player1' => $this->playerScore($playerOne),
                'player2' => $this->playerScore($playerTwo),
            ],
            'current_user' => $this->playerScore($currentPlayer),
            'opponent' => $this->playerScore($opponentPlayer),
            'elo_applied' => $winner !== null || $loser !== null,
            'elo_changes' => [
                'player1' => $this->playerEloChange($playerOne),
                'player2' => $this->playerEloChange($playerTwo),
            ],
            'answer_breakdown' => $this->rankedMatchPayloadBuilder->answerBreakdown($match),
        ], $eventName);
    }

    protected function playerIdentity(?RankedMatchPlayer $player): ?array
    {
        if (! $player) {
            return null;
        }

        return [
            'user_id' => $player->user_id,
            'username' => $player->username_snapshot,
        ];
    }

    protected function matchParticipant(?RankedMatchPlayer $player): ?array
    {
        if (! $player) {
            return null;
        }

        return [
            'user_id' => $player->user_id,
            'username' => $player->username_snapshot,
            'elo' => $player->elo_before,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function startedQuestions(RankedMatch $match): array
    {
        $questionIds = collect($match->payload['question_ids'] ?? [])
            ->map(fn ($questionId) => (int) $questionId)
            ->filter()
            ->values();

        if ($questionIds->isEmpty()) {
            return [];
        }

        $questionsById = Question::query()
            ->whereIn('id', $questionIds->all())
            ->get(['id', 'prompt'])
            ->keyBy('id');

        $startedAt = $match->started_at ?? $match->matched_at ?? now();

        return $questionIds
            ->map(function (int $questionId, int $index) use ($questionsById, $startedAt): array {
                $question = $questionsById->get($questionId);
                $revealedAt = $startedAt->copy()
                    ->addSeconds($index * self::QUESTION_REVEAL_INTERVAL_SECONDS)
                    ->toIso8601String();

                return [
                    'question_id' => $questionId,
                    'text' => $question?->prompt,
                    'question_text' => $question?->prompt,
                    'question_number' => $index + 1,
                    'revealed_at' => $revealedAt,
                ];
            })
            ->values()
            ->all();
    }

    protected function playerScore(?RankedMatchPlayer $player): ?array
    {
        if (! $player) {
            return null;
        }

        return [
            'user_id' => $player->user_id,
            'username' => $player->username_snapshot,
            'correct_answers' => $player->correct_answers,
            'total_answered' => $player->total_answered,
            'points' => $player->points,
            'sum_response_time_ms' => $player->sum_response_time_ms,
            'elo_before' => $player->elo_before,
            'elo_after' => $player->elo_after,
            'elo_change' => $player->elo_change,
        ];
    }

    protected function playerEloChange(?RankedMatchPlayer $player): ?array
    {
        if (! $player) {
            return null;
        }

        return [
            'elo_before' => $player->elo_before,
            'elo_after' => $player->elo_after,
            'elo_change' => $player->elo_change,
        ];
    }

    protected function finishedResult(
        ?RankedMatchPlayer $winner,
        ?RankedMatchPlayer $playerOne,
        ?RankedMatchPlayer $playerTwo,
        RankedMatch $match,
    ): string {
        if ($match->reason === 'draw' || ! $winner) {
            return 'draw';
        }

        if ($playerOne && $winner->is($playerOne)) {
            return 'player1_won';
        }

        if ($playerTwo && $winner->is($playerTwo)) {
            return 'player2_won';
        }

        return 'draw';
    }

    protected function baseEvent(
        RankedMatch $match,
        RankedMatchEvent $event,
        array $data,
        ?string $eventName = null,
    ): array {
        return [
            'event' => $eventName ?? $event->event_name,
            'id' => $event->event_id,
            'api_version' => $match->api_version,
            'timestamp' => $event->occurred_at?->toIso8601String(),
            'data' => $data,
        ];
    }
}
