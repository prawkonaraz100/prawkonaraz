<?php

test('alternate production hosts permanently redirect to the canonical URL', function (string $host) {
    $this->app['env'] = 'production';
    config()->set('app.url', 'https://prawkonaraz.pl');

    $this->get("https://{$host}/up?source=legacy")
        ->assertStatus(301)
        ->assertRedirect('https://prawkonaraz.pl/up?source=legacy');
})->with([
    'old apex domain' => 'prawkoapp.pl',
    'old www domain' => 'www.prawkoapp.pl',
    'temporary host' => 'wild-bison5536.byst.re',
    'canonical www alias' => 'www.prawkonaraz.pl',
]);

test('the canonical production host is served without a redirect', function () {
    $this->app['env'] = 'production';
    config()->set('app.url', 'https://prawkonaraz.pl');

    $this->get('https://prawkonaraz.pl/up')
        ->assertOk();
});

test('local development hosts are not redirected', function () {
    $this->app['env'] = 'local';
    config()->set('app.url', 'http://localhost:8000');

    $this->get('http://custom-local-host.test/up')
        ->assertOk();
});
