<?php

use App\Models\LicenseCategory;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\ConfirmAccountDeletion;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    $this->withoutMiddleware(ThrottleRequests::class);
});

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    UserProfile::factory()
        ->for($user, 'user')
        ->for($category, 'targetCategory')
        ->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('product profile can be updated from the web profile page', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.product.update'), [
            'display_name' => 'Jan',
            'target_category_id' => $category->getKey(),
            'exam_date' => '2026-05-01',
            'onboarding_step' => 'target_selected',
            'visual_explanations_mode' => 'before_answer',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $profile = $user->refresh()->profile;

    expect($profile)->not->toBeNull();
    expect($profile->display_name)->toBe('Jan');
    expect($profile->target_category_id)->toBe($category->getKey());
    expect($profile->exam_date?->toDateString())->toBe('2026-05-01');
    expect($profile->onboarding_step)->toBe('target_selected');
    expect($profile->visual_explanations_mode)->toBe('before_answer');
    expect($profile->visual_explanations_enabled)->toBeTrue();
});

test('legacy visual explanations boolean payload maps to the new mode', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.product.update'), [
            'visual_explanations_enabled' => false,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $profile = $user->refresh()->profile;

    expect($profile)->not->toBeNull();
    expect($profile->visual_explanations_mode)->toBe('off');
    expect($profile->visual_explanations_enabled)->toBeFalse();
});

test('product profile update can redirect back to the current page', function () {
    $user = User::factory()->create();
    $category = LicenseCategory::factory()->create([
        'code' => 'B',
        'name' => 'Kategoria B',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.product.update'), [
            'target_category_id' => $category->getKey(),
            'return_to' => '/dashboard',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/dashboard');

    expect($user->refresh()->profile?->target_category_id)->toBe($category->getKey());
});

test('profile information can be updated', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'current_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'verification-link-sent')
        ->assertRedirect('/verify-email');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('profile email is normalized without requiring a password for a case-only change', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'learner@example.com']);

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Learner',
        'email' => '  LEARNER@EXAMPLE.COM  ',
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect($user->refresh()->email)->toBe('learner@example.com');
    expect($user->email_verified_at)->not->toBeNull();
    Notification::assertNothingSent();
});

test('product profile does not redirect outside the application', function ($returnTo) {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.product.update'), [
        'return_to' => $returnTo,
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');
})->with([
    'protocol-relative URL' => '//example.com',
    'absolute URL' => 'https://example.com',
    'backslash URL' => '/\\example.com',
    'array value' => [['/dashboard']],
]);

test('current password is required when password enabled user changes email address', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('old@example.com', $user->email);
    $this->assertNotNull($user->email_verified_at);

    Notification::assertNothingSent();
});

test('incorrect current password blocks email address change', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'current_password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/profile');

    $this->assertSame('old@example.com', $user->refresh()->email);

    Notification::assertNothingSent();
});

test('oauth only user receives current email confirmation before email address changes', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password_login_enabled' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'OAuth User',
            'email' => 'new@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'email-change-confirmation-sent')
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('OAuth User', $user->name);
    $this->assertSame('old@example.com', $user->email);
    $this->assertNotNull($user->email_verified_at);

    Notification::assertSentTo($user, ConfirmEmailChange::class);
    Notification::assertNotSentTo($user, VerifyEmail::class);
});

test('signed email change confirmation page is displayed', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password_login_enabled' => false,
    ]);

    $url = (new ConfirmEmailChange('new@example.com'))->confirmationUrl($user);

    $this
        ->get($url)
        ->assertOk()
        ->assertSee('Zmienić adres e-mail?')
        ->assertSee('old@example.com')
        ->assertSee('new@example.com');

    $this->assertSame('old@example.com', $user->refresh()->email);
});

test('signed email change confirmation updates oauth only email and sends verification link', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password_login_enabled' => false,
    ]);

    $url = (new ConfirmEmailChange('new@example.com'))->confirmationUrl($user);

    $this
        ->actingAs($user)
        ->post($url)
        ->assertSessionHas('status', 'email-change-confirmed')
        ->assertRedirect('/verify-email');

    $user->refresh();

    $this->assertSame('new@example.com', $user->email);
    $this->assertNull($user->email_verified_at);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('email change confirmation rejects invalid current email hash', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password_login_enabled' => false,
    ]);

    $url = URL::temporarySignedRoute(
        'profile.email-change.confirm',
        now()->addMinutes(30),
        [
            'user' => $user->getKey(),
            'hash' => 'invalid-hash',
            'email' => Crypt::encryptString('new@example.com'),
        ],
    );

    $this
        ->get($url)
        ->assertForbidden();

    $this->assertSame('old@example.com', $user->refresh()->email);
});

test('email change confirmation rejects an address that becomes unavailable', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'password_login_enabled' => false,
    ]);

    $url = (new ConfirmEmailChange('new@example.com'))->confirmationUrl($user);

    User::factory()->create([
        'email' => 'new@example.com',
    ]);

    $this
        ->get($url)
        ->assertStatus(409);

    $this->assertSame('old@example.com', $user->refresh()->email);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);

    Notification::assertNothingSent();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('oauth only user can request account deletion confirmation link', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->post(route('profile.deletion.send'), [
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'account-deletion-link-sent')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
    Notification::assertSentTo($user, ConfirmAccountDeletion::class);
});

test('oauth only deletion confirmation requires matching email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->post(route('profile.deletion.send'), [
            'email' => 'other@example.com',
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
    Notification::assertNothingSent();
});

test('password enabled user cannot request email deletion confirmation link', function () {
    Notification::fake();

    $user = User::factory()->create([
        'password_login_enabled' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->post(route('profile.deletion.send'), [
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
    Notification::assertNothingSent();
});

test('signed account deletion confirmation page is displayed', function () {
    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    $url = (new ConfirmAccountDeletion)->confirmationUrl($user);

    $this
        ->get($url)
        ->assertOk()
        ->assertSee('Usunąć konto?')
        ->assertSee($user->email);

    $this->assertNotNull($user->fresh());
});

test('signed account deletion confirmation deletes oauth only account', function () {
    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    $url = (new ConfirmAccountDeletion)->confirmationUrl($user);

    $this
        ->post($url)
        ->assertRedirect('/');

    $this->assertNull($user->fresh());
});

test('account deletion confirmation rejects invalid hash', function () {
    $user = User::factory()->create([
        'password_login_enabled' => false,
    ]);

    $url = URL::temporarySignedRoute(
        'profile.deletion.confirm',
        now()->addMinutes(30),
        [
            'user' => $user->getKey(),
            'hash' => 'invalid-hash',
        ],
    );

    $this
        ->get($url)
        ->assertForbidden();

    $this->assertNotNull($user->fresh());
});
