<?php

test('google analytics tag is not rendered until it is enabled and configured', function () {
    config()->set('services.google_analytics.enabled', false);
    config()->set('services.google_analytics.measurement_id', 'G-TEST12345');

    $this->get('/')
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag/js', false)
        ->assertDontSee('google-analytics-consent', false);
});

test('google analytics tag renders on public blade pages when configured', function () {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-TEST12345');
    config()->set('services.google_analytics.consent_required', true);

    $this->get('/')
        ->assertOk()
        ->assertSee('G-TEST12345', false)
        ->assertSee('prawkonaraz.analyticsConsent.v1', false)
        ->assertSee('google-analytics-consent', false)
        ->assertSeeText('Akceptuję')
        ->assertSeeText('Nie teraz');
});

test('google analytics consent banner renders the cookie mascot asset', function () {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-TEST12345');
    config()->set('services.google_analytics.consent_required', true);

    expect(view('components.analytics.google-consent-banner')->render())
        ->toContain('analytics-consent__mascot');

    expect(file_get_contents(resource_path('views/components/analytics/google-consent-banner.blade.php')))
        ->toContain('resources/images/analytics/cookie-mascot-blink.webp')
        ->toContain('resources/images/analytics/cookie-mascot.png');
});

test('google analytics tag renders on inertia pages when configured', function () {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-TEST12345');
    config()->set('services.google_analytics.consent_required', true);

    $this->get('/kurs')
        ->assertOk()
        ->assertSee('G-TEST12345', false)
        ->assertSee('google-analytics-consent', false);
});

test('google analytics can load without the built in consent banner', function () {
    config()->set('services.google_analytics.enabled', true);
    config()->set('services.google_analytics.measurement_id', 'G-TEST12345');
    config()->set('services.google_analytics.consent_required', false);

    $this->get('/')
        ->assertOk()
        ->assertSee('G-TEST12345', false)
        ->assertDontSee('id="google-analytics-consent"', false)
        ->assertSee('analytics_storage:', false)
        ->assertSee('granted', false);
});
