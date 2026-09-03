<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\User;
use App\Support\RankedMatchPresenceStore;
use App\Support\RankedMatchService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

test('matched player can inspect ranked match details without seeing correct answers before finish', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);

    $response = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.show', $match->public_id));

    $response->assertOk()
        ->assertJsonPath('data.match.public_id', $match->public_id)
        ->assertJsonPath('data.match.status', 'matched')
        ->assertJsonPath('data.progress.answered', 0)
        ->assertJsonCount(3, 'data.questions')
        ->assertJsonMissingPath('data.questions.0.correct_answer');

    expect($secondUser->name)->toBe('Player Two');
});

test('missing ranked match returns ranked error envelope with match not found code', function () {
    $user = User::factory()->withPurchasedAccess()->create();

    $response = $this->actingAs($user)
        ->getJson(route('api.v1.ranked.matches.show', '00000000-0000-0000-0000-000000000000'));

    $response->assertNotFound()
        ->assertJsonPath('error.code', 'MATCH_NOT_FOUND')
        ->assertJsonPath('error_event.event', 'error')
        ->assertJsonPath('error_event.api_version', '1.0')
        ->assertJsonPath('error_event.data.error_code', 'MATCH_NOT_FOUND')
        ->assertJsonPath('error_event.data.details.match_id', '00000000-0000-0000-0000-000000000000')
        ->assertJsonPath('error_event.data.details.user_id', $user->getKey());
});

test('matched match waits for both players to confirm ready before countdown and answers', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    $firstReady = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.ready', $match->public_id));

    $firstReady->assertOk()
        ->assertJsonPath('data.match.status', 'matched')
        ->assertJsonPath('data.match.ready.current_user_ready', true)
        ->assertJsonPath('data.match.ready.opponent_ready', false)
        ->assertJsonPath('data.match.countdown_started_at', null);

    $blockedAnswer = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 900,
        ]);

    $blockedAnswer->assertStatus(422)
        ->assertJsonValidationErrors(['match_not_started'])
        ->assertJsonPath('error.code', 'MATCH_NOT_STARTED')
        ->assertJsonPath('error_event.data.error_code', 'MATCH_NOT_STARTED')
        ->assertJsonPath('error_event.data.details.match_id', $match->public_id)
        ->assertJsonPath('error_event.data.details.user_id', $firstUser->getKey());

    $secondReady = $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.ready', $match->public_id));

    $secondReady->assertOk()
        ->assertJsonPath('data.match.status', 'matched')
        ->assertJsonPath('data.match.ready.current_user_ready', true)
        ->assertJsonPath('data.match.ready.opponent_ready', true)
        ->assertJsonPath('data.match.countdown_seconds', RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS);

    expect($secondReady->json('data.match.countdown_started_at'))->not->toBeNull();
    expect($secondReady->json('data.match.starts_at'))->not->toBeNull();

    $events = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $events->assertOk()
        ->assertJsonPath('data.events.1.event', 'match.countdown_started')
        ->assertJsonPath('data.events.1.data.countdown_seconds', RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS);

    $this->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'in_progress')
        ->assertJsonPath('data.match.state', 'in_progress');
});

test('ranked match answers are idempotent for the same player and question', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser);

    $questionId = collect($match->payload['question_ids'] ?? [])->first();

    $firstResponse = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionId,
            'user_answer' => 'a',
            'response_time_ms' => 1300,
        ]);

    $firstResponse->assertOk()
        ->assertJsonPath('data.accepted', true)
        ->assertJsonPath('data.answer.question_id', $questionId)
        ->assertJsonPath('data.progress.answered', 1);

    $secondResponse = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionId,
            'user_answer' => 'a',
            'response_time_ms' => 1300,
        ]);

    $secondResponse->assertOk()
        ->assertJsonPath('data.accepted', false)
        ->assertJsonPath('data.progress.answered', 1);
});

test('ranked match finishes when both players answer the full question set and updates ratings', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);
    $finalAnswerResponse = finishRankedMatchFixture($this, $match, $questions, $firstUser, $secondUser);

    $finalAnswerResponse->assertOk()
        ->assertJsonPath('data.match.status', 'finished')
        ->assertJsonPath('data.progress.answered', 3);

    $match->refresh();

    expect($match->status)->toBe('finished');
    expect($match->reason)->toBe('more_correct_answers');

    $firstPlayer = $match->players()->where('user_id', $firstUser->getKey())->firstOrFail();
    $secondPlayer = $match->players()->where('user_id', $secondUser->getKey())->firstOrFail();

    expect($firstPlayer->correct_answers)->toBe(3);
    expect($secondPlayer->correct_answers)->toBe(1);
    expect($firstPlayer->elo_after)->not->toBeNull();
    expect($secondPlayer->elo_after)->not->toBeNull();

    $firstRating = RankedPlayerRating::query()->findOrFail($firstUser->getKey());
    $secondRating = RankedPlayerRating::query()->findOrFail($secondUser->getKey());

    expect($firstRating->matches_played)->toBe(1);
    expect($firstRating->wins)->toBe(1);
    expect($secondRating->matches_played)->toBe(1);
    expect($secondRating->losses)->toBe(1);
    expect(
        RankedQueueEntry::query()
            ->where('ranked_match_id', $match->getKey())
            ->where('status', 'completed')
            ->count()
    )->toBe(2);

    $matchDetails = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.show', $match->public_id));

    $matchDetails->assertOk()
        ->assertJsonPath('data.match.status', 'finished');

    $payloadQuestions = collect($matchDetails->json('data.questions'));
    $questionById = $questions->keyBy('id');
    $firstPayloadQuestion = $payloadQuestions->first();

    expect($firstPayloadQuestion)->not->toBeNull();
    expect($firstPayloadQuestion['is_answered'])->toBeTrue();
    expect($firstPayloadQuestion['correct_answer'])->toBe(
        strtoupper($questionById->get($firstPayloadQuestion['id'])->correct_answer)
    );
});

test('in-progress ranked match auto-finishes on duration expiry and rejects late answers', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk()
        ->assertJsonPath('data.match.status', 'in_progress')
        ->assertJsonPath('data.progress.answered', 1);

    $this->travel(91)->seconds();

    $lateAnswer = $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[1]->getKey(),
            'user_answer' => $questions[1]->correct_answer,
            'response_time_ms' => 1000,
        ]);

    $lateAnswer->assertStatus(422)
        ->assertJsonValidationErrors(['match'])
        ->assertJsonPath('error.code', 'MATCH_FINISHED')
        ->assertJsonPath('error_event.event', 'error')
        ->assertJsonPath('error_event.api_version', '1.0')
        ->assertJsonPath('error_event.data.error_code', 'MATCH_FINISHED')
        ->assertJsonPath('error_event.data.details.match_id', $match->public_id)
        ->assertJsonPath('error_event.data.details.user_id', $secondUser->getKey());

    $match->refresh();

    expect($match->status)->toBe('finished');
    expect($match->reason)->toBe('more_correct_answers');
    expect($match->finished_at)->not->toBeNull();

    $firstPlayer = $match->players()->where('user_id', $firstUser->getKey())->firstOrFail();
    $secondPlayer = $match->players()->where('user_id', $secondUser->getKey())->firstOrFail();

    expect($firstPlayer->status)->toBe('finished');
    expect($secondPlayer->status)->toBe('finished');
    expect($firstPlayer->correct_answers)->toBe(1);
    expect($secondPlayer->correct_answers)->toBe(0);

    $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null)
        ->assertJsonPath('data.recent_match.public_id', $match->public_id)
        ->assertJsonPath('data.recent_match.status', 'finished')
        ->assertJsonPath('data.recent_match.reason', 'more_correct_answers');

    $events = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $events->assertOk()
        ->assertJsonPath('data.events.3.event', 'match.finished')
        ->assertJsonPath('data.events.3.data.status', 'finished')
        ->assertJsonPath('data.events.3.data.reason', 'more_correct_answers')
        ->assertJsonPath('data.events.3.data.outcome', 'win')
        ->assertJsonPath('data.events.3.data.result_label', 'Wygrana')
        ->assertJsonPath('data.events.3.data.result', 'player1_won')
        ->assertJsonPath('data.events.3.data.winner.user_id', $firstUser->getKey())
        ->assertJsonPath('data.events.3.data.loser.user_id', $secondUser->getKey())
        ->assertJsonPath('data.events.3.data.current_user.user_id', $firstUser->getKey())
        ->assertJsonPath('data.events.3.data.current_user.correct_answers', 1)
        ->assertJsonPath('data.events.3.data.opponent.user_id', $secondUser->getKey())
        ->assertJsonPath('data.events.3.data.opponent.correct_answers', 0)
        ->assertJsonPath('data.events.3.data.scores.player1.correct_answers', 1)
        ->assertJsonPath('data.events.3.data.scores.player2.correct_answers', 0);

    expect($events->json('data.events.3.data.current_user.elo_change'))->toBeInt();
    expect($events->json('data.events.3.data.current_user.elo_change'))->toBeGreaterThan(0);
});

test('answer for a question outside the ranked match returns invalid answer error envelope', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser);

    $extraQuestion = Question::factory()->create();

    $response = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $extraQuestion->getKey(),
            'user_answer' => 'a',
            'response_time_ms' => 1000,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['question_id'])
        ->assertJsonPath('error.code', 'INVALID_ANSWER')
        ->assertJsonPath('error_event.event', 'error')
        ->assertJsonPath('error_event.data.error_code', 'INVALID_ANSWER')
        ->assertJsonPath('error_event.data.details.match_id', $match->public_id)
        ->assertJsonPath('error_event.data.details.user_id', $firstUser->getKey())
        ->assertJsonPath('error_event.data.details.question_id', $extraQuestion->getKey());
});

test('overview exposes the most recent finished match for the current user', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    finishRankedMatchFixture($this, $match, $questions, $firstUser, $secondUser)->assertOk();

    $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null)
        ->assertJsonPath('data.recent_match.public_id', $match->public_id)
        ->assertJsonPath('data.recent_match.status', 'finished')
        ->assertJsonPath('data.recent_match.outcome', 'win')
        ->assertJsonPath('data.recent_match.current_user.correct_answers', 3);
});

test('ranked history endpoint returns the most recent finished matches first', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    finishRankedMatchFixture($this, $match, $questions, $firstUser, $secondUser)->assertOk();

    $history = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.history'));

    $history->assertOk()
        ->assertJsonCount(1, 'data.matches')
        ->assertJsonPath('data.matches.0.public_id', $match->public_id)
        ->assertJsonPath('data.matches.0.outcome', 'win');
});

test('pong endpoint marks the opponent as disconnected after the heartbeat timeout', function () {
    [$firstUser, , $match] = createRankedMatchFixture($this);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'matched');

    $this->travel(20)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'matched');

    $this->travel(15)->seconds();

    $response = $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id));

    $response->assertOk()
        ->assertJsonPath('data.state', 'opponent_disconnected')
        ->assertJsonPath('data.match.state', 'opponent_disconnected')
        ->assertJsonPath('data.match.presence.current_user.presence_state', 'online')
        ->assertJsonPath('data.match.presence.opponent.presence_state', 'disconnected');

    $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'opponent_disconnected')
        ->assertJsonPath('data.active_match.presence.opponent.presence_state', 'disconnected');
});

test('events endpoint exposes reconnect payload with opponent current state after the player returns', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser);

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 1100,
        ])
        ->assertOk();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(20)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(15)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'opponent_disconnected');

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'in_progress');

    $events = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $events->assertOk()
        ->assertJsonPath('data.events.4.event', 'match.opponent_disconnected')
        ->assertJsonPath('data.events.4.data.timeout_seconds', RankedMatchService::RECONNECT_GRACE_SECONDS)
        ->assertJsonPath('data.events.4.data.reconnect_deadline', $events->json('data.events.4.data.reconnect_deadline_at'))
        ->assertJsonPath('data.events.5.event', 'match.opponent_reconnected')
        ->assertJsonPath('data.events.5.data.opponent_current_state.current_question', 2)
        ->assertJsonPath('data.events.5.data.opponent_current_state.correct_answers', 1)
        ->assertJsonPath('data.events.5.data.opponent_current_state.points', 1)
        ->assertJsonPath('data.events.5.data.opponent_current_state.total_answered', 1);
});

test('presence sync prefers cache-backed heartbeat timestamps over stale database timestamps', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);

    $presenceStore = app(RankedMatchPresenceStore::class);

    $match->players()
        ->where('user_id', $secondUser->getKey())
        ->update([
            'last_seen_at' => now()->subMinutes(5),
        ]);

    $presenceStore->touch(
        $match,
        $secondUser->getKey(),
        now(),
        RankedMatchService::DISCONNECT_TIMEOUT_SECONDS + RankedMatchService::RECONNECT_GRACE_SECONDS + RankedMatchService::HEARTBEAT_SECONDS,
    );

    $response = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'matched')
        ->assertJsonPath('data.active_match.presence.opponent.presence_state', 'online');

    expect($response->json('data.active_match.presence.opponent.seconds_until_disconnect'))->toBeGreaterThan(0);
});

test('expired reconnect window abandons the match and awards a walkover to the active player', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);
    $presenceStore = app(RankedMatchPresenceStore::class);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(20)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(15)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'opponent_disconnected');

    $this->travel(15)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.state', 'opponent_disconnected');

    $this->travel(11)->seconds();

    $overview = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'));

    $overview->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null)
        ->assertJsonPath('data.recent_match.status', 'abandoned')
        ->assertJsonPath('data.recent_match.reason', 'opponent_disconnect_no_return')
        ->assertJsonPath('data.recent_match.outcome', 'win');

    $match->refresh();

    expect($match->status)->toBe('abandoned');
    expect($match->reason)->toBe('opponent_disconnect_no_return');

    $firstPlayer = $match->players()->where('user_id', $firstUser->getKey())->firstOrFail();
    $secondPlayer = $match->players()->where('user_id', $secondUser->getKey())->firstOrFail();

    expect($firstPlayer->elo_after)->not->toBeNull();
    expect($secondPlayer->elo_after)->not->toBeNull();
    expect($firstPlayer->status)->toBe('finished');
    expect($secondPlayer->status)->toBe('abandoned');

    $firstRating = RankedPlayerRating::query()->findOrFail($firstUser->getKey());
    $secondRating = RankedPlayerRating::query()->findOrFail($secondUser->getKey());

    expect($firstRating->wins)->toBe(1);
    expect($secondRating->losses)->toBe(1);
    expect($presenceStore->lastSeenAt($match, $firstUser->getKey()))->toBeNull();
    expect($presenceStore->lastSeenAt($match, $secondUser->getKey()))->toBeNull();
});

test('finishing a match clears cache-backed presence keys for both players', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    $presenceStore = app(RankedMatchPresenceStore::class);

    expect($presenceStore->lastSeenAt($match, $firstUser->getKey()))->not->toBeNull();
    expect($presenceStore->lastSeenAt($match, $secondUser->getKey()))->not->toBeNull();

    finishRankedMatchFixture($this, $match, $questions, $firstUser, $secondUser)->assertOk();

    expect($presenceStore->lastSeenAt($match, $firstUser->getKey()))->toBeNull();
    expect($presenceStore->lastSeenAt($match, $secondUser->getKey()))->toBeNull();
});

test('player can intentionally abandon an active ranked match and loses immediately', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);

    startRankedMatchFixture($this, $match, $firstUser, $secondUser);

    $response = $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.abandon', $match->public_id));

    $response->assertOk()
        ->assertJsonPath('data.abandoned_match', true)
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null)
        ->assertJsonPath('data.recent_match.status', 'abandoned')
        ->assertJsonPath('data.recent_match.reason', 'player_left_match')
        ->assertJsonPath('data.recent_match.outcome', 'loss')
        ->assertJsonPath('data.recent_match.result_label', 'Przegrana');

    $winnerOverview = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'));

    $winnerOverview->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null)
        ->assertJsonPath('data.recent_match.status', 'abandoned')
        ->assertJsonPath('data.recent_match.reason', 'player_left_match')
        ->assertJsonPath('data.recent_match.outcome', 'win')
        ->assertJsonPath('data.recent_match.result_label', 'Wygrana');

    $match->refresh();

    expect($match->status)->toBe('abandoned');
    expect($match->reason)->toBe('player_left_match');

    $firstPlayer = $match->players()->where('user_id', $firstUser->getKey())->firstOrFail();
    $secondPlayer = $match->players()->where('user_id', $secondUser->getKey())->firstOrFail();

    expect($firstPlayer->status)->toBe('finished');
    expect($secondPlayer->status)->toBe('abandoned');
});

test('events endpoint returns queue and opponent progress events for the current user', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    $initialEvents = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $initialEvents->assertOk()
        ->assertJsonPath('data.match_public_id', $match->public_id)
        ->assertJsonPath('data.events.0.event', 'queue.matched');

    readyRankedMatchFixture($this, $match, $firstUser, $secondUser);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser, false);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk();

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 1100,
        ])
        ->assertOk();

    $events = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $events->assertOk()
        ->assertJsonPath('data.events.0.event', 'queue.matched')
        ->assertJsonPath('data.events.1.event', 'match.countdown_started')
        ->assertJsonPath('data.events.2.event', 'match.started')
        ->assertJsonPath('data.events.3.event', 'match.opponent_answered')
        ->assertJsonPath('data.events.3.data.question_id', $questions[0]->getKey())
        ->assertJsonPath('data.events.3.data.opponent.username', 'Player Two');

    $match->refresh();

    $questionIds = collect($match->payload['question_ids'] ?? [])->values();
    $questionById = $questions->keyBy('id');
    $questionIndex = $questionIds->search($questions[0]->getKey());
    $payloadEvents = $events->json('data.events');
    $startedEvent = $payloadEvents[2];
    $opponentAnsweredEvent = $payloadEvents[3];
    $expectedRevealAt = Carbon::parse($startedEvent['data']['started_at'])
        ->copy()
        ->addSeconds(2)
        ->toIso8601String();

    expect($startedEvent['data']['status'])->toBe('in_progress');
    expect($startedEvent['data']['player1'])->toMatchArray([
        'user_id' => $firstUser->getKey(),
        'username' => 'Player One',
        'elo' => 1500,
    ]);
    expect($startedEvent['data']['player2'])->toMatchArray([
        'user_id' => $secondUser->getKey(),
        'username' => 'Player Two',
        'elo' => 1500,
    ]);
    expect($startedEvent['data']['questions'][0])->toMatchArray([
        'question_id' => $questionIds[0],
        'text' => $questionById->get($questionIds[0])->prompt,
        'question_text' => $questionById->get($questionIds[0])->prompt,
        'question_number' => 1,
        'revealed_at' => $startedEvent['data']['started_at'],
    ]);
    expect($startedEvent['data']['questions'][1]['revealed_at'])->toBe($expectedRevealAt);

    expect($questionIndex)->not->toBeFalse();
    expect($opponentAnsweredEvent['data']['question_number'])->toBe($questionIndex + 1);
    expect($opponentAnsweredEvent['data']['current_question'])->toBe($questionIndex + 1);
    expect($opponentAnsweredEvent['data']['opponent_score'])->toMatchArray([
        'correct_answers' => 1,
        'total_answered' => 1,
        'points' => 1,
    ]);
    expect($opponentAnsweredEvent['data']['last_answer'])->toMatchArray([
        'question_id' => $questions[0]->getKey(),
        'answer_correct' => true,
        'response_time_ms' => 1100,
    ]);
    expect($opponentAnsweredEvent['data']['answered_at'])->not->toBeNull();
});

test('events endpoint exposes disconnect and abandon events after reconnect timeout', function () {
    [$firstUser, $secondUser, $match] = createRankedMatchFixture($this);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(20)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(15)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(15)->seconds();

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk();

    $this->travel(11)->seconds();

    $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk();

    $events = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $events->assertOk()
        ->assertJsonPath('data.events.1.event', 'match.opponent_disconnected')
        ->assertJsonPath('data.events.1.data.timeout_seconds', RankedMatchService::RECONNECT_GRACE_SECONDS)
        ->assertJsonPath('data.events.1.data.reconnect_deadline', $events->json('data.events.1.data.reconnect_deadline_at'))
        ->assertJsonPath('data.events.2.event', 'match.abandoned')
        ->assertJsonPath('data.events.2.data.status', 'abandoned')
        ->assertJsonPath('data.events.2.data.reason', 'opponent_disconnect_no_return')
        ->assertJsonPath('data.events.2.data.outcome', 'win')
        ->assertJsonPath('data.events.2.data.result_label', 'Wygrana')
        ->assertJsonPath('data.events.2.data.winner_by_default.user_id', $firstUser->getKey())
        ->assertJsonPath('data.events.2.data.loser.user_id', $match->players()->where('user_id', '!=', $firstUser->getKey())->value('user_id'))
        ->assertJsonPath('data.events.2.data.current_user.user_id', $firstUser->getKey())
        ->assertJsonPath('data.events.2.data.opponent.user_id', $secondUser->getKey())
        ->assertJsonPath('data.events.2.data.opponent.correct_answers', 0)
        ->assertJsonPath('data.events.2.data.elo_applied', true);

    expect($events->json('data.events.2.data.current_user.elo_change'))->toBeInt();
    expect($events->json('data.events.2.data.current_user.elo_change'))->toBeGreaterThan(0);
});

test('events endpoint can return only incremental events after a cursor', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    $initialEvents = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', $match->public_id));

    $initialEvents->assertOk()
        ->assertJsonCount(1, 'data.events');

    $cursor = $initialEvents->json('data.events.0.id');

    readyRankedMatchFixture($this, $match, $firstUser, $secondUser);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser, false);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 900,
        ])
        ->assertOk();

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions[0]->getKey(),
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 1100,
        ])
        ->assertOk();

    $incrementalEvents = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.matches.events', [
            'rankedMatch' => $match->public_id,
            'after_event_id' => $cursor,
        ]));

    $incrementalEvents->assertOk()
        ->assertJsonCount(3, 'data.events')
        ->assertJsonPath('data.events.0.event', 'match.countdown_started')
        ->assertJsonPath('data.events.1.event', 'match.started')
        ->assertJsonPath('data.events.2.event', 'match.opponent_answered');
});

test('realtime stream emits overview sync and match events for authenticated players', function () {
    [$firstUser, $secondUser, $match, $questions] = createRankedMatchFixture($this);

    readyRankedMatchFixture($this, $match, $firstUser, $secondUser);
    startRankedMatchFixture($this, $match, $firstUser, $secondUser, false);

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questions->get(0)->getKey(),
            'user_answer' => $questions->get(0)->correct_answer,
            'response_time_ms' => 1100,
        ])
        ->assertOk();

    $response = $this->actingAs($firstUser)
        ->get(route('api.v1.ranked.stream', [
            'max_ticks' => 1,
            'sleep_ms' => 0,
        ]));

    $response->assertOk();

    expect((string) $response->headers->get('content-type'))->toContain('text/event-stream');

    $content = $response->streamedContent();

    expect($content)->toContain('event: stream.ready');
    expect($content)->toContain('event: heartbeat');
    expect($content)->toContain('event: overview.sync');
    expect($content)->toContain('event: queue.matched');
    expect($content)->toContain('event: match.events');
    expect($content)->toContain(sprintf('"match_public_id":"%s"', $match->public_id));
    expect($content)->toContain(sprintf('"match_id":"%s"', $match->public_id));
    expect($content)->toContain(sprintf('"heartbeat_seconds":%d', RankedMatchService::HEARTBEAT_SECONDS));
    expect($content)->toContain('"event":"heartbeat"');
    expect($content)->toContain('"event":"queue.matched"');
    expect($content)->toContain('"event":"match.countdown_started"');
    expect($content)->toContain('"event":"match.started"');
    expect($content)->toContain('"event":"match.opponent_answered"');
});

/**
 * @return array{0: User, 1: User, 2: RankedMatch, 3: Collection<int, Question>}
 */
function createRankedMatchFixture(TestCase $testCase): array
{
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $questions = Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->sequence(
            ['correct_answer' => 'a'],
            ['correct_answer' => 'b'],
            ['correct_answer' => 'c'],
        )
        ->create();

    $firstUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Player One',
    ]);
    $secondUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Player Two',
    ]);

    $testCase->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk();

    $testCase->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk();

    $match = RankedMatch::query()->latest('id')->firstOrFail();

    return [$firstUser, $secondUser, $match, $questions];
}

function finishRankedMatchFixture(
    TestCase $testCase,
    RankedMatch $match,
    Collection $questions,
    User $firstUser,
    User $secondUser,
): TestResponse {
    startRankedMatchFixture($testCase, $match, $firstUser, $secondUser);

    foreach ($questions as $question) {
        $testCase->actingAs($firstUser)
            ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
                'question_id' => $question->getKey(),
                'user_answer' => $question->correct_answer,
                'response_time_ms' => 1000,
            ])
            ->assertOk();
    }

    $questionIds = $questions->pluck('id')->values()->all();

    $testCase->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionIds[0],
            'user_answer' => $questions[0]->correct_answer,
            'response_time_ms' => 1300,
        ])
        ->assertOk();

    $testCase->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionIds[1],
            'user_answer' => $questions[1]->correct_answer === 'a' ? 'b' : 'a',
            'response_time_ms' => 1600,
        ])
        ->assertOk();

    return $testCase->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
            'question_id' => $questionIds[2],
            'user_answer' => $questions[2]->correct_answer === 'a' ? 'b' : 'a',
            'response_time_ms' => 1700,
        ]);
}

function readyRankedMatchFixture(
    TestCase $testCase,
    RankedMatch $match,
    User $firstUser,
    User $secondUser,
): void {
    $testCase->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.ready', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'matched');

    $testCase->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.matches.ready', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'matched')
        ->assertJsonPath('data.match.countdown_seconds', RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS);
}

function startRankedMatchFixture(
    TestCase $testCase,
    RankedMatch $match,
    User $firstUser,
    User $secondUser,
    bool $readyPlayers = true,
): void {
    if ($readyPlayers) {
        readyRankedMatchFixture($testCase, $match, $firstUser, $secondUser);
    }

    $testCase->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();

    $testCase->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'in_progress');
}
