<?php

use App\Models\ProductAccessGrant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('moderator panel shows only accounts owned by the current moderator', function () {
    $moderator = User::factory()->moderator()->create(['name' => 'Moderator A']);
    $otherModerator = User::factory()->moderator()->create(['name' => 'Moderator B']);

    $ownAccount = User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'name' => 'Kursant Moderatora A',
            'email' => 'kursant-a@example.com',
        ]);

    User::factory()
        ->temporaryModeratorAccount($otherModerator)
        ->create([
            'name' => 'Kursant Moderatora B',
            'email' => 'kursant-b@example.com',
        ]);

    User::factory()->create([
        'name' => 'Konto bez moderatora',
        'email' => 'bez-moderatora@example.com',
    ]);

    $this->actingAs($moderator)
        ->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Moderator/Accounts/Index')
            ->where('panel.is_admin_view', false)
            ->where('panel.quota.used', 1)
            ->where('panel.quota.limit', User::DEFAULT_MODERATOR_QUOTA)
            ->has('accounts', 1)
            ->where('accounts.0.id', $ownAccount->getKey())
            ->where('accounts.0.name', 'Kursant Moderatora A')
        );
});

test('moderator panel exposes account status summary', function () {
    $moderator = User::factory()->moderator()->create();

    User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'name' => 'Do przejęcia',
            'temporary_account_expires_at' => now()->addDays(30),
        ]);

    User::factory()
        ->create([
            'name' => 'Aktywne',
            'moderator_owner_id' => $moderator->getKey(),
            'created_by_moderator_id' => $moderator->getKey(),
            'claimed_at' => now()->subDay(),
        ]);

    User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'name' => 'Wygasłe',
            'temporary_account_expires_at' => now()->subDay(),
        ]);

    User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'name' => 'Zablokowane',
            'banned_at' => now(),
            'ban_reason' => 'Testowa blokada.',
        ]);

    $this->actingAs($moderator)
        ->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Moderator/Accounts/Index')
            ->where('panel.summary.active', 1)
            ->where('panel.summary.pending_claim', 1)
            ->where('panel.summary.expired', 1)
            ->where('panel.summary.banned', 1)
            ->where('accounts', fn ($accounts): bool => collect($accounts)
                ->pluck('status.code')
                ->sort()
                ->values()
                ->all() === ['active', 'banned', 'expired', 'pending_claim'])
        );
});

test('moderator panel prefers moderator grant expiry when it exists', function () {
    $moderator = User::factory()->moderator()->create();
    $account = User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'temporary_account_expires_at' => now()->addDays(90),
        ]);
    $expiresAt = now()->addDays(14)->seconds(0);

    ProductAccessGrant::query()->create([
        'user_id' => $account->getKey(),
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'status' => ProductAccessGrant::STATUS_ACTIVE,
        'starts_at' => now()->subMinute(),
        'expires_at' => $expiresAt,
        'granted_by_user_id' => $moderator->getKey(),
    ]);

    $this->actingAs($moderator)
        ->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('accounts.0.access_expires_at', $expiresAt->toIso8601String())
        );
});

test('student cannot access moderator panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('moderator.accounts.index'))
        ->assertForbidden();
});

test('administrator can use moderator panel as an all accounts overview', function () {
    $admin = User::factory()->admin()->create();
    $moderatorA = User::factory()->moderator()->create();
    $moderatorB = User::factory()->moderator()->create();

    User::factory()
        ->temporaryModeratorAccount($moderatorA)
        ->create(['name' => 'Kursant A']);

    User::factory()
        ->temporaryModeratorAccount($moderatorB)
        ->create(['name' => 'Kursant B']);

    $this->actingAs($admin)
        ->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Moderator/Accounts/Index')
            ->where('panel.is_admin_view', true)
            ->where('panel.summary.total', 2)
            ->has('accounts', 2)
        );
});

test('dashboard sends moderator to the personal moderator panel', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->get(route('dashboard'))
        ->assertRedirect(route('moderator.accounts.index'));
});
