<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

test('ops test mail refuses non-delivering mailers', function () {
    Config::set('mail.default', 'log');

    $this->artisan('ops:test-mail', ['to' => 'ops@example.test'])
        ->expectsOutputToContain('MAIL_MAILER=log nie wysyla prawdziwych maili.')
        ->assertFailed();
});
test('ops test mail sends through configured mailer', function () {
    Mail::fake();

    Config::set('mail.default', 'smtp');
    Config::set('mail.from.address', 'kontakt@prawkonaraz.pl');
    Config::set('mail.from.name', 'prawkonaraz.pl');
    Config::set('mail.mailers.smtp.host', 'ssl0.ovh.net');
    Config::set('mail.mailers.smtp.port', 465);
    Config::set('mail.mailers.smtp.scheme', 'smtps');

    $this->artisan('ops:test-mail', [
        'to' => 'ops@example.test',
        '--subject' => 'Test SMTP',
    ])
        ->expectsOutputToContain('Mailer: smtp')
        ->expectsOutputToContain('Wyslano mail testowy do ops@example.test.')
        ->assertSuccessful();
});
