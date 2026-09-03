<?php

use App\Models\User;
use App\Models\UserIpHistory;

test('user ip history prune command removes entries older than configured threshold', function () {
    $user = User::factory()->create();

    UserIpHistory::query()->create([
        'user_id' => $user->id,
        'source' => 'www',
        'ip_address' => '203.0.113.1',
        'session_id' => 'old-session',
        'hit_count' => 1,
        'first_seen_at' => now()->subDays(120),
        'last_seen_at' => now()->subDays(120),
    ]);

    $recentHistory = UserIpHistory::query()->create([
        'user_id' => $user->id,
        'source' => 'panel',
        'ip_address' => '203.0.113.2',
        'session_id' => 'recent-session',
        'hit_count' => 3,
        'first_seen_at' => now()->subDays(10),
        'last_seen_at' => now()->subDays(1),
    ]);

    $this->artisan('ops:prune-user-ip-history', ['--days' => 90])
        ->expectsOutputToContain('Usunieto 1 wpisow historii IP starszych niz 90 dni.')
        ->assertSuccessful();

    expect(UserIpHistory::query()->count())->toBe(1);
    expect(UserIpHistory::query()->first()?->is($recentHistory))->toBeTrue();
});
