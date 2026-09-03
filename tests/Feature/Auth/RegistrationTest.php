<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ReturningUserCookie;

test('registration screen can be rendered', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->for($category, 'licenseCategory')->create();

    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('categories.0.code', 'B')
        );
});

test('new users can register', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->for($category, 'licenseCategory')->create();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $this->assertAuthenticated();
    $response
        ->assertRedirect(route('verification.notice', absolute: false))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->profile)->not->toBeNull();
    expect($user?->profile?->tier)->toBe('free');
    expect($user?->profile?->target_category_id)->toBe($category->getKey());
    expect($user?->profile?->preferred_learning_track)->toBe(UserProfile::LEARNING_TRACK_PJM);
    expect($user?->profile?->onboarding_step)->toBe('target_category_locked');
    expect($user?->profile?->visual_explanations_enabled)->toBeTrue();
    expect($user?->profile?->visual_explanations_mode)->toBe('after_incorrect');
});

test('new users can leave learning track undecided when registering', function () {
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    Question::factory()->for($category, 'licenseCategory')->create();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'undecided@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'target_category_id' => $category->getKey(),
    ]);

    $user = User::query()->where('email', 'undecided@example.com')->first();

    expect($user?->profile?->preferred_learning_track)->toBe(UserProfile::LEARNING_TRACK_UNDECIDED);
});

test('new users must select a target category when registering', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('target_category_id');
    $this->assertGuest();
});
