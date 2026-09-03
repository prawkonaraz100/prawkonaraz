<?php

use App\Models\LicenseCategory;
use App\Models\ProductAccessGrant;
use App\Models\Question;
use App\Models\User;
use App\Support\ModeratorAccountProvisioningService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

function createModeratorProvisioningCategory(): LicenseCategory
{
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    return $category;
}

test('moderator can create a full account with start password category and 90 day grant', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();

    $this->actingAs($moderator)
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'full',
            'name' => 'Nowy Kursant',
            'email' => 'nowy.kursant@example.com',
            'target_category_id' => $category->getKey(),
        ])
        ->assertRedirect(route('moderator.accounts.index'))
        ->assertSessionHas('moderator_created_account.start_password');

    $createdAccount = session('moderator_created_account');
    $user = User::query()->where('email', 'nowy.kursant@example.com')->firstOrFail();
    $grant = ProductAccessGrant::query()
        ->where('user_id', $user->getKey())
        ->where('source', ProductAccessGrant::SOURCE_MODERATOR_GRANT)
        ->first();

    expect($user->requires_password_change)->toBeTrue()
        ->and($user->is_temporary_account)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->moderator_owner_id)->toBe($moderator->getKey())
        ->and($user->profile?->target_category_id)->toBe($category->getKey())
        ->and(Hash::check($createdAccount['start_password'], $user->password))->toBeTrue()
        ->and($grant)->not->toBeNull()
        ->and($grant?->expires_at?->between(now()->addDays(89), now()->addDays(91)))->toBeTrue();
});

test('moderator can create a temporary account without knowing email', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();

    $this->actingAs($moderator)
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'temporary',
            'name' => 'Konto Tymczasowe',
            'target_category_id' => $category->getKey(),
        ])
        ->assertRedirect(route('moderator.accounts.index'))
        ->assertSessionHas('moderator_created_account.start_password');

    $createdAccount = session('moderator_created_account');
    $user = User::query()->where('email', $createdAccount['login_email'])->firstOrFail();

    expect($createdAccount['login_email'])->toContain('@moderator.local')
        ->and($user->is_temporary_account)->toBeTrue()
        ->and($user->requires_password_change)->toBeTrue()
        ->and($user->temporary_account_expires_at?->between(now()->addDays(13), now()->addDays(15)))->toBeTrue()
        ->and($user->productAccessGrants()->where('source', ProductAccessGrant::SOURCE_MODERATOR_GRANT)->exists())->toBeTrue();
});

test('moderator cannot create account over own quota', function () {
    $moderator = User::factory()->moderator()->create([
        'moderator_quota' => 0,
    ]);
    $category = createModeratorProvisioningCategory();

    $this->actingAs($moderator)
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'full',
            'name' => 'Poza Limitem',
            'email' => 'poza-limitem@example.com',
            'target_category_id' => $category->getKey(),
        ])
        ->assertStatus(409);
});

test('expired unclaimed temporary account releases moderator quota slot', function () {
    $moderator = User::factory()->moderator()->create([
        'moderator_quota' => 1,
    ]);
    $category = createModeratorProvisioningCategory();

    User::factory()
        ->temporaryModeratorAccount($moderator)
        ->create([
            'temporary_account_expires_at' => now()->subDay(),
        ]);

    expect($moderator->refresh()->moderatorAccountsUsed())->toBe(0)
        ->and($moderator->moderatorQuotaRemaining())->toBe(1);

    $this->actingAs($moderator)
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'full',
            'name' => 'Nowy Slot',
            'email' => 'nowy-slot@example.com',
            'target_category_id' => $category->getKey(),
        ])
        ->assertRedirect(route('moderator.accounts.index'));

    expect(User::query()->where('email', 'nowy-slot@example.com')->exists())->toBeTrue()
        ->and($moderator->refresh()->moderatorAccountsUsed())->toBe(1)
        ->and($moderator->moderatorQuotaRemaining())->toBe(0);
});

test('existing email does not create duplicate account', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();
    User::factory()->create([
        'email' => 'duplikat@example.com',
    ]);

    $this->actingAs($moderator)
        ->from(route('moderator.accounts.index'))
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'full',
            'name' => 'Duplikat',
            'email' => 'duplikat@example.com',
            'target_category_id' => $category->getKey(),
        ])
        ->assertRedirect(route('moderator.accounts.index'))
        ->assertSessionHasErrors('email');

    expect(User::query()->where('email', 'duplikat@example.com')->count())->toBe(1);
});

test('start password is visible on the next moderator panel response only', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();

    $this->actingAs($moderator)
        ->post(route('moderator.accounts.store'), [
            'account_type' => 'full',
            'name' => 'Jednorazowe Hasło',
            'email' => 'jednorazowe@example.com',
            'target_category_id' => $category->getKey(),
        ])
        ->assertRedirect(route('moderator.accounts.index'));

    $startPassword = session('moderator_created_account.start_password');

    $this->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('createdAccount.start_password', $startPassword)
        );

    $this->get(route('moderator.accounts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('createdAccount', null)
        );
});

test('full moderator account must change password before email verification and product entry', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();
    $createdAccount = app(ModeratorAccountProvisioningService::class)->create($moderator, [
        'account_type' => 'full',
        'name' => 'Pełne Konto',
        'email' => 'pelne@example.com',
        'target_category_id' => $category->getKey(),
    ]);
    $account = User::query()->where('email', 'pelne@example.com')->firstOrFail();

    $this->post(route('login'), [
        'email' => $account->email,
        'password' => $createdAccount['start_password'],
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))
        ->assertRedirect(route('password.force.edit'));

    $this->get(route('password.force.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/ForcePasswordChange')
        );

    $this->put(route('password.force.update'), [
        'current_password' => $createdAccount['start_password'],
        'password' => 'NoweHaslo123!',
        'password_confirmation' => 'NoweHaslo123!',
    ])->assertRedirect(route('dashboard'));

    expect($account->refresh()->requires_password_change)->toBeFalse()
        ->and($account->email_verified_at)->toBeNull();

    $this->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('temporary account must be claimed with real email before normal onboarding', function () {
    Notification::fake();

    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();
    $createdAccount = app(ModeratorAccountProvisioningService::class)->create($moderator, [
        'account_type' => 'temporary',
        'name' => 'Tymczasowy Kursant',
        'target_category_id' => $category->getKey(),
    ]);
    $account = User::query()->where('email', $createdAccount['login_email'])->firstOrFail();

    $this->post(route('login'), [
        'email' => $account->email,
        'password' => $createdAccount['start_password'],
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))
        ->assertRedirect(route('account.claim.edit'));

    $this->get(route('account.claim.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/ClaimTemporaryAccount')
            ->where('technicalEmail', $createdAccount['login_email'])
        );

    $this->put(route('account.claim.update'), [
        'email' => 'realny.kursant@example.com',
        'current_password' => $createdAccount['start_password'],
        'password' => 'NoweHaslo123!',
        'password_confirmation' => 'NoweHaslo123!',
    ])->assertRedirect(route('verification.notice'));

    $account->refresh();

    expect($account->email)->toBe('realny.kursant@example.com')
        ->and($account->is_temporary_account)->toBeFalse()
        ->and($account->requires_password_change)->toBeFalse()
        ->and($account->claimed_at)->not->toBeNull()
        ->and($account->email_verified_at)->toBeNull()
        ->and($account->temporary_account_expires_at)->toBeNull();
});

test('expired temporary account cannot log in', function () {
    $moderator = User::factory()->moderator()->create();
    $category = createModeratorProvisioningCategory();
    $createdAccount = app(ModeratorAccountProvisioningService::class)->create($moderator, [
        'account_type' => 'temporary',
        'name' => 'Wygasły Kursant',
        'target_category_id' => $category->getKey(),
    ]);

    User::query()
        ->where('email', $createdAccount['login_email'])
        ->firstOrFail()
        ->forceFill(['temporary_account_expires_at' => now()->subDay()])
        ->save();

    $this->post(route('login'), [
        'email' => $createdAccount['login_email'],
        'password' => $createdAccount['start_password'],
    ])->assertSessionHasErrors('email');
});
