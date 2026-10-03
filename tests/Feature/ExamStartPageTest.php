<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

test('exam shortcut opens a dedicated start screen with the selected category and real progress', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    UserProfile::factory()->for($user)->create(['target_category_id' => $category->getKey()]);
    Question::factory()->for($category, 'licenseCategory')->create();

    $this->actingAs($user)
        ->get(route('session.index', ['widok' => 'testy']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/ExamStart')
            ->where('category.id', $category->getKey())
            ->where('category.code', 'B')
            ->where('courseProgress.answered_questions', 0)
            ->where('activeSession', null)
        );
});

test('the dedicated exam start screen keeps the full product access gate', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->categoryB()->create();
    UserProfile::factory()->for($user)->create(['target_category_id' => $category->getKey()]);
    Question::factory()->for($category, 'licenseCategory')->create();

    $this->actingAs($user)
        ->get(route('session.index', ['widok' => 'testy']))
        ->assertRedirect(route('access.activate'));
});
