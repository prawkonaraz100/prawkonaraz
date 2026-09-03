<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Models\UserIpHistory;
use App\Models\UserProfile;
use App\Models\UserSocialAccount;
use App\Support\GoogleIdentityRegistrationSession;
use App\Support\GoogleIdentityTokenVerifier;
use App\Support\ReturningUserCookie;
use App\Support\SocialProviderUser;
use App\Support\UserProfileService;
use Illuminate\Validation\ValidationException;

function createGoogleIdentityCategory(): LicenseCategory
{
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    return $category;
}

function mockGoogleIdentityProviderUser(
    SocialProviderUser|ValidationException $providerUser,
    string $credential = 'valid-google-credential',
): void {
    $mock = Mockery::mock(GoogleIdentityTokenVerifier::class);

    if ($providerUser instanceof ValidationException) {
        $mock->shouldReceive('userFromCredential')
            ->with($credential)
            ->andThrow($providerUser);
    } else {
        $mock->shouldReceive('userFromCredential')
            ->with($credential)
            ->andReturn($providerUser);
    }

    app()->instance(GoogleIdentityTokenVerifier::class, $mock);
}

beforeEach(function (): void {
    config()->set('services.google.client_id', 'test-client.apps.googleusercontent.com');
    config()->set('services.google.identity_enabled', true);
});

test('google identity login logs in linked social account and records ip history', function () {
    $user = User::factory()->create([
        'email' => 'linked-google@example.com',
    ]);
    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-linked',
        'email' => 'old-linked-google@example.com',
        'display_name' => 'Old Name',
        'avatar_url' => null,
        'linked_at' => now()->subDay(),
    ]);
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-linked',
        email: 'linked-google@example.com',
        name: 'Linked Google',
        avatarUrl: 'https://lh3.googleusercontent.com/linked.jpg',
        emailVerified: true,
    ));

    $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.101'])
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertOk()
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE)
        ->assertJson([
            'redirect' => route('dashboard', absolute: false),
        ]);

    $this->assertAuthenticatedAs($user);

    $account = $user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->firstOrFail();

    expect($account->email)->toBe('linked-google@example.com')
        ->and($account->display_name)->toBe('Linked Google')
        ->and($account->avatar_url)->toBe('https://lh3.googleusercontent.com/linked.jpg')
        ->and(UserIpHistory::query()
            ->where('user_id', $user->getKey())
            ->where('source', 'google_identity_login')
            ->where('ip_address', '203.0.113.101')
            ->exists())->toBeTrue();
});

test('google identity login auto links existing account with trusted email', function () {
    $category = createGoogleIdentityCategory();
    $user = User::factory()->create([
        'email' => 'existing-google@example.com',
    ]);
    app(UserProfileService::class)->update($user, [
        'target_category_id' => $category->getKey(),
        'onboarding_step' => 'target_category_locked',
    ]);
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-existing',
        email: 'existing-google@example.com',
        name: 'Existing Google',
        emailVerified: true,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertOk()
        ->assertJson([
            'redirect' => route('dashboard', absolute: false),
        ]);

    $this->assertAuthenticatedAs($user);

    expect($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();
});

test('google identity login starts registration when google account is new', function () {
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-new',
        email: 'new-google@example.com',
        name: 'New Google',
        emailVerified: true,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertOk()
        ->assertCookieMissing(ReturningUserCookie::NAME)
        ->assertJson([
            'registration_required' => true,
            'redirect' => route('register', ['google_identity' => '1'], absolute: false),
        ])
        ->assertSessionHas(GoogleIdentityRegistrationSession::SESSION_KEY);

    $this->assertGuest();

    expect(User::query()->where('email', 'new-google@example.com')->exists())->toBeFalse()
        ->and(UserSocialAccount::query()->where('provider_user_id', 'google-new')->exists())->toBeFalse();
});

test('google identity registration creates social account after category selection', function () {
    $category = createGoogleIdentityCategory();
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-register',
        email: 'Register.Google@example.com',
        name: 'Register Google',
        avatarUrl: 'https://lh3.googleusercontent.com/register.jpg',
        emailVerified: true,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertOk()
        ->assertSessionHas(GoogleIdentityRegistrationSession::SESSION_KEY);

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.118'])
        ->post(route('register'), [
            'target_category_id' => $category->getKey(),
            'preferred_learning_track' => UserProfile::LEARNING_TRACK_CLASSIC,
        ]);

    $user = User::query()->where('email', 'register.google@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);

    $response
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE)
        ->assertSessionMissing(GoogleIdentityRegistrationSession::SESSION_KEY);

    $account = $user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->firstOrFail();

    expect($user->name)->toBe('Register Google')
        ->and($user->password_login_enabled)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->profile?->target_category_id)->toBe($category->getKey())
        ->and($user->profile?->preferred_learning_track)->toBe(UserProfile::LEARNING_TRACK_CLASSIC)
        ->and($account->provider_user_id)->toBe('google-register')
        ->and($account->avatar_url)->toBe('https://lh3.googleusercontent.com/register.jpg')
        ->and(UserIpHistory::query()
            ->where('user_id', $user->getKey())
            ->where('source', 'google_identity_registration')
            ->where('ip_address', '203.0.113.118')
            ->exists())->toBeTrue();
});

test('google identity registration keeps pending context when category is missing', function () {
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-missing-category',
        email: 'missing-category@example.com',
        name: 'Missing Category',
        emailVerified: true,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertOk()
        ->assertSessionHas(GoogleIdentityRegistrationSession::SESSION_KEY);

    $this
        ->post(route('register'), [
            'preferred_learning_track' => UserProfile::LEARNING_TRACK_CLASSIC,
        ])
        ->assertSessionHasErrors('target_category_id')
        ->assertSessionHas(GoogleIdentityRegistrationSession::SESSION_KEY);

    $this->assertGuest();

    expect(User::query()->where('email', 'missing-category@example.com')->exists())->toBeFalse()
        ->and(UserSocialAccount::query()->where('provider_user_id', 'google-missing-category')->exists())->toBeFalse();
});

test('google identity login rejects unverified google email', function () {
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-unverified',
        email: 'unverified-google@example.com',
        name: 'Unverified Google',
        emailVerified: false,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('provider');

    $this->assertGuest();
});

test('google identity login rejects invalid credentials', function () {
    mockGoogleIdentityProviderUser(ValidationException::withMessages([
        'credential' => 'Nie udało się potwierdzić logowania przez Google.',
    ]));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('credential');

    $this->assertGuest();
});

test('google identity login rejects banned linked users', function () {
    $user = User::factory()->create([
        'email' => 'banned-google@example.com',
        'banned_at' => now(),
        'ban_reason' => 'Testowa blokada.',
    ]);
    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-banned',
        'email' => 'banned-google@example.com',
        'linked_at' => now(),
    ]);
    mockGoogleIdentityProviderUser(new SocialProviderUser(
        id: 'google-banned',
        email: 'banned-google@example.com',
        name: 'Banned Google',
        emailVerified: true,
    ));

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertForbidden()
        ->assertCookieMissing(ReturningUserCookie::NAME)
        ->assertJson([
            'message' => 'To konto zostało zablokowane.',
        ]);

    $this->assertGuest();
});

test('google identity login returns service unavailable when disabled', function () {
    config()->set('services.google.identity_enabled', false);

    $this
        ->postJson(route('google.identity.login'), [
            'credential' => 'valid-google-credential',
        ])
        ->assertServiceUnavailable()
        ->assertJson([
            'message' => 'Logowanie przez Google nie jest jeszcze skonfigurowane.',
        ]);

    $this->assertGuest();
});
