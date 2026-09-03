<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('make admin command creates a loggable admin account with the provided password', function () {
    $email = 'make-admin-command@test.local';

    $this->artisan('app:make-admin', [
        'email' => $email,
        '--name' => 'Make Admin Test',
        '--password' => 'change-me-now',
    ])->assertSuccessful();

    $user = User::query()->where('email', $email)->first();

    expect($user)->not->toBeNull();
    expect($user?->is_admin)->toBeTrue();
    expect($user?->email_verified_at)->not->toBeNull();
    expect(Hash::check('change-me-now', (string) $user?->password))->toBeTrue();
});
