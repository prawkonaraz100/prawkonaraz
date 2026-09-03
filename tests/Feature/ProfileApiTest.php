<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserProfile;

test('me profile api returns the product profile and active categories', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);
    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    LicenseCategory::factory()->create([
        'code' => 'A',
        'name' => 'Kategoria A',
        'is_active' => false,
        'sort_order' => 2,
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->for($category, 'targetCategory')
        ->create([
            'display_name' => 'Jan',
            'study_streak' => 3,
            'last_study_date' => today(),
            'exam_date' => '2026-05-01',
        ]);

    $this->actingAs($user)
        ->getJson(route('api.v1.me.profile.show'))
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Jan')
        ->assertJsonPath('data.target_category.code', 'B')
        ->assertJsonPath('data.study_streak', 3)
        ->assertJsonPath('data.exam_date', '2026-05-01')
        ->assertJsonCount(1, 'meta.categories')
        ->assertJsonPath('meta.categories.0.code', 'B');
});

test('me profile api can update product preferences', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'PB2',
        'name' => 'Kategoria B',
    ]);

    $this->actingAs($user)
        ->putJson(route('api.v1.me.profile.update'), [
            'display_name' => 'Jan',
            'target_category_id' => $category->getKey(),
            'exam_date' => '2026-06-15',
            'onboarding_step' => 'exam_planned',
        ])
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Jan')
        ->assertJsonPath('data.target_category_id', $category->getKey())
        ->assertJsonPath('data.target_category.code', 'PB2')
        ->assertJsonPath('data.exam_date', '2026-06-15')
        ->assertJsonPath('data.onboarding_step', 'exam_planned');

    $profile = $user->refresh()->profile;

    expect($profile)->not->toBeNull();
    expect($profile->display_name)->toBe('Jan');
    expect($profile->target_category_id)->toBe($category->getKey());
});

test('starting a study session syncs user profile activity streak', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
    ]);

    Question::factory()
        ->count(3)
        ->for($category, 'licenseCategory')
        ->create();

    UserProfile::factory()
        ->for($user, 'user')
        ->for($category, 'targetCategory')
        ->create([
            'study_streak' => 2,
            'last_study_date' => today()->subDay(),
        ]);

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 3,
        ])
        ->assertRedirect();

    $profile = $user->refresh()->profile;

    expect($profile->study_streak)->toBe(3);
    expect($profile->last_study_date?->toDateString())->toBe(today()->toDateString());
});
