<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/profile');
});

test('social only user can set first password without current password', function () {
    $user = User::factory()->create([
        'password_login_enabled' => false,
        'password' => 'unused-social-password',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'password-set')
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->password_login_enabled)->toBeTrue();
    $this->assertTrue(Hash::check('new-password', $user->password));
});

test('password enabled user cannot update password without current password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect('/profile');

    $this->assertFalse(Hash::check('new-password', $user->refresh()->password));
});
