<?php

use App\Models\User;
use App\Support\AppOnboardingCookie;
use App\Support\ReturningUserCookie;
use App\Support\SocialAuthProviderClient;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

test('first time guest sees the mobile app onboarding', function () {
    $this->get(route('app.entry'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn (Assert $page) => $page->component('App/Onboarding'));
});

test('guest who finished onboarding goes straight to login', function () {
    $this->withCookie(AppOnboardingCookie::NAME, AppOnboardingCookie::VALUE)
        ->get(route('app.entry'))
        ->assertRedirect(route('app.login'));
});

test('returning guest does not see onboarding again', function () {
    $this->withCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE)
        ->get(route('app.entry'))
        ->assertRedirect(route('app.login'));
});

test('onboarding can lead to registration and remembers the choice', function () {
    $this->post(route('app.onboarding.complete'), ['destination' => 'register'])
        ->assertRedirect(route('app.register'))
        ->assertCookie(AppOnboardingCookie::NAME, AppOnboardingCookie::VALUE);
});

test('onboarding can lead to login and remembers the choice', function () {
    $this->post(route('app.onboarding.complete'), ['destination' => 'login'])
        ->assertRedirect(route('app.login'))
        ->assertCookie(AppOnboardingCookie::NAME, AppOnboardingCookie::VALUE);
});

test('mobile login and registration have dedicated noindex views', function () {
    $this->get(route('app.login'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn (Assert $page) => $page
            ->component('App/AuthFullScreen')
            ->where('mode', 'login'));

    $this->get(route('app.register'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn (Assert $page) => $page
            ->component('App/AuthFullScreen')
            ->where('mode', 'register')
            ->has('categories'));
});

test('mobile auth pages do not replace the public website forms', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    $this->get(route('register'))->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
});

test('invalid login submitted from the app returns to mobile login', function () {
    $this->from(route('app.login'))
        ->post(route('login'), [
            'email' => 'missing@example.test',
            'password' => 'incorrect-password',
        ])
        ->assertRedirect(route('app.login'))
        ->assertSessionHasErrors('email');
});

test('social login errors return to the corresponding mobile form', function () {
    $providerClient = \Mockery::mock(SocialAuthProviderClient::class);
    $providerClient->shouldReceive('assertSupported')->twice();
    $providerClient->shouldReceive('user')->twice()->andThrow(
        ValidationException::withMessages(['provider' => 'Nie udało się zalogować.']),
    );
    app()->instance(SocialAuthProviderClient::class, $providerClient);

    foreach (['login' => null, 'register' => 123] as $destination => $categoryId) {
        $state = 'app-'.$destination;
        $this->withSession([
            'social_auth.'.$state => [
                'provider' => 'google',
                'intent' => 'login',
                'target_category_id' => $categoryId,
                'surface' => 'app',
            ],
        ])->get(route('social.callback', [
            'provider' => 'google',
            'state' => $state,
            'code' => 'oauth-code',
        ]))
            ->assertRedirect(route('app.'.$destination))
            ->assertSessionHasErrors('provider');
    }
});

test('onboarding destination is restricted to auth pages', function () {
    $this->post(route('app.onboarding.complete'), ['destination' => 'https://example.com'])
        ->assertSessionHasErrors('destination')
        ->assertCookieMissing(AppOnboardingCookie::NAME);
});

test('authenticated app entry keeps the existing post login checks', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('app.entry'))
        ->assertRedirect(route('dashboard'));
});

test('installed PWA starts at the app entry and client routes are exposed', function () {
    $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, 512, JSON_THROW_ON_ERROR);

    expect($manifest['start_url'])->toStartWith('/app?');
    expect(config('ziggy.groups.app'))->toContain('app.login', 'app.register', 'app.onboarding.complete', 'profile.edit');
});
