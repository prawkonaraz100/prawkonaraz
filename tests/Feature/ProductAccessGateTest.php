<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionSignLanguageAsset;
use App\Models\User;
use App\Models\UserProfile;
use Inertia\Testing\AssertableInertia as Assert;

function createProductAccessGateCategory(): LicenseCategory
{
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    return $category;
}

test('dashboard sends users without active access to activation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('access.activate'));
});

test('activation page renders for verified users without active access', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('access.activate'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Access/Activate')
            ->where('access.allowed', false)
            ->where('access.reason', 'missing_access')
            ->where('pricingUrl', route('public.pricing', absolute: false))
        );
});

test('product pages redirect users without active access to activation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertRedirect(route('access.activate'))
        ->assertSessionHas('status', 'Aktywuj pełną naukę, aby korzystać z klasycznych trybów.');
});

test('study hub allows free PJM access without unlocking full product', function () {
    $category = createProductAccessGateCategory();
    $question = Question::query()->where('license_category_id', $category->getKey())->firstOrFail();
    $question->forceFill(['external_id' => '9101'])->save();

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '9101',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('session.index'));

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('category.id', $category->getKey())
            ->where('access.full_product.allowed', false)
            ->where('pjm_module.available', true)
            ->where('pjm_module.preferred', true)
            ->where('pjm_module.show_entry_tile', true)
            ->where('pjm_module.starter_mode', true)
            ->where('pjm_module.coverage.pjm_questions', 1)
        );

    $this->actingAs($user)
        ->get(route('session.pjm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/PjmIndex')
            ->where('category.id', $category->getKey())
            ->where('coverage.pjm_questions', 1)
        );
});

test('free PJM access is not exposed when the user did not choose PJM at registration', function () {
    $category = createProductAccessGateCategory();
    $question = Question::query()->where('license_category_id', $category->getKey())->firstOrFail();
    $question->forceFill(['external_id' => '9102'])->save();

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '9102',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $user = User::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_CLASSIC,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('access.activate'));

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertRedirect(route('access.activate'));

    $this->actingAs($user)
        ->get(route('session.pjm'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Moduł PJM nie jest jeszcze dostępny dla tej kategorii.');
});

test('PJM preferred users with purchased access can use the full product without starter lock', function () {
    $category = createProductAccessGateCategory();
    $question = Question::query()->where('license_category_id', $category->getKey())->firstOrFail();
    $question->forceFill(['external_id' => '9103'])->save();

    QuestionSignLanguageAsset::factory()->create([
        'external_id' => '9103',
        'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
    ]);

    $user = User::factory()->withPurchasedAccess()->create();

    UserProfile::factory()->create([
        'user_id' => $user->getKey(),
        'target_category_id' => $category->getKey(),
        'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
    ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('category.id', $category->getKey())
            ->where('access.full_product.allowed', true)
            ->where('pjm_module.available', true)
            ->where('pjm_module.preferred', true)
            ->where('pjm_module.show_entry_tile', true)
            ->where('pjm_module.starter_mode', false)
        );

    $this->actingAs($user)
        ->get(route('session.pjm'))
        ->assertOk();
});

test('api product routes reject users without active access', function () {
    $user = User::factory()->create();
    $category = createProductAccessGateCategory();

    $this->actingAs($user)
        ->postJson(route('api.v1.sessions.store'), [
            'category_id' => $category->getKey(),
            'mode' => 'learn',
            'question_count' => 1,
        ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN');
});

test('users with an active grant can enter the product', function () {
    $user = User::factory()->withPurchasedAccess()->create();
    $category = createProductAccessGateCategory();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('category.id', $category->getKey())
        );
});

test('system exception accounts can enter the product', function () {
    $user = User::factory()->testAccount()->create();
    $category = createProductAccessGateCategory();

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Session/Index')
            ->where('category.id', $category->getKey())
        );
});

test('banned users cannot enter the product even with an active grant', function () {
    $user = User::factory()->withPurchasedAccess()->create([
        'banned_at' => now(),
        'ban_reason' => 'Testowa blokada.',
    ]);

    $this->actingAs($user)
        ->get(route('session.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'To konto zostało zablokowane.');
});
