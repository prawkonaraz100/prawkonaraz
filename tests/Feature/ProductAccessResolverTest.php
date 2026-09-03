<?php

use App\Models\ProductAccessGrant;
use App\Models\User;
use App\Support\ProductAccessResolver;

test('user helpers distinguish account roles and test accounts', function () {
    $admin = User::factory()->admin()->create();
    $moderator = User::factory()->moderator()->create();
    $student = User::factory()->create();
    $testAccount = User::factory()->testAccount()->create();

    expect($admin->isAdministrator())->toBeTrue()
        ->and($admin->isModerator())->toBeFalse()
        ->and($moderator->isModerator())->toBeTrue()
        ->and($moderator->isAdministrator())->toBeFalse()
        ->and($student->isStudent())->toBeTrue()
        ->and($testAccount->isTestAccount())->toBeTrue()
        ->and($testAccount->isStudent())->toBeFalse();
});

test('system accounts receive system product access', function () {
    $resolver = app(ProductAccessResolver::class);

    foreach ([
        User::factory()->admin()->create(),
        User::factory()->moderator()->create(),
        User::factory()->testAccount()->create(),
    ] as $user) {
        $decision = $resolver->forUser($user);

        expect($decision->allowed)->toBeTrue()
            ->and($decision->source)->toBe(ProductAccessGrant::SOURCE_SYSTEM)
            ->and($decision->grant)->toBeNull();
    }
});

test('banned users are denied before any product access source is considered', function () {
    $user = User::factory()->admin()->create([
        'banned_at' => now(),
        'ban_reason' => 'Testowa blokada.',
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('banned')
        ->and($decision->source)->toBeNull();
});

test('purchase access has priority over moderator access', function () {
    $user = User::factory()->create();

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeTrue()
        ->and($decision->source)->toBe(ProductAccessGrant::SOURCE_PURCHASE)
        ->and($decision->grant?->source)->toBe(ProductAccessGrant::SOURCE_PURCHASE);
});

test('moderator access is accepted when there is no active purchase', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create([
        'created_by_moderator_id' => $moderator->id,
        'moderator_owner_id' => $moderator->id,
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
        'granted_by_user_id' => $moderator->id,
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeTrue()
        ->and($decision->source)->toBe(ProductAccessGrant::SOURCE_MODERATOR_GRANT)
        ->and($decision->grant?->granted_by_user_id)->toBe($moderator->id);
});

test('unclaimed temporary accounts are denied before grants are considered', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->temporaryModeratorAccount($moderator)->create();

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
        'granted_by_user_id' => $moderator->id,
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('temporary_account_unclaimed')
        ->and($decision->source)->toBeNull();
});

test('accounts requiring a password change are denied before grants are considered', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create([
        'requires_password_change' => true,
        'created_by_moderator_id' => $moderator->id,
        'moderator_owner_id' => $moderator->id,
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
        'granted_by_user_id' => $moderator->id,
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('password_change_required')
        ->and($decision->source)->toBeNull();
});

test('expired revoked and future grants are ignored', function () {
    $user = User::factory()->create();

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->subDay(),
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
        'revoked_at' => now(),
    ]);

    ProductAccessGrant::query()->create([
        'user_id' => $user->id,
        'source' => ProductAccessGrant::SOURCE_PURCHASE,
        'starts_at' => now()->addDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $decision = app(ProductAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('missing_access');
});

test('administrator can increase moderator quota in model data', function () {
    $moderator = User::factory()->moderator()->create([
        'moderator_quota' => 45,
    ]);

    expect($moderator->moderatorQuotaLimit())->toBe(45);
});
