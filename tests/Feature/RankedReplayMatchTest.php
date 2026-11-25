<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedMatchAnswer;
use App\Models\RankedMatchPlayer;
use App\Models\RankedPlayerRating;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\RankedMatchService;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Zakończony mecz na żywo, którego jeden gracz posłuży za nagranie.
 * Nagrany gracz odpowiada na kolejne pytania w 1., 2., 3., ... sekundzie meczu.
 *
 * @return array{0: RankedMatch, 1: Collection<int, Question>}
 */
function createRankedReplaySource(LicenseCategory $category, User $recordedUser, int $questionCount = 6, int $correctCount = 4, int $elo = 1500): array
{
    $questions = Question::factory()
        ->count($questionCount)
        ->for($category, 'licenseCategory')
        ->create(['correct_answer' => 'a']);
    $otherPlayer = User::factory()->create();
    $startedAt = now()->subDay();

    $match = RankedMatch::query()->create([
        'public_id' => (string) Illuminate\Support\Str::uuid(),
        'license_category_id' => $category->getKey(),
        'status' => 'finished',
        'api_version' => '1.0',
        'duration_seconds' => 90,
        'total_questions' => $questionCount,
        'matched_at' => $startedAt->copy()->subSeconds(5),
        'countdown_seconds' => 3,
        'started_at' => $startedAt,
        'finished_at' => $startedAt->copy()->addSeconds(60),
        'reason' => 'more_correct_answers',
        'payload' => ['question_ids' => $questions->pluck('id')->all()],
    ]);

    RankedMatchPlayer::query()->create([
        'ranked_match_id' => $match->getKey(),
        'user_id' => $recordedUser->getKey(),
        'slot' => 'player1',
        'status' => 'finished',
        'presence_state' => 'online',
        'username_snapshot' => $recordedUser->name,
        'elo_before' => $elo,
        'total_answered' => $questionCount,
        'correct_answers' => $correctCount,
    ]);
    RankedMatchPlayer::query()->create([
        'ranked_match_id' => $match->getKey(),
        'user_id' => $otherPlayer->getKey(),
        'slot' => 'player2',
        'status' => 'finished',
        'presence_state' => 'online',
        'username_snapshot' => $otherPlayer->name,
        'elo_before' => 1500,
    ]);

    foreach ($questions->values() as $index => $question) {
        RankedMatchAnswer::query()->create([
            'ranked_match_id' => $match->getKey(),
            'user_id' => $recordedUser->getKey(),
            'question_id' => $question->getKey(),
            'question_number' => $index + 1,
            'selected_answer' => $index < $correctCount ? 'a' : 'b',
            'is_correct' => $index < $correctCount,
            'response_time_ms' => 1000,
            'elapsed_ms' => ($index + 1) * 1000,
            'answered_at' => $startedAt->copy()->addSeconds($index + 1),
        ]);
    }

    return [$match, $questions];
}

function startRankedReplay($testCase, User $user, LicenseCategory $category)
{
    return $testCase->actingAs($user)->postJson(route('api.v1.ranked.replay'), [
        'category_id' => $category->getKey(),
    ]);
}

function beginRankedReplay($testCase, User $user, RankedMatch $replay): void
{
    $testCase->actingAs($user)
        ->postJson(route('api.v1.ranked.matches.ready', $replay->public_id))
        ->assertOk();

    $testCase->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS)->seconds();

    $testCase->actingAs($user)
        ->postJson(route('api.v1.ranked.matches.pong', $replay->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'in_progress');
}

test('a player can start a replay match against a recorded player of the same category', function () {
    $category = LicenseCategory::factory()->create(['code' => 'C', 'name' => 'Kategoria C']);
    $recordedUser = User::factory()->withPurchasedAccess()->create(['name' => 'Marek']);
    [$source, $questions] = createRankedReplaySource($category, $recordedUser, elo: 1540);
    $player = User::factory()->withPurchasedAccess()->create();

    startRankedReplay($this, $player, $category)
        ->assertOk()
        ->assertJsonPath('data.joined', true)
        ->assertJsonPath('data.state', 'matched')
        ->assertJsonPath('data.active_match.is_replay', true)
        ->assertJsonPath('data.active_match.category.code', 'C')
        ->assertJsonPath('data.active_match.opponent.username', 'Marek · nagranie')
        ->assertJsonPath('data.active_match.opponent.is_replay', true)
        ->assertJsonPath('data.active_match.opponent.elo_before', 1540)
        ->assertJsonPath('data.active_match.replay.source_username', 'Marek');

    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();

    expect($replay->replay_source_match_id)->toBe($source->getKey());
    expect($replay->payload['question_ids'])->toEqual($questions->pluck('id')->all());

    // Nagrany gracz nie „siedzi” w cudzym meczu: jego stan i historia go nie widzą.
    $this->actingAs($recordedUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.active_match', null);

    $this->actingAs($recordedUser)
        ->getJson(route('api.v1.ranked.history'))
        ->assertOk()
        ->assertJsonCount(1, 'data.matches')
        ->assertJsonPath('data.matches.0.public_id', $source->public_id);
});

test('replays come only from human recordings of the same category that the player has not used', function () {
    $categoryB = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $categoryC = LicenseCategory::factory()->create(['code' => 'C', 'name' => 'Kategoria C']);
    $player = User::factory()->withPurchasedAccess()->create();

    // Nagrania, które nie mogą zostać użyte w kategorii B:
    createRankedReplaySource($categoryC, User::factory()->create());            // inna kategoria
    createRankedReplaySource($categoryB, User::factory()->testAccount()->create()); // konto testowe
    createRankedReplaySource($categoryB, $player);                              // własne nagranie
    createRankedReplaySource($categoryB, User::factory()->create(), questionCount: 3, correctCount: 3); // za mało odpowiedzi

    expect(app(RankedMatchService::class)->hasReplayAvailable($player, $categoryB))->toBeFalse();

    startRankedReplay($this, $player, $categoryB)
        ->assertStatus(422)
        ->assertJsonValidationErrors('replay');

    expect(RankedMatch::query()->whereNotNull('replay_source_match_id')->count())->toBe(0);

    createRankedReplaySource($categoryB, User::factory()->create(['name' => 'Ola']));

    expect(app(RankedMatchService::class)->hasReplayAvailable($player, $categoryB))->toBeTrue();

    startRankedReplay($this, $player, $categoryB)
        ->assertOk()
        ->assertJsonPath('data.active_match.category.code', 'B')
        ->assertJsonPath('data.active_match.opponent.username', 'Ola · nagranie');

    // Po zakończeniu to samo nagranie nie jest proponowane drugi raz.
    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();
    $this->actingAs($player)->postJson(route('api.v1.ranked.matches.abandon', $replay->public_id))->assertOk();

    expect(app(RankedMatchService::class)->hasReplayAvailable($player, $categoryB))->toBeFalse();
});

test('recorded answers are played back at their original moments of the match', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $recordedUser = User::factory()->create(['name' => 'Marek']);
    createRankedReplaySource($category, $recordedUser);
    $player = User::factory()->withPurchasedAccess()->create();

    startRankedReplay($this, $player, $category)->assertOk();
    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();

    beginRankedReplay($this, $player, $replay);

    // Start meczu to pełna sekunda; po 2,5 s nagranie ma 2 odpowiedzi (w 1. i 2. sekundzie).
    $this->travelTo($replay->fresh()->started_at->copy()->addMilliseconds(2500));

    $events = $this->actingAs($player)
        ->getJson(route('api.v1.ranked.matches.events', $replay->public_id))
        ->assertOk()
        ->json('data.events');

    $opponentAnswers = collect($events)->where('event', 'match.opponent_answered')->values();

    expect($opponentAnswers)->toHaveCount(2);
    expect($opponentAnswers->last()['data']['opponent_score']['total_answered'])->toBe(2);

    $this->travel(2)->seconds();

    $this->actingAs($player)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.active_match.opponent.total_answered', 4);
});

test('finishing a replay match changes ELO only for the live player', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $recordedUser = User::factory()->create(['name' => 'Marek']);
    [, $questions] = createRankedReplaySource($category, $recordedUser, questionCount: 6, correctCount: 4);
    RankedPlayerRating::query()->create([
        'user_id' => $recordedUser->getKey(),
        'rating' => 1612,
        'peak_rating' => 1612,
        'matches_played' => 1,
        'wins' => 1,
        'losses' => 0,
        'draws' => 0,
        'current_streak' => 1,
        'best_streak' => 1,
    ]);
    $player = User::factory()->withPurchasedAccess()->create();

    startRankedReplay($this, $player, $category)->assertOk();
    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();
    beginRankedReplay($this, $player, $replay);

    // Żywy gracz odpowiada poprawnie na wszystkie 6 pytań w pierwszej sekundzie.
    foreach ($questions as $question) {
        $this->actingAs($player)
            ->postJson(route('api.v1.ranked.matches.answers.store', $replay->public_id), [
                'question_id' => $question->getKey(),
                'user_answer' => 'a',
            ])
            ->assertOk();
    }

    expect($replay->fresh()->status)->toBe('in_progress');

    // Mecz kończy się, gdy nagranie odtworzy wszystkie swoje odpowiedzi (6. w 6. sekundzie).
    $this->travel(7)->seconds();
    $this->actingAs($player)->getJson(route('api.v1.ranked.overview'))->assertOk();

    $replay->refresh();

    expect($replay->status)->toBe('finished');
    expect($replay->reason)->toBe('more_correct_answers');
    expect((int) data_get($replay->payload, 'winner_user_id'))->toBe($player->getKey());

    $playerRating = RankedPlayerRating::query()->where('user_id', $player->getKey())->sole();
    $recordedRating = RankedPlayerRating::query()->where('user_id', $recordedUser->getKey())->sole();
    $replayPlayer = $replay->players()->where('is_replay', true)->sole();

    expect($playerRating->rating)->toBeGreaterThan(1500);
    expect($playerRating->wins)->toBe(1);
    expect($recordedRating->rating)->toBe(1612);
    expect($recordedRating->matches_played)->toBe(1);
    expect($replayPlayer->elo_change)->toBe(0);
    expect($replayPlayer->correct_answers)->toBe(4);
});

test('abandoning a replay match is a loss for the live player only', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $recordedUser = User::factory()->create();
    createRankedReplaySource($category, $recordedUser);
    $player = User::factory()->withPurchasedAccess()->create();

    startRankedReplay($this, $player, $category)->assertOk();
    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();
    beginRankedReplay($this, $player, $replay);

    $this->actingAs($player)
        ->postJson(route('api.v1.ranked.matches.abandon', $replay->public_id))
        ->assertOk();

    expect($replay->fresh()->status)->toBe('abandoned');
    expect(RankedPlayerRating::query()->where('user_id', $player->getKey())->value('losses'))->toBe(1);
    expect(RankedPlayerRating::query()->where('user_id', $recordedUser->getKey())->exists())->toBeFalse();
});

test('ranking page tells the lobby whether a replay is available in the selected category', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B', 'sort_order' => 10]);
    Question::factory()->for($category, 'licenseCategory')->create();
    $player = User::factory()->withPurchasedAccess()->create();
    UserProfile::factory()->for($player, 'user')->create(['target_category_id' => $category->getKey()]);

    $this->actingAs($player)
        ->get(route('session.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('replayAvailable', false)
            ->where('specSummary.replay_offer_after_seconds', RankedMatchService::REPLAY_OFFER_AFTER_SECONDS)
        );

    createRankedReplaySource($category, User::factory()->create());

    $this->actingAs($player)
        ->get(route('session.ranking'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('replayAvailable', true));
});
