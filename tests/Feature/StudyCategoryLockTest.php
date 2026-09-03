<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

function createLockedCategory(string $code): LicenseCategory
{
    $category = LicenseCategory::factory()->create([
        'code' => $code,
        'name' => 'Kategoria '.$code,
    ]);

    Question::factory()->for($category, 'licenseCategory')->create();

    return $category;
}

test('locked student sees only assigned category in study context', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createLockedCategory('B');
    createLockedCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->for($categoryB, 'targetCategory')
        ->create();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('category.code', 'B')
            ->where('studyContext.canSwitchCategory', false)
            ->where('studyContext.categoryLocked', true)
            ->where('studyContext.categories', fn ($categories) => collect($categories)->pluck('code')->all() === ['B'])
        );
});

test('locked student cannot start a web study session in a different category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createLockedCategory('B');
    $categoryC = createLockedCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->for($categoryB, 'targetCategory')
        ->create();

    $this->actingAs($user)
        ->post(route('study-sessions.store'), [
            'license_category_id' => $categoryC->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertForbidden();
});

test('locked student cannot start an api study session in a different category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createLockedCategory('B');
    $categoryC = createLockedCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->for($categoryB, 'targetCategory')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $categoryC->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

test('locked student cannot join ranked queue in a different category', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $categoryB = createLockedCategory('B');
    $categoryC = createLockedCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->for($categoryB, 'targetCategory')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.ranked.queue.join'), [
            'category_id' => $categoryC->getKey(),
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

test('system accounts can use every category', function () {
    $user = User::factory()->testAccount()->create();
    $categoryB = createLockedCategory('B');
    $categoryC = createLockedCategory('C');

    UserProfile::factory()
        ->for($user, 'user')
        ->for($categoryB, 'targetCategory')
        ->create();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $categoryC->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertCreated();
});

test('student can set missing category once but cannot change it later', function () {
    $user = User::factory()->create();
    $categoryB = createLockedCategory('B');
    $categoryC = createLockedCategory('C');

    $this->actingAs($user)
        ->patch(route('profile.product.update'), [
            'target_category_id' => $categoryB->getKey(),
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->profile?->target_category_id)->toBe($categoryB->getKey());

    $this->actingAs($user)
        ->patch(route('profile.product.update'), [
            'target_category_id' => $categoryC->getKey(),
        ])
        ->assertSessionHasErrors('target_category_id');

    expect($user->refresh()->profile?->target_category_id)->toBe($categoryB->getKey());
});
