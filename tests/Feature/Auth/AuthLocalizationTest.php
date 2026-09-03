<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

test('auth mail notifications use polish copy', function () {
    App::setLocale('pl');

    $user = User::factory()->unverified()->create();

    $verifyMail = (new VerifyEmail())->toMail($user);
    $resetMail = (new ResetPassword('test-token'))->toMail($user);

    expect($verifyMail->subject)
        ->toBe('Potwierdź adres e-mail')
        ->and($verifyMail->introLines)
        ->toContain('Kliknij przycisk poniżej, aby potwierdzić adres e-mail.')
        ->and($verifyMail->actionText)
        ->toBe('Potwierdź adres e-mail')
        ->and($resetMail->subject)
        ->toBe('Reset hasła')
        ->and($resetMail->introLines)
        ->toContain('Otrzymujesz tę wiadomość, ponieważ poproszono o reset hasła dla Twojego konta.')
        ->and($resetMail->actionText)
        ->toBe('Zresetuj hasło');
});

test('auth status and validation messages are translated', function () {
    App::setLocale('pl');

    expect(trans('auth.failed'))
        ->toBe('Podany e-mail lub hasło są nieprawidłowe.')
        ->and(trans('passwords.sent'))
        ->toBe('Wysłaliśmy link do resetu hasła na podany adres e-mail.')
        ->and(Lang::get('Hello!'))
        ->toBe('Dzień dobry!')
        ->and(trans('validation.current_password', ['attribute' => 'obecne hasło']))
        ->toBe('Podane obecne hasło jest nieprawidłowe.');
});

