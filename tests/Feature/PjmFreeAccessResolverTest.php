<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\PjmFreeAccessResolver;
use App\Support\ProductAccessResolver;

function createPjmFreeAccessCategory(string $externalId = '9001'): LicenseCategory
{
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => $externalId,
        'is_active' => true,
        'delivery_issue' => null,
    ]);

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => $externalId,
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    return $category;
}

it('allows free PJM access without unlocking the full product', function (): void {
    $category = createPjmFreeAccessCategory();
    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $pjmDecision = app(PjmFreeAccessResolver::class)->forUser($user->refresh());
    $productDecision = app(ProductAccessResolver::class)->forUser($user->refresh());

    expect($pjmDecision->allowed)->toBeTrue()
        ->and($pjmDecision->source)->toBe(PjmFreeAccessResolver::SOURCE_FREE_PJM)
        ->and($productDecision->allowed)->toBeFalse()
        ->and($productDecision->reason)->toBe('missing_access');
});

it('requires verified email for free PJM access', function (): void {
    $category = createPjmFreeAccessCategory('9002');
    $user = User::factory()->unverified()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $decision = app(PjmFreeAccessResolver::class)->forUser($user->refresh());

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('email_unverified');
});

it('requires the PJM learning track for free PJM access', function (): void {
    $category = createPjmFreeAccessCategory('9005');
    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_CLASSIC,
    ]);

    $decision = app(PjmFreeAccessResolver::class)->forUser($user->refresh());

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('pjm_track_not_selected');
});

it('denies free PJM access when the selected category has no PJM assets', function (): void {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->for($category, 'licenseCategory')->create([
        'external_id' => '9003',
        'is_active' => true,
        'delivery_issue' => null,
    ]);

    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $decision = app(PjmFreeAccessResolver::class)->forUser($user->refresh());

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('missing_pjm_assets');
});

it('keeps system accounts allowed for PJM regardless of profile category', function (): void {
    $user = User::factory()->testAccount()->unverified()->create();

    $decision = app(PjmFreeAccessResolver::class)->forUser($user);

    expect($decision->allowed)->toBeTrue()
        ->and($decision->source)->toBe(PjmFreeAccessResolver::SOURCE_SYSTEM);
});

it('denies banned accounts before PJM category checks', function (): void {
    $category = createPjmFreeAccessCategory('9004');
    $user = User::factory()->create([
        'banned_at' => now(),
    ]);

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
    ]);

    $decision = app(PjmFreeAccessResolver::class)->forUser($user->refresh());

    expect($decision->allowed)->toBeFalse()
        ->and($decision->reason)->toBe('banned');
});
