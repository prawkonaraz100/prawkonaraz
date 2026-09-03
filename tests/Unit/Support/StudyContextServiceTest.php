<?php

use App\Models\User;
use App\Models\UserProfile;
use App\Support\StudyContextService;
use App\Support\UserProfileService;

uses(Tests\TestCase::class);

test('users without a profile default visual explanations to after incorrect', function () {
    $user = User::factory()->create();

    expect($user->profile)->toBeNull();

    $service = app(StudyContextService::class);

    expect($service->visualExplanationsMode($user))
        ->toBe(UserProfileService::VISUAL_EXPLANATIONS_MODE_AFTER_INCORRECT)
        ->and($service->visualExplanationsEnabled($user))
        ->toBeTrue();
});

test('users with profile set to off keep visual explanations disabled', function () {
    $user = User::factory()->create();

    UserProfile::factory()->for($user, 'user')->create([
        'visual_explanations_enabled' => false,
        'visual_explanations_mode' => UserProfileService::VISUAL_EXPLANATIONS_MODE_OFF,
    ]);

    $service = app(StudyContextService::class);

    expect($service->visualExplanationsMode($user->fresh()))
        ->toBe(UserProfileService::VISUAL_EXPLANATIONS_MODE_OFF)
        ->and($service->visualExplanationsEnabled($user->fresh()))
        ->toBeFalse();
});
