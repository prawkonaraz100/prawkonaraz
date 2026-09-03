<?php

use App\Models\User;
use App\Models\UserIpHistory;
use App\Support\ReturningUserCookie;
use Inertia\Testing\AssertableInertia as Assert;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this
        ->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();
    $response
        ->assertRedirect(route('dashboard', absolute: false))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);

    expect(UserIpHistory::query()->where('user_id', $user->id)->where('source', 'logowanie')->first())
        ->not->toBeNull()
        ->ip_address->toBe('203.0.113.50');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('banned users can not authenticate using the login screen', function () {
    $user = User::factory()->create([
        'banned_at' => now(),
        'ban_reason' => 'Testowa blokada.',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response
        ->assertRedirect('/login')
        ->assertSessionHasErrors([
            'email' => 'To konto zostało zablokowane.',
        ]);
});

test('banned authenticated users are logged out from protected pages', function () {
    $user = User::factory()->create([
        'banned_at' => now(),
        'ban_reason' => 'Testowa blokada.',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'To konto zostało zablokowane.');

    $this->assertGuest();
});

test('authenticated requests create ip history entries', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
        ->get(route('dashboard'))
        ->assertRedirect(route('access.activate'))
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);

    expect(UserIpHistory::query()->where('user_id', $user->id)->where('source', 'www')->first())
        ->not->toBeNull()
        ->ip_address->toBe('198.51.100.77');
});

test('guest requests do not create returning user marker', function () {
    $this
        ->get('/')
        ->assertOk()
        ->assertCookieMissing(ReturningUserCookie::NAME);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response
        ->assertRedirect('/')
        ->assertCookie(ReturningUserCookie::NAME, ReturningUserCookie::VALUE);
});

test('get logout requests show confirmation without ending the session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/logout');

    $this->assertAuthenticatedAs($user);
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/LogoutConfirm'));
});
