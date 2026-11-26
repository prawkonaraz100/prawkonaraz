<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedMatchPlayer;
use App\Models\User;
use App\Support\ProductAccessDecision;
use App\Support\ProductAccessResolver;
use App\Support\RankedMatchService;
use App\Support\RankedRealtimeConnectionState;
use App\Support\RankedRealtimeEventStreamPublisher;
use Illuminate\Support\Str;
use Illuminate\Testing\TestCase;

// Mecz z tempem: pytanie i odblokowuje się po starcie + i × odstęp, u obu graczy jednocześnie.

beforeEach(function () {
    config([
        'ranked.match_questions' => 3,
        'ranked.question_interval_seconds' => 6,
        'ranked.match_duration_seconds' => 30,
    ]);
});

/**
 * @return array{0: User, 1: User, 2: RankedMatch, 3: list<int>}
 */
function startPacedRankedMatch($testCase): array
{
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    Question::factory()->count(3)->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    $first = User::factory()->withPurchasedAccess()->create(['name' => 'Gracz Jeden']);
    $second = User::factory()->withPurchasedAccess()->create(['name' => 'Gracz Dwa']);

    foreach ([$first, $second] as $user) {
        $testCase->actingAs($user)
            ->postJson(route('api.v1.ranked.queue.join'), ['category_id' => $category->getKey()])
            ->assertOk();
    }

    $match = RankedMatch::query()->latest('id')->firstOrFail();

    foreach ([$first, $second] as $user) {
        $testCase->actingAs($user)->postJson(route('api.v1.ranked.matches.ready', $match->public_id))->assertOk();
    }

    $testCase->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();
    $testCase->actingAs($first)->postJson(route('api.v1.ranked.matches.pong', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.status', 'in_progress');

    $match->refresh();

    return [$first, $second, $match, array_map('intval', $match->payload['question_ids'])];
}

function answerPacedQuestion($testCase, User $user, RankedMatch $match, int $questionId, string $answer = 'a')
{
    return $testCase->actingAs($user)->postJson(route('api.v1.ranked.matches.answers.store', $match->public_id), [
        'question_id' => $questionId,
        'user_answer' => $answer,
    ]);
}

test('new matches store the configured format', function () {
    [, , $match] = startPacedRankedMatch($this);

    expect($match->total_questions)->toBe(3)
        ->and($match->duration_seconds)->toBe(30)
        ->and(data_get($match->payload, 'format.question_interval_seconds'))->toBe(6);
});

test('load test players do not match with regular players from the same category', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    Question::factory()->count(3)->for($category, 'licenseCategory')->create();
    $regular = User::factory()->withPurchasedAccess()->create();
    $firstTest = User::factory()->withPurchasedAccess()->create(['is_test_account' => true]);
    $secondTest = User::factory()->withPurchasedAccess()->create(['is_test_account' => true]);
    $service = app(RankedMatchService::class);

    $service->joinQueue($regular, $category);
    $service->joinQueue($firstTest, $category);
    expect(RankedMatch::query()->count())->toBe(0);

    $service->joinQueue($secondTest, $category);
    $match = RankedMatch::query()->sole();
    expect($match->players()->pluck('user_id')->sort()->values()->all())
        ->toBe([$firstTest->id, $secondTest->id]);
});

test('a question cannot be answered before it is revealed', function () {
    [$first, , $match, $questionIds] = startPacedRankedMatch($this);
    $this->travelTo($match->started_at->copy()->addSeconds(1));

    answerPacedQuestion($this, $first, $match, $questionIds[0])->assertOk();

    answerPacedQuestion($this, $first, $match, $questionIds[1])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'QUESTION_NOT_AVAILABLE');

    $this->travelTo($match->started_at->copy()->addSeconds(6));

    answerPacedQuestion($this, $first, $match, $questionIds[1])
        ->assertOk()
        ->assertJsonPath('data.accepted', true);
});

test('match details expose reveal times and the server clock', function () {
    [$first, , $match] = startPacedRankedMatch($this);

    $details = $this->actingAs($first)
        ->getJson(route('api.v1.ranked.matches.show', $match->public_id))
        ->assertOk()
        ->assertJsonPath('data.match.question_interval_seconds', 6)
        ->json('data');

    expect($details['server_now'])->toBeString()
        ->and(collect($details['questions'])->pluck('available_at')->all())->toBe([
            $match->started_at->toIso8601String(),
            $match->started_at->copy()->addSeconds(6)->toIso8601String(),
            $match->started_at->copy()->addSeconds(12)->toIso8601String(),
        ]);
});

test('response time is measured from the moment the question was revealed', function () {
    [$first, , $match, $questionIds] = startPacedRankedMatch($this);

    $this->travelTo($match->started_at->copy()->addMilliseconds(1500));
    answerPacedQuestion($this, $first, $match, $questionIds[0])->assertOk()->assertJsonPath('data.answer.response_time_ms', 1500);

    $this->travelTo($match->started_at->copy()->addMilliseconds(8000));
    answerPacedQuestion($this, $first, $match, $questionIds[1])->assertOk()->assertJsonPath('data.answer.response_time_ms', 2000);
});

test('a points tie is won by the faster total reaction on correct answers', function () {
    [$first, $second, $match, $questionIds] = startPacedRankedMatch($this);
    $startedAt = $match->started_at->copy();

    // Pierwszy gracz: reakcje 3,0 s + 1,0 s; drugi: 1,5 s + 1,5 s → drugi szybszy o 1 s.
    $this->travelTo($startedAt->copy()->addMilliseconds(1500));
    answerPacedQuestion($this, $second, $match, $questionIds[0])->assertOk();
    $this->travelTo($startedAt->copy()->addMilliseconds(3000));
    answerPacedQuestion($this, $first, $match, $questionIds[0])->assertOk();
    $this->travelTo($startedAt->copy()->addMilliseconds(7000));
    answerPacedQuestion($this, $first, $match, $questionIds[1])->assertOk();
    $this->travelTo($startedAt->copy()->addMilliseconds(7500));
    answerPacedQuestion($this, $second, $match, $questionIds[1])->assertOk();
    $this->travelTo($startedAt->copy()->addMilliseconds(12500));
    answerPacedQuestion($this, $first, $match, $questionIds[2], 'b')->assertOk();
    answerPacedQuestion($this, $second, $match, $questionIds[2], 'b')->assertOk();

    $match->refresh();
    $winner = RankedMatchPlayer::query()->where('ranked_match_id', $match->getKey())->where('status', 'won')->first()
        ?? RankedMatchPlayer::query()->where('ranked_match_id', $match->getKey())->orderByDesc('elo_change')->first();

    expect($match->status)->toBe('finished')
        ->and($match->reason)->toBe('faster_time')
        ->and($winner->user_id)->toBe($second->getKey());
});

test('an open stream keeps a player online without pongs', function () {
    config(['ranked.match_duration_seconds' => 120]);
    [$first, $second, $match] = startPacedRankedMatch($this);
    $publisher = app(RankedRealtimeEventStreamPublisher::class);
    $state = new RankedRealtimeConnectionState;

    // 40 s bez odpowiedzi i pongów, ale z otwartym strumieniem (tick co 10 s).
    for ($elapsed = 0; $elapsed <= 40; $elapsed += 10) {
        $this->travelTo($match->started_at->copy()->addSeconds($elapsed));
        $publisher->publish($first, $state);
    }

    // Przejścia obecności jak w harmonogramie (ranked:sweep co minutę).
    app(RankedMatchService::class)->sweepActiveMatches();

    $player = RankedMatchPlayer::query()
        ->where('ranked_match_id', $match->getKey())
        ->where('user_id', $first->getKey())
        ->firstOrFail();

    $silentPlayer = RankedMatchPlayer::query()
        ->where('ranked_match_id', $match->getKey())
        ->where('user_id', $second->getKey())
        ->firstOrFail();

    // Gracz z otwartym strumieniem zostaje online, gracz bez strumienia i bez pongów nie.
    expect($player->presence_state)->toBe('online')
        ->and($silentPlayer->presence_state)->toBe('disconnected');
});

test('replays are offered only from matches with the same pace', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $questions = Question::factory()->count(6)->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    $recorded = User::factory()->withPurchasedAccess()->create();
    $other = User::factory()->create();
    $player = User::factory()->withPurchasedAccess()->create();
    $startedAt = now()->subDay();

    $legacy = RankedMatch::query()->create([
        'public_id' => (string) Str::uuid(),
        'license_category_id' => $category->getKey(),
        'status' => 'finished',
        'api_version' => '1.0',
        'duration_seconds' => 90,
        'total_questions' => 6,
        'matched_at' => $startedAt->copy()->subSeconds(5),
        'countdown_seconds' => 3,
        'started_at' => $startedAt,
        'finished_at' => $startedAt->copy()->addSeconds(60),
        'reason' => 'more_correct_answers',
        'payload' => ['question_ids' => $questions->pluck('id')->all()],
    ]);

    foreach ([[$recorded, 'player1'], [$other, 'player2']] as [$user, $slot]) {
        RankedMatchPlayer::query()->create([
            'ranked_match_id' => $legacy->getKey(),
            'user_id' => $user->getKey(),
            'slot' => $slot,
            'status' => 'finished',
            'presence_state' => 'online',
            'username_snapshot' => $user->name,
            'elo_before' => 1500,
            'total_answered' => 6,
            'correct_answers' => 4,
        ]);
    }

    $service = app(RankedMatchService::class);

    expect($service->hasReplayAvailable($player, $category))->toBeFalse();

    config(['ranked.question_interval_seconds' => 0]);

    expect($service->hasReplayAvailable($player, $category))->toBeTrue();
});

test('ranked api reuses a positive product access decision for a short time', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    Question::factory()->count(3)->for($category, 'licenseCategory')->create();
    $user = User::factory()->withPurchasedAccess()->create();

    $this->mock(ProductAccessResolver::class)
        ->shouldReceive('forUser')
        ->once()
        ->andReturn(ProductAccessDecision::allow('purchase'));

    $this->actingAs($user)->getJson(route('api.v1.ranked.overview'))->assertOk();
    $this->actingAs($user)->getJson(route('api.v1.ranked.overview'))->assertOk();
});

test('the concurrent player limit comes from configuration', function () {
    config(['ranked.max_concurrent_players' => 2]);
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    Question::factory()->count(3)->for($category, 'licenseCategory')->create();
    [$first, $second, $third] = User::factory()->count(3)->withPurchasedAccess()->create()->all();

    foreach ([$first, $second] as $user) {
        $this->actingAs($user)
            ->postJson(route('api.v1.ranked.queue.join'), ['category_id' => $category->getKey()])
            ->assertOk();
    }

    $this->actingAs($third)
        ->postJson(route('api.v1.ranked.queue.join'), ['category_id' => $category->getKey()])
        ->assertOk()
        ->assertJsonPath('data.queue.status', 'server_full')
        ->assertJsonPath('data.capacity.max_players', 2);
});
