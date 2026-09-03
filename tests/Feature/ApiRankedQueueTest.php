<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\RankedMatch;
use App\Models\RankedPlayerRating;
use App\Models\RankedQueueEntry;
use App\Models\User;
use App\Support\RankedMatchService;

test('authenticated users can inspect an empty ranked overview and get a default rating', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.rating.rating', 1500)
        ->assertJsonPath('data.rating.matches_played', 0)
        ->assertJsonPath('data.capacity.current_players', 0)
        ->assertJsonPath('data.capacity.max_players', RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->assertJsonPath('data.capacity.is_full', false)
        ->assertJsonPath('data.queue', null)
        ->assertJsonPath('data.active_match', null);

    expect(RankedPlayerRating::query()->where('user_id', $user->getKey())->exists())->toBeTrue();
});

test('joining the ranked queue stores a queued entry for the selected category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.joined', true)
        ->assertJsonPath('data.state', 'queued')
        ->assertJsonPath('data.queue.status', 'queued')
        ->assertJsonPath('data.queue.category.code', 'C')
        ->assertJsonPath('data.capacity.current_players', 1)
        ->assertJsonPath('data.capacity.is_full', false)
        ->assertJsonPath('data.active_match', null);

    $queueEntry = RankedQueueEntry::query()->latest('id')->firstOrFail();

    expect($queueEntry->user_id)->toBe($user->getKey());
    expect($queueEntry->license_category_id)->toBe($category->getKey());
    expect($queueEntry->status)->toBe('queued');
});

test('second player joining the same category creates a ranked match for both users', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $firstUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Player One',
    ]);
    $secondUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Player Two',
    ]);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued');

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'matched')
        ->assertJsonPath('data.active_match.status', 'matched')
        ->assertJsonPath('data.active_match.category.code', 'B')
        ->assertJsonPath('data.active_match.players.0.username', 'Player One')
        ->assertJsonPath('data.active_match.players.1.username', 'Player Two');

    $match = RankedMatch::query()->with('players')->firstOrFail();

    expect($match->status)->toBe('matched');
    expect($match->public_id)->not->toBeEmpty();
    expect($match->players)->toHaveCount(2);
    expect($match->players->pluck('username_snapshot')->values()->all())->toEqual(['Player One', 'Player Two']);

    $firstOverview = $this->actingAs($firstUser)
        ->getJson(route('api.v1.ranked.overview'));

    $firstOverview->assertOk()
        ->assertJsonPath('data.state', 'matched')
        ->assertJsonPath('data.active_match.public_id', $match->public_id)
        ->assertJsonPath('data.queue.status', 'matched');

    expect(
        RankedQueueEntry::query()
            ->where('status', 'matched')
            ->count()
    )->toBe(2);
});

test('users can leave the ranked queue before they are matched', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'D',
        'name' => 'Kategoria D',
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued');

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.leave'))
        ->assertOk()
        ->assertJsonPath('data.left_queue', true)
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.queue', null)
        ->assertJsonPath('data.active_match', null);

    $queueEntry = RankedQueueEntry::query()->latest('id')->firstOrFail();

    expect($queueEntry->status)->toBe('cancelled');
    expect($queueEntry->left_at)->not->toBeNull();
});

test('overview expires a queued entry after the matchmaking timeout', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create();

    RankedQueueEntry::query()->create([
        'user_id' => $user->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'queued',
        'joined_at' => now()->subSeconds(RankedMatchService::MATCHMAKING_TIMEOUT_SECONDS + 5),
        'payload' => [
            'source' => 'test-timeout',
        ],
    ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'idle')
        ->assertJsonPath('data.queue', null)
        ->assertJsonPath('data.active_match', null);

    $queueEntry = RankedQueueEntry::query()
        ->where('user_id', $user->getKey())
        ->latest('id')
        ->firstOrFail();

    expect($queueEntry->status)->toBe('cancelled');
    expect($queueEntry->left_at)->not->toBeNull();
});

test('joining a new ranked queue category replaces an older queued entry for the same user', function () {
    $user = User::factory()->testAccount()->create();
    $categoryB = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);
    $categoryC = LicenseCategory::factory()->create([
        'code' => 'C',
        'name' => 'Kategoria C',
    ]);

    Question::factory()->for($categoryB, 'licenseCategory')->create();
    Question::factory()->for($categoryC, 'licenseCategory')->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $categoryB->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued')
        ->assertJsonPath('data.queue.category.code', 'B');

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $categoryC->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued')
        ->assertJsonPath('data.queue.category.code', 'C');

    expect(
        RankedQueueEntry::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'cancelled')
            ->count()
    )->toBe(1);

    expect(
        RankedQueueEntry::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'queued')
            ->value('license_category_id')
    )->toBe($categoryC->getKey());
});

test('joining the ranked queue returns server_full when the global concurrent limit is reached', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create();

    User::factory()
        ->count(RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->create()
        ->each(function (User $queuedUser) use ($category): void {
            RankedQueueEntry::query()->create([
                'user_id' => $queuedUser->getKey(),
                'license_category_id' => $category->getKey(),
                'status' => 'queued',
                'joined_at' => now(),
                'payload' => [
                    'source' => 'test-seed',
                ],
            ]);
        });

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.joined', true)
        ->assertJsonPath('data.state', 'server_full')
        ->assertJsonPath('data.queue.status', 'server_full')
        ->assertJsonPath('data.queue.server_full.error_code', 'SERVER_FULL')
        ->assertJsonPath('data.queue.server_full.current_capacity', RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->assertJsonPath('data.capacity.current_players', RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->assertJsonPath('data.capacity.waiting_users', 1)
        ->assertJsonPath('data.capacity.is_full', true)
        ->assertJsonPath('data.active_match', null);

    $queueEntry = RankedQueueEntry::query()
        ->where('user_id', $user->getKey())
        ->latest('id')
        ->firstOrFail();

    expect($queueEntry->status)->toBe('server_full');
    expect(data_get($queueEntry->payload, 'error_code'))->toBe('SERVER_FULL');
});

test('overview resumes a server_full queue entry when a concurrent slot is released', function () {
    $targetUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Target Player',
    ]);
    $category = LicenseCategory::factory()->categoryB()->create();
    $otherCategory = LicenseCategory::factory()->categoryC()->create();

    Question::factory()->count(5)->for($category, 'licenseCategory')->create();
    Question::factory()->count(5)->for($otherCategory, 'licenseCategory')->create();

    $opponent = User::factory()->withPurchasedAccess()->create([
        'name' => 'Queued Opponent',
    ]);

    RankedQueueEntry::query()->create([
        'user_id' => $opponent->getKey(),
        'license_category_id' => $category->getKey(),
        'status' => 'queued',
        'joined_at' => now()->subSeconds(20),
        'payload' => [
            'source' => 'test-seed',
        ],
    ]);

    $fillerUsers = User::factory()
        ->count(RankedMatchService::MAX_CONCURRENT_PLAYERS - 1)
        ->create();

    foreach ($fillerUsers as $index => $fillerUser) {
        RankedQueueEntry::query()->create([
            'user_id' => $fillerUser->getKey(),
            'license_category_id' => $otherCategory->getKey(),
            'status' => 'queued',
            'joined_at' => now()->subSeconds(40 - $index),
            'payload' => [
                'source' => 'test-seed',
            ],
        ]);
    }

    $this->actingAs($targetUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'server_full');

    $releasedEntry = RankedQueueEntry::query()
        ->where('license_category_id', $otherCategory->getKey())
        ->where('status', 'queued')
        ->latest('id')
        ->firstOrFail();

    $releasedEntry->forceFill([
        'status' => 'cancelled',
        'left_at' => now(),
    ])->save();

    $this->actingAs($targetUser)
        ->getJson(route('api.v1.ranked.overview'))
        ->assertOk()
        ->assertJsonPath('data.state', 'matched')
        ->assertJsonPath('data.queue.status', 'matched')
        ->assertJsonPath('data.capacity.current_players', RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->assertJsonPath('data.capacity.waiting_users', 0)
        ->assertJsonPath('data.active_match.status', 'matched')
        ->assertJsonPath('data.active_match.players.0.username', 'Queued Opponent')
        ->assertJsonPath('data.active_match.players.1.username', 'Target Player');

    $targetQueueEntry = RankedQueueEntry::query()
        ->where('user_id', $targetUser->getKey())
        ->latest('id')
        ->firstOrFail();

    expect($targetQueueEntry->status)->toBe('matched');
    expect($targetQueueEntry->ranked_match_id)->not->toBeNull();
});

test('realtime stream emits queue.server_full for users waiting outside capacity', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create();

    User::factory()
        ->count(RankedMatchService::MAX_CONCURRENT_PLAYERS)
        ->create()
        ->each(function (User $queuedUser) use ($category): void {
            RankedQueueEntry::query()->create([
                'user_id' => $queuedUser->getKey(),
                'license_category_id' => $category->getKey(),
                'status' => 'queued',
                'joined_at' => now(),
                'payload' => [
                    'source' => 'test-seed',
                ],
            ]);
        });

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'server_full');

    $response = $this->actingAs($user)
        ->get(route('api.v1.ranked.stream', [
            'max_ticks' => 1,
            'sleep_ms' => 0,
        ]));

    $response->assertOk();

    expect((string) $response->headers->get('content-type'))->toContain('text/event-stream');

    $content = $response->streamedContent();

    expect($content)->toContain('event: overview.sync');
    expect($content)->toContain('event: error');
    expect($content)->toContain('event: queue.server_full');
    expect($content)->toContain('"event":"error"');
    expect($content)->toContain('"event":"queue.server_full"');
    expect($content)->toContain('"error_code":"SERVER_FULL"');
    expect($content)->toContain('"channel":"queue"');
});

test('realtime stream emits queue.queued for users waiting in a normal queue', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued');

    $response = $this->actingAs($user)
        ->get(route('api.v1.ranked.stream', [
            'max_ticks' => 1,
            'sleep_ms' => 0,
        ]));

    $response->assertOk();

    expect((string) $response->headers->get('content-type'))->toContain('text/event-stream');

    $content = $response->streamedContent();

    expect($content)->toContain('event: overview.sync');
    expect($content)->toContain('event: queue.queued');
    expect($content)->toContain('"event":"queue.queued"');
    expect($content)->toContain('"status":"queued"');
    expect($content)->toContain('"code":"B"');
});

test('realtime stream emits queue.matched for users who already have an active ranked match', function () {
    $category = LicenseCategory::factory()->categoryB()->create();

    Question::factory()->for($category, 'licenseCategory')->create();

    $firstUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Stream Player One',
    ]);
    $secondUser = User::factory()->withPurchasedAccess()->create([
        'name' => 'Stream Player Two',
    ]);

    $this->actingAs($firstUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'queued');

    $this->actingAs($secondUser)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $category->getKey(),
        ])
        ->assertOk()
        ->assertJsonPath('data.state', 'matched');

    $match = RankedMatch::query()->latest('id')->firstOrFail();

    $response = $this->actingAs($firstUser)
        ->get(route('api.v1.ranked.stream', [
            'max_ticks' => 1,
            'sleep_ms' => 0,
        ]));

    $response->assertOk();

    expect((string) $response->headers->get('content-type'))->toContain('text/event-stream');

    $content = $response->streamedContent();

    expect($content)->toContain('event: overview.sync');
    expect($content)->toContain('event: queue.matched');
    expect($content)->toContain('"event":"queue.matched"');
    expect($content)->toContain('"match_id":"'.$match->public_id.'"');
    expect($content)->toContain('"starting_in_seconds"');
    expect($content)->not->toContain('event: queue.queued');
});
