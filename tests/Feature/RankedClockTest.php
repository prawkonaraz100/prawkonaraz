<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedMatchAnswer;
use App\Models\RankedMatchEvent;
use App\Models\RankedMatchPlayer;
use App\Models\RankedQueueEntry;
use App\Models\User;
use App\Support\RankedMatchPresenceStore;
use App\Support\RankedMatchService;
use App\Support\RankedRealtimeSignal;
use App\Support\RankedRealtimeStreamLoop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Etap 1e: wspólny zegar meczów i lekki strumień SSE.

beforeEach(function () {
    config([
        'ranked.match_questions' => 3,
        'ranked.question_interval_seconds' => 6,
        'ranked.match_duration_seconds' => 30,
        'ranked.clock_enabled' => true,
    ]);
});

/**
 * @return array{0: User, 1: User, 2: RankedMatch, 3: LicenseCategory}
 */
function clockMatchedPlayers(): array
{
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    Question::factory()->count(3)->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    $first = User::factory()->withPurchasedAccess()->create(['name' => 'Gracz Jeden']);
    $second = User::factory()->withPurchasedAccess()->create(['name' => 'Gracz Dwa']);
    $service = app(RankedMatchService::class);

    $service->joinQueue($first, $category);
    $service->joinQueue($second, $category);

    return [$first, $second, RankedMatch::query()->latest('id')->firstOrFail(), $category];
}

/**
 * @return array{0: User, 1: User, 2: RankedMatch}
 */
function clockStartedMatch($testCase): array
{
    [$first, $second, $match] = clockMatchedPlayers();
    $service = app(RankedMatchService::class);

    $service->recordReady($match, $first);
    $service->recordReady($match, $second);
    $testCase->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();
    $service->clockTick();

    $match->refresh();
    expect($match->status)->toBe('in_progress');

    return [$first, $second, $match];
}

function clockTouch(RankedMatch $match, User ...$users): void
{
    foreach ($users as $user) {
        app(RankedMatchPresenceStore::class)->touch($match, $user->getKey(), now(), 120);
    }
}

function clockLockingQueries(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    return collect($queries)->filter(fn (array $query) => str_contains(strtolower($query['query']), 'for update'))->count();
}

test('match events change the realtime version of both live players after commit', function () {
    $signal = app(RankedRealtimeSignal::class);
    [$first, $second, $match] = clockMatchedPlayers();

    $firstVersion = $signal->userVersion($first->getKey());
    $secondVersion = $signal->userVersion($second->getKey());
    expect($firstVersion)->not->toBeNull()
        ->and($secondVersion)->not->toBeNull();

    $service = app(RankedMatchService::class);
    $service->recordReady($match, $first);
    $service->recordReady($match, $second); // drugi „gotowy” zapisuje match.countdown_started

    expect($signal->userVersion($first->getKey()))->not->toBe($firstVersion)
        ->and($signal->userVersion($second->getKey()))->not->toBe($secondVersion);
});

test('the clock starts a match when the countdown elapses without any player request', function () {
    [, , $match] = clockStartedMatch($this);

    expect(RankedMatchEvent::query()->where('ranked_match_id', $match->getKey())->where('event_name', 'match.started')->count())->toBe(1);
});

test('the clock finishes a match when its time runs out', function () {
    [$first, $second, $match] = clockStartedMatch($this);

    $this->travel(31)->seconds();
    clockTouch($match, $first, $second);
    $result = app(RankedMatchService::class)->clockTick();

    expect($match->fresh()->status)->toBe('finished')
        ->and($result['resolved'])->toBe(1)
        ->and(RankedQueueEntry::query()->where('ranked_match_id', $match->getKey())->where('status', 'completed')->count())->toBe(2);
});

test('the clock marks a silent player disconnected and then awards a walkover', function () {
    config(['ranked.match_duration_seconds' => 300]);
    [$first, $second, $match] = clockStartedMatch($this);
    $service = app(RankedMatchService::class);

    // Pierwszy gracz ma otwarty strumień (obecność w Redis), drugi zamilkł.
    $this->travel(RankedMatchService::DISCONNECT_TIMEOUT_SECONDS + 2)->seconds();
    clockTouch($match, $first);
    $service->clockTick();

    $silent = RankedMatchPlayer::query()->where('ranked_match_id', $match->getKey())->where('user_id', $second->getKey())->sole();
    expect($silent->presence_state)->toBe('disconnected')
        ->and($match->fresh()->status)->toBe('in_progress');

    $this->travel(RankedMatchService::RECONNECT_GRACE_SECONDS + 2)->seconds();
    clockTouch($match, $first);
    $service->clockTick();

    $match->refresh();
    expect($match->status)->toBe('abandoned')
        ->and($match->reason)->toBe('opponent_disconnect_no_return')
        ->and((int) data_get($match->payload, 'winner_user_id'))->toBe($first->getKey());
});

test('the clock pairs players who missed each other when joining the queue', function () {
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    Question::factory()->count(3)->for($category, 'licenseCategory')->create();
    $users = User::factory()->count(2)->withPurchasedAccess()->create();

    foreach ($users as $index => $user) {
        RankedQueueEntry::query()->create([
            'user_id' => $user->getKey(),
            'license_category_id' => $category->getKey(),
            'status' => 'queued',
            'joined_at' => now()->subSeconds(5 - $index),
            'payload' => ['source' => 'test'],
        ]);
    }

    $result = app(RankedMatchService::class)->clockTick();

    $match = RankedMatch::query()->sole();
    expect($result['paired'])->toBe(1)
        ->and($match->players()->pluck('user_id')->sort()->values()->all())->toBe($users->pluck('id')->sort()->values()->all())
        ->and(RankedQueueEntry::query()->where('status', 'matched')->count())->toBe(2);
});

test('an idle tick does not lock or write anything', function () {
    [$first, $second, $match] = clockStartedMatch($this);
    $service = app(RankedMatchService::class);
    $eventsBefore = RankedMatchEvent::query()->count();

    $this->travel(2)->seconds();
    clockTouch($match, $first, $second);

    expect(clockLockingQueries(fn () => $service->clockTick(expireQueue: false)))->toBe(0)
        ->and(RankedMatchEvent::query()->count())->toBe($eventsBefore)
        ->and($match->fresh()->status)->toBe('in_progress');
});

test('the clock plays back replay answers on time and skips the lock when nothing is due', function () {
    config(['ranked.match_duration_seconds' => 90]);
    $category = LicenseCategory::factory()->create(['code' => 'B', 'name' => 'Kategoria B']);
    $questions = Question::factory()->count(6)->for($category, 'licenseCategory')->create(['correct_answer' => 'a']);
    $recorded = User::factory()->create(['name' => 'Marek']);
    $other = User::factory()->create();
    $startedAt = now()->subDay();

    $source = RankedMatch::query()->create([
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
        'payload' => [
            'question_ids' => $questions->pluck('id')->all(),
            'format' => ['question_interval_seconds' => 6],
        ],
    ]);

    foreach ([[$recorded, 'player1'], [$other, 'player2']] as [$user, $slot]) {
        RankedMatchPlayer::query()->create([
            'ranked_match_id' => $source->getKey(),
            'user_id' => $user->getKey(),
            'slot' => $slot,
            'status' => 'finished',
            'presence_state' => 'online',
            'username_snapshot' => $user->name,
            'elo_before' => 1500,
            'total_answered' => $user->is($recorded) ? 6 : 0,
            'correct_answers' => $user->is($recorded) ? 4 : 0,
        ]);
    }

    foreach ($questions->values() as $index => $question) {
        RankedMatchAnswer::query()->create([
            'ranked_match_id' => $source->getKey(),
            'user_id' => $recorded->getKey(),
            'question_id' => $question->getKey(),
            'question_number' => $index + 1,
            'selected_answer' => 'a',
            'is_correct' => true,
            'response_time_ms' => 1000,
            // Odpowiedź 1 s po odblokowaniu pytania (co 6 s): 1 s, 7 s, 13 s, ...
            'elapsed_ms' => $index * 6000 + 1000,
            'answered_at' => $startedAt->copy()->addSeconds($index * 6 + 1),
        ]);
    }

    $player = User::factory()->withPurchasedAccess()->create();
    $service = app(RankedMatchService::class);
    $service->startReplayMatch($player, $category);
    $replay = RankedMatch::query()->whereNotNull('replay_source_match_id')->sole();

    $service->recordReady($replay, $player);
    $this->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS)->seconds();
    $service->clockTick();
    $replay->refresh();
    expect($replay->status)->toBe('in_progress');

    $this->travelTo($replay->started_at->copy()->addMilliseconds(7500));
    clockTouch($replay, $player);
    $service->clockTick();

    $released = fn () => RankedMatchAnswer::query()->where('ranked_match_id', $replay->getKey())->where('user_id', $recorded->getKey())->count();
    expect($released())->toBe(2);

    // 0,3 s później nic nowego nie wypada: bez blokady wiersza i bez nowych odpowiedzi.
    $this->travelTo($replay->started_at->copy()->addMilliseconds(7800));
    expect(clockLockingQueries(fn () => $service->clockTick(expireQueue: false)))->toBe(0)
        ->and($released())->toBe(2);

    $this->travelTo($replay->started_at->copy()->addMilliseconds(13100));
    clockTouch($replay, $player);
    $service->clockTick(expireQueue: false);
    expect($released())->toBe(3);
});

test('the clock lease lets only one instance run at a time', function () {
    $signal = app(RankedRealtimeSignal::class);

    expect($signal->acquireClockLease('a', 5))->toBeTrue()
        ->and($signal->acquireClockLease('b', 5))->toBeFalse()
        ->and($signal->acquireClockLease('a', 5))->toBeTrue();

    $signal->releaseClockLease('a');

    expect($signal->acquireClockLease('b', 5))->toBeTrue();
});

test('clock pulse is required and expires', function () {
    $signal = app(RankedRealtimeSignal::class);

    expect($signal->clockAlive())->toBeFalse();

    $signal->recordClockPulse(1000.0);
    expect($signal->clockAlive(1003.0))->toBeTrue()
        ->and($signal->clockAlive(1010.0))->toBeFalse();

    config(['ranked.clock_enabled' => false]);
    expect($signal->clockAlive(1001.0))->toBeFalse();
});

test('ranked:clock --once runs one tick and records the pulse', function () {
    [$first, $second, $match] = clockMatchedPlayers();
    $service = app(RankedMatchService::class);
    $service->recordReady($match, $first);
    $service->recordReady($match, $second);
    $this->travel(RankedMatchService::MATCH_READY_COUNTDOWN_SECONDS + 1)->seconds();

    $this->artisan('ranked:clock', ['--once' => true])->assertExitCode(0);

    expect($match->fresh()->status)->toBe('in_progress')
        ->and(app(RankedRealtimeSignal::class)->clockAlive())->toBeTrue();
});

test('ranked:clock does nothing when the flag is off', function () {
    config(['ranked.clock_enabled' => false]);

    $this->artisan('ranked:clock')->assertExitCode(0);

    expect(app(RankedRealtimeSignal::class)->clockAlive())->toBeFalse();
});

test('ranked:clock-restart asks running clocks to stop', function () {
    $signal = app(RankedRealtimeSignal::class);
    $startedAt = microtime(true) - 1;

    expect($signal->clockRestartRequestedAfter($startedAt))->toBeFalse();

    $this->artisan('ranked:clock-restart')->assertExitCode(0);

    expect($signal->clockRestartRequestedAfter($startedAt))->toBeTrue();
});

/**
 * Uruchamia pętlę strumienia na sztucznym zegarze (bez prawdziwego czekania).
 *
 * @param  callable(float): void|null  $onTime  wywoływane po każdym „uśpieniu” z bieżącym czasem
 * @return array{mode: string, full_publishes: int, polls: int}
 */
function clockRunStream(User $user, ?callable $onTime = null, int $maxTicks = 25, int $sleepMs = 1000, ?float $lifetime = null): array
{
    $time = 1000.0;
    $loop = app(RankedRealtimeStreamLoop::class)->withRuntime(
        clock: function () use (&$time): float {
            return $time;
        },
        sleeper: function (int $milliseconds) use (&$time, $onTime): void {
            $time += $milliseconds / 1000;

            if ($onTime) {
                $onTime($time);
            }
        },
    );

    return $loop->run($user, fn () => null, fn () => null, $maxTicks, $sleepMs, $lifetime);
}

test('with a live clock the stream recomputes state only on change or every full refresh', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $signal = app(RankedRealtimeSignal::class);
    $signal->recordClockPulse(1000.0);

    $result = clockRunStream($user, fn (float $time) => $signal->recordClockPulse($time));

    // 25 s połączenia: pełny stan na starcie, po 10 s i po 20 s, a wersja sprawdzana co 250 ms.
    expect($result['mode'])->toBe('lightweight')
        ->and($result['full_publishes'])->toBe(3)
        ->and($result['polls'])->toBeGreaterThan(90);
});

test('a version change makes the stream recompute state at the next poll', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $signal = app(RankedRealtimeSignal::class);
    $signal->recordClockPulse(1000.0);
    $bumped = false;

    $result = clockRunStream($user, function (float $time) use ($signal, $user, &$bumped): void {
        $signal->recordClockPulse($time);

        if (! $bumped && $time >= 1005.0) {
            $signal->bumpUsers([$user->getKey()]);
            $bumped = true;
        }
    });

    expect($result['full_publishes'])->toBe(4);
});

test('when the clock stops the stream falls back to recomputing every second', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    app(RankedRealtimeSignal::class)->recordClockPulse(1000.0);

    // Puls nie jest odnawiany: po ok. 5 s zegar uznajemy za martwy.
    $result = clockRunStream($user);

    expect($result['mode'])->toBe('lightweight')
        ->and($result['full_publishes'])->toBeGreaterThan(15);
});

test('a longer lifetime keeps one connection open for longer in lightweight mode', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $signal = app(RankedRealtimeSignal::class);
    $signal->recordClockPulse(1000.0);

    $result = clockRunStream($user, fn (float $time) => $signal->recordClockPulse($time), lifetime: 55.0);

    // Pełny stan na starcie i co 10 s: 0, 10, 20, 30, 40, 50.
    expect($result['full_publishes'])->toBe(6)
        ->and($result['polls'])->toBeGreaterThan(200);
});

test('without the clock the stream keeps the previous behaviour', function () {
    config(['ranked.clock_enabled' => false]);
    $user = User::factory()->withPurchasedAccess()->create();

    $result = clockRunStream($user);

    expect($result['mode'])->toBe('full')
        ->and($result['full_publishes'])->toBe(25);
});
