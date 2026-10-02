<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\User;
use App\Support\SocialAuthProviderClient;
use App\Support\SocialProviderUser;
use App\Support\UserProfileService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;

function registrationRecoveryCategory(): LicenseCategory
{
    $category = LicenseCategory::factory()->categoryB()->create();
    Question::factory()->for($category, 'licenseCategory')->create();

    return $category;
}

function registrationRecoveryPayload(LicenseCategory $category): array
{
    return ['name' => 'Recovery Test', 'email' => 'Recovery@example.test', 'password' => 'password',
        'password_confirmation' => 'password', 'target_category_id' => $category->id];
}

test('registration normalizes email and signed links survive guest login without weakening verification', function () {
    Notification::fake();
    $payload = registrationRecoveryPayload(registrationRecoveryCategory());
    $payload['email'] = '  Recovery@Example.Test  ';
    $this->post('/register', $payload)->assertRedirect(route('verification.notice'));
    $user = User::where('email', 'recovery@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    $url = Notification::sent($user, VerifyEmail::class)->first()->toMail($user)->viewData['verificationUrl'];
    $this->get('/nauka')->assertRedirect(route('verification.notice'));
    $this->post('/logout');
    Auth::forgetGuards();
    $this->get($url)->assertRedirect(route('login'));
    $this->post('/login', ['email' => ' Recovery@Example.Test ', 'password' => 'password'])->assertRedirect($url);
    $this->get($url)->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get($url)->assertRedirect();
    $other = User::factory()->unverified()->create();
    $this->actingAs($other)->get($url)->assertForbidden()->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerificationLinkProblem')->where('problem', 'wrong-account'));
    expect($other->fresh()->hasVerifiedEmail())->toBeFalse();
    $otherUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $other->id, 'hash' => sha1($other->email)]);
    $this->get($otherUrl.'tampered')->assertForbidden()->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerificationLinkProblem')->where('problem', 'invalid-link'));
    $wrongEmailUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $other->id, 'hash' => sha1('old@example.test')]);
    $this->get($wrongEmailUrl)->assertForbidden()->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerificationLinkProblem')->where('problem', 'invalid-link'));
    $this->travel(61)->minutes();
    $this->get($otherUrl)->assertForbidden()->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerificationLinkProblem')->where('problem', 'invalid-link'));
    expect($other->fresh()->hasVerifiedEmail())->toBeFalse();
    $this->travelBack();
});

test('SMTP failure preserves registered account login and shows retry errors without success status', function () {
    $category = registrationRecoveryCategory();
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')->twice()->andThrow(new TransportException('Simulated SMTP outage'));
    app()->instance(Dispatcher::class, $dispatcher);
    $this->post('/register', registrationRecoveryPayload($category))
        ->assertRedirect(route('verification.notice'))->assertSessionHas('status', 'verification-link-failed');
    $user = User::where('email', 'recovery@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->profile)->not->toBeNull();
    expect($user->hasVerifiedEmail())->toBeFalse();
    $this->get('/verify-email')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Auth/VerifyEmail')->where('status', 'verification-link-failed'));
    $this->from('/verify-email')->post(route('verification.send'))
        ->assertRedirect('/verify-email')->assertSessionHasErrors('email')->assertSessionMissing('status');
    expect(User::where('email', 'recovery@example.test')->count())->toBe(1);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('registration rolls back account if profile creation fails', function () {
    $category = registrationRecoveryCategory();
    $profileService = Mockery::mock(UserProfileService::class);
    $profileService->shouldReceive('update')->once()->andThrow(new RuntimeException('Simulated profile failure'));
    app()->instance(UserProfileService::class, $profileService);
    $this->post('/register', registrationRecoveryPayload($category))->assertStatus(500);
    expect(User::where('email', 'recovery@example.test')->exists())->toBeFalse();
    $this->assertGuest();
});

test('registration limit blocks execution and returns usable form errors without flashing passwords', function () {
    Notification::fake();
    $category = registrationRecoveryCategory();
    for ($i = 0; $i < 5; $i++) {
        $this->from('/register')->post('/register', [])->assertSessionHasErrors('email');
    }
    $this->from('/register')->post('/register', registrationRecoveryPayload($category))
        ->assertRedirect('/register')->assertSessionHasErrors('email')->assertHeader('Retry-After')
        ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
    expect(User::where('email', 'recovery@example.test')->exists())->toBeFalse();
    Notification::assertNothingSent();
    $this->postJson('/register', registrationRecoveryPayload($category))->assertStatus(429)->assertJsonValidationErrors('email');
    $this->travel(61)->seconds();
    $this->post('/register', registrationRecoveryPayload($category))->assertRedirect(route('verification.notice'));
    Notification::assertCount(1);
    $this->travelBack();
});

test('verification resend limit shows form errors and is independent of link verification and other users', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($i = 0; $i < 6; $i++) {
        $this->from('/verify-email')->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    }
    $this->post(route('verification.send'))->assertRedirect('/verify-email')->assertSessionHasErrors('email')->assertHeader('Retry-After');
    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    $this->postJson(route('verification.send'))->assertStatus(429)->assertJsonValidationErrors('email');
    $url = Notification::sent($user, VerifyEmail::class)->first()->toMail($user)->viewData['verificationUrl'];
    $this->get($url)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $other = User::factory()->unverified()->create();
    $this->actingAs($other)->post(route('verification.send'))->assertSessionHas('status', 'verification-link-sent');
    Notification::assertSentToTimes($other, VerifyEmail::class, 1);
});

test('social registration recovers login when SMTP fails for a new unverified account', function () {
    $category = registrationRecoveryCategory();
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')->twice()->andThrow(new TransportException('Simulated SMTP outage'));
    app()->instance(Dispatcher::class, $dispatcher);
    foreach (['facebook', 'google'] as $provider) {
        $client = Mockery::mock(SocialAuthProviderClient::class);
        $client->shouldReceive('assertSupported')->andReturnNull();
        $client->shouldReceive('user')->once()->andReturn(new SocialProviderUser(
            id: 'recovery-'.$provider, email: $provider.'@example.test', name: 'Social Recovery',
        ));
        app()->instance(SocialAuthProviderClient::class, $client);
        $this->withSession(['social_auth.recovery-state' => ['provider' => $provider, 'intent' => 'login', 'target_category_id' => $category->id]])
            ->get('/auth/'.$provider.'/callback?state=recovery-state')
            ->assertRedirect(route('verification.notice'))->assertSessionHas('status', 'verification-link-failed');
        $user = User::where('email', $provider.'@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        expect($user->hasVerifiedEmail())->toBeFalse();
        expect($user->socialAccounts()->count())->toBe(1);
        Auth::logout();
        Auth::forgetGuards();
    }
});
