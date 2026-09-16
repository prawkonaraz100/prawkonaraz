<?php

use App\Mail\ContactMessageMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

test('homepage rotates the contact advisor with the Warsaw weekday', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-07 12:00:00', 'Europe/Warsaw'));

    $this->get('/')
        ->assertOk()
        ->assertSee('data-contact-advisor-day="1"', false);

    Carbon::setTestNow(Carbon::parse('2026-09-08 12:00:00', 'Europe/Warsaw'));

    $this->get('/')
        ->assertOk()
        ->assertSee('data-contact-advisor-day="2"', false);

    Carbon::setTestNow();
});

test('contact form uses its dedicated rate limiter', function () {
    $route = app('router')->getRoutes()->getByName('about.contact.store');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:contact');
});

test('visitor can send a contact message from the homepage', function () {
    Mail::fake();
    Config::set('content.organization.email', 'kontakt@example.test');

    $response = $this
        ->from(route('home'))
        ->post(route('about.contact.store'), [
            'contact_name' => 'Anna',
            'contact_email' => 'anna@example.test',
            'contact_topic' => 'learning',
            'contact_message' => 'Mam pytanie dotyczące trybu nauki kategorii B.',
            'contact_consent' => '1',
            'website' => '',
        ]);

    $response
        ->assertRedirect(route('home').'#kontakt')
        ->assertSessionHas('contact_success');

    Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail): bool {
        return $mail->hasTo('kontakt@example.test')
            && $mail->messageData['name'] === 'Anna'
            && $mail->messageData['email'] === 'anna@example.test'
            && $mail->messageData['topic'] === 'Pytanie o naukę';
    });
});

test('contact form validates fields in its own error bag', function () {
    Mail::fake();

    $this
        ->from(route('home'))
        ->post(route('about.contact.store'), [
            'contact_name' => '',
            'contact_email' => 'niepoprawny-adres',
            'contact_topic' => 'unknown',
            'contact_message' => 'Za krótka',
        ])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors([
            'contact_name',
            'contact_email',
            'contact_topic',
            'contact_message',
            'contact_consent',
        ], null, 'contact');

    Mail::assertNothingSent();
});

test('contact form honeypot rejects automated submissions', function () {
    Mail::fake();

    $this
        ->from(route('home'))
        ->post(route('about.contact.store'), [
            'contact_name' => 'Bot',
            'contact_email' => 'bot@example.test',
            'contact_topic' => 'other',
            'contact_message' => 'Automatyczna wiadomość testowa.',
            'contact_consent' => '1',
            'website' => 'https://spam.example',
        ])
        ->assertSessionHasErrors(['website'], null, 'contact');

    Mail::assertNothingSent();
});
