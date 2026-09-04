<?php

test('terms and privacy pages expose the service legal documents', function () {
    config()->set('content.organization.legal_name', 'PrawkoNaRaz Test');
    config()->set('content.legal_documents.operator_address', 'ul. Testowa 1, 00-001 Warszawa');
    config()->set('content.legal_documents.operator_tax_id', '1234567890');
    config()->set('content.legal_documents.privacy_email', 'privacy@example.test');

    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSeeText('Regulamin serwisu prawkonaraz.pl')
        ->assertSeeText('PrawkoNaRaz Test')
        ->assertSeeText('ul. Testowa 1, 00-001 Warszawa')
        ->assertSeeText('NIP: 1234567890')
        ->assertSee(route('legal.privacy', absolute: false), false)
        ->assertDontSeeText('Projekt lokalny');

    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSeeText('Polityka prywatności prawkonaraz.pl')
        ->assertSeeText('privacy@example.test')
        ->assertSeeText('Logowanie przez Google')
        ->assertSeeText('nie stanowi zgody na opcjonalną analitykę')
        ->assertSee(route('legal.terms', absolute: false), false)
        ->assertDontSeeText('Projekt lokalny');
});

test('incomplete operator data keeps legal drafts out of search results', function () {
    config()->set('content.organization.legal_name', null);
    config()->set('content.legal_documents.operator_address', null);

    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false)
        ->assertSeeText('Projekt lokalny');
});

test('public footer and login drawer link to the correct legal pages', function () {
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('legal.terms', absolute: false), false)
        ->assertSee(route('legal.privacy', absolute: false), false);

    $this->get(route('public.auth-drawers.show', ['drawer' => 'login']))
        ->assertOk()
        ->assertSeeText('Kontynuując z Google, akceptujesz');
});

test('homepage renders one tap legal notice for first time visitors when google identity is enabled', function () {
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.identity_enabled', true);
    config()->set('services.google.one_tap_enabled', true);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-google-one-tap-enabled="true"', false)
        ->assertSee('data-google-one-tap-host', false)
        ->assertSee('data-google-one-tap-button', false)
        ->assertDontSee('data-google-one-tap-prompt', false)
        ->assertSeeText('Wyraź zgodę i dołącz do Prawko na Raz')
        ->assertSeeText('Klikając Kontynuuj, aby dołączyć lub się zalogować');
});
