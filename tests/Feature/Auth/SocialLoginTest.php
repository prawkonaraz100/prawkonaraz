<?php

use App\Models\LicenseCategory;
use App\Models\ProductAccessGrant;
use App\Models\Question;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSocialAccount;
use App\Support\ProductAccessResolver;
use App\Support\ReturningUserCookie;
use App\Support\SocialAuthProviderClient;
use App\Support\SocialProviderUser;
use App\Support\UserProfileService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

function createSocialAuthCategory(): LicenseCategory
{
    $category = LicenseCategory::factory()->categoryB()->create([
        'sort_order' => 1,
    ]);

    Question::factory()
        ->for($category, 'licenseCategory')
        ->create();

    return $category;
}

function mockSocialProviderUser(SocialProviderUser $providerUser): void
{
    $mock = Mockery::mock(SocialAuthProviderClient::class);
    $mock->shouldReceive('assertSupported')->andReturnNull();
    $mock->shouldReceive('user')->andReturn($providerUser);

    app()->instance(SocialAuthProviderClient::class, $mock);
}

test('social registration creates user with locked category and linked provider', function () {
    $category = createSocialAuthCategory();
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-123',
        email: 'Social.User@example.com',
        name: 'Social User',
        emailVerified: true,
    ));

    $this
        ->withSession([
            'social_auth.state-1' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => $category->getKey(),
                'preferred_learning_track' => UserProfile::LEARNING_TRACK_PJM,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-1',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);

    $user = User::query()->where('email', 'social.user@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);

    expect($user->password_login_enabled)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->profile?->target_category_id)->toBe($category->getKey())
        ->and($user->profile?->preferred_learning_track)->toBe(UserProfile::LEARNING_TRACK_PJM)
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();
});

test('google registration without verified email creates unverified user and sends verification mail', function () {
    Notification::fake();
    $category = createSocialAuthCategory();
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-untrusted-new',
        email: 'Google.Untrusted@example.com',
        name: 'Google Untrusted',
        emailVerified: null,
    ));

    $this
        ->withSession([
            'social_auth.state-google-untrusted-new' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => $category->getKey(),
                'preferred_learning_track' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-google-untrusted-new',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'google.untrusted@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);

    expect($user->email_verified_at)->toBeNull()
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('google registration with explicitly unverified email is rejected', function () {
    Notification::fake();
    $category = createSocialAuthCategory();
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-unverified',
        email: 'google-unverified@example.com',
        name: 'Google Unverified',
        emailVerified: false,
    ));

    $this
        ->from(route('register'))
        ->withSession([
            'social_auth.state-google-unverified' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => $category->getKey(),
                'preferred_learning_track' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-google-unverified',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('provider');

    $this->assertGuest();

    expect(User::query()->where('email', 'google-unverified@example.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

test('facebook registration creates unverified user and sends verification mail', function () {
    Notification::fake();
    $category = createSocialAuthCategory();
    mockSocialProviderUser(new SocialProviderUser(
        id: 'facebook-untrusted-new',
        email: 'Facebook.User@example.com',
        name: 'Facebook User',
        emailVerified: null,
    ));

    $this
        ->withSession([
            'social_auth.state-facebook-untrusted-new' => [
                'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
                'intent' => 'login',
                'target_category_id' => $category->getKey(),
                'preferred_learning_track' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
            'state' => 'state-facebook-untrusted-new',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'facebook.user@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);

    expect($user->email_verified_at)->toBeNull()
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_FACEBOOK)->exists())->toBeTrue();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('social login links existing user without creating duplicate and keeps paywall', function () {
    $category = createSocialAuthCategory();
    $user = User::factory()->create([
        'email' => 'existing@example.com',
    ]);
    app(UserProfileService::class)->update($user, [
        'target_category_id' => $category->getKey(),
        'onboarding_step' => 'target_category_locked',
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-existing',
        email: 'existing@example.com',
        name: 'Existing User',
        emailVerified: true,
    ));

    $this
        ->withSession([
            'social_auth.state-2' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-2',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->where('email', 'existing@example.com')->count())->toBe(1)
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();

    $this->get(route('dashboard'))
        ->assertRedirect(route('access.activate'));
});

test('untrusted social email cannot auto link existing user account', function () {
    $category = createSocialAuthCategory();
    $user = User::factory()->create([
        'email' => 'existing-facebook@example.com',
    ]);
    app(UserProfileService::class)->update($user, [
        'target_category_id' => $category->getKey(),
        'onboarding_step' => 'target_category_locked',
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'facebook-existing',
        email: 'existing-facebook@example.com',
        name: 'Existing Facebook User',
        emailVerified: null,
    ));

    $this
        ->from(route('login'))
        ->withSession([
            'social_auth.state-facebook-existing' => [
                'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
            'state' => 'state-facebook-existing',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('provider');

    $this->assertGuest();

    expect(User::query()->where('email', 'existing-facebook@example.com')->count())->toBe(1)
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_FACEBOOK)->exists())->toBeFalse();
});

test('existing linked account can log in even when provider does not return email verification signal', function () {
    $user = User::factory()->create([
        'email' => 'linked-facebook@example.com',
    ]);
    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
        'provider_user_id' => 'facebook-linked',
        'email' => 'linked-facebook@example.com',
        'linked_at' => now(),
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'facebook-linked',
        email: 'linked-facebook@example.com',
        name: 'Linked Facebook User',
        emailVerified: null,
    ));

    $this
        ->withSession([
            'social_auth.state-facebook-linked' => [
                'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
            'state' => 'state-facebook-linked',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);

    $this->assertAuthenticatedAs($user);
});

test('social login keeps existing moderator grant on linked account', function () {
    $category = createSocialAuthCategory();
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create([
        'email' => 'granted@example.com',
        'created_by_moderator_id' => $moderator->getKey(),
        'moderator_owner_id' => $moderator->getKey(),
    ]);
    app(UserProfileService::class)->update($user, [
        'target_category_id' => $category->getKey(),
        'onboarding_step' => 'target_category_locked',
    ]);
    $grant = ProductAccessGrant::query()->create([
        'user_id' => $user->getKey(),
        'source' => ProductAccessGrant::SOURCE_MODERATOR_GRANT,
        'status' => ProductAccessGrant::STATUS_ACTIVE,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(90),
        'granted_by_user_id' => $moderator->getKey(),
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-granted',
        email: 'granted@example.com',
        name: 'Granted User',
        emailVerified: true,
    ));

    $this
        ->withSession([
            'social_auth.state-granted' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-granted',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('dashboard', absolute: false));

    $decision = app(ProductAccessResolver::class)->forUser($user->refresh());

    expect(ProductAccessGrant::query()->whereKey($grant->getKey())->exists())->toBeTrue()
        ->and($decision->allowed)->toBeTrue()
        ->and($decision->source)->toBe(ProductAccessGrant::SOURCE_MODERATOR_GRANT)
        ->and($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();
});

test('new social account cannot be created without target category', function () {
    mockSocialProviderUser(new SocialProviderUser(
        id: 'facebook-123',
        email: 'new-facebook@example.com',
        name: 'Facebook User',
        emailVerified: true,
    ));

    $this
        ->withSession([
            'social_auth.state-3' => [
                'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
            'state' => 'state-3',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('target_category_id');

    expect(User::query()->where('email', 'new-facebook@example.com')->exists())->toBeFalse();
});

test('authenticated user can link social account with matching email', function () {
    $user = User::factory()->create([
        'email' => 'profile@example.com',
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'google-profile',
        email: 'profile@example.com',
        name: 'Profile User',
        emailVerified: true,
    ));

    $this
        ->actingAs($user)
        ->withSession([
            'social_auth.state-4' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'link',
                'user_id' => $user->getKey(),
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'state-4',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('profile.edit'));

    expect($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();
});

test('authenticated user can link facebook account with matching email without provider verification signal', function () {
    $user = User::factory()->create([
        'email' => 'profile-facebook@example.com',
    ]);
    mockSocialProviderUser(new SocialProviderUser(
        id: 'facebook-profile',
        email: 'profile-facebook@example.com',
        name: 'Profile Facebook User',
        emailVerified: null,
    ));

    $this
        ->actingAs($user)
        ->withSession([
            'social_auth.state-facebook-profile' => [
                'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
                'intent' => 'link',
                'user_id' => $user->getKey(),
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_FACEBOOK,
            'state' => 'state-facebook-profile',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('profile.edit'));

    expect($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_FACEBOOK)->exists())->toBeTrue();
});

test('social only user cannot unlink the last login method', function () {
    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-only',
        'email' => $user->email,
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.social.destroy', ['provider' => UserSocialAccount::PROVIDER_GOOGLE]))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('provider');

    expect($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeTrue();
});

test('user can unlink social account when password login is enabled', function () {
    $user = User::factory()->create([
        'password_login_enabled' => true,
    ]);

    UserSocialAccount::query()->create([
        'user_id' => $user->getKey(),
        'provider' => UserSocialAccount::PROVIDER_GOOGLE,
        'provider_user_id' => 'google-with-password',
        'email' => $user->email,
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.social.destroy', ['provider' => UserSocialAccount::PROVIDER_GOOGLE]))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'Metoda logowania została odpięta.');

    expect($user->socialAccounts()->where('provider', UserSocialAccount::PROVIDER_GOOGLE)->exists())->toBeFalse();
});

test('cancelled social login redirects to login with readable error', function () {
    $this
        ->from(route('login'))
        ->withSession([
            'social_auth.cancel-login' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'cancel-login',
            'error' => 'access_denied',
        ]))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'provider' => 'Logowanie przez Google zostało przerwane. Możesz spróbować ponownie.',
        ]);

    $this->assertGuest();
});

test('cancelled social registration redirects to register with readable error', function () {
    $category = createSocialAuthCategory();

    $this
        ->from(route('register'))
        ->withSession([
            'social_auth.cancel-register' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'login',
                'target_category_id' => $category->getKey(),
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'cancel-register',
            'error' => 'access_denied',
        ]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('provider');

    $this->assertGuest();
});

test('cancelled social linking redirects to profile with readable error', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->withSession([
            'social_auth.cancel-link' => [
                'provider' => UserSocialAccount::PROVIDER_GOOGLE,
                'intent' => 'link',
                'user_id' => $user->getKey(),
                'target_category_id' => null,
            ],
        ])
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'cancel-link',
            'error' => 'access_denied',
        ]))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('provider');

    $this->assertAuthenticatedAs($user);
});

test('expired social callback redirects to login with readable error', function () {
    $this
        ->from(route('login'))
        ->get(route('social.callback', [
            'provider' => UserSocialAccount::PROVIDER_GOOGLE,
            'state' => 'missing-state',
            'code' => 'oauth-code',
        ]))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'provider' => 'Sesja logowania społecznościowego wygasła. Spróbuj ponownie.',
        ]);

    $this->assertGuest();
});
