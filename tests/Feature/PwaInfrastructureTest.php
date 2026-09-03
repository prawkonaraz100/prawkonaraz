<?php

use Illuminate\Support\Facades\Config;

test('pwa manifest exposes installability basics', function () {
    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest)
        ->toBeArray()
        ->and($manifest['name'])->toBe('PrawkoNaRaz')
        ->and($manifest['short_name'])->toBe('PrawkoNaRaz')
        ->and($manifest['id'])->toBe('/nauka')
        ->and($manifest['start_url'])->toStartWith('/nauka')
        ->and($manifest['scope'])->toBe('/')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['icons'])->toHaveCount(4);

    $maskable512 = collect($manifest['icons'])->firstWhere('src', '/pwa/maskable-512.png');

    expect($maskable512)
        ->not->toBeNull()
        ->and($maskable512['sizes'])->toBe('512x512')
        ->and($maskable512['purpose'])->toBe('maskable');
});

test('service worker keeps private learning and api traffic out of runtime cache', function () {
    $serviceWorker = (string) file_get_contents(public_path('service-worker.js'));

    expect($serviceWorker)
        ->toContain("const PRIVATE_PATH_PREFIXES")
        ->toContain("'/api/'")
        ->toContain("'/auth/'")
        ->toContain("'/nauka'")
        ->toContain("'/study-sessions'")
        ->toContain('isPrivateRequest(url)')
        ->toContain("request.mode === 'navigate'");
});

test('assetlinks endpoint returns empty statements until twa identity is configured', function () {
    Config::set('pwa.assetlinks.statements', []);

    $response = $this->get('/.well-known/assetlinks.json');

    $response
        ->assertOk()
        ->assertExactJson([]);

    expect($response->headers->get('cache-control'))
        ->toContain('public')
        ->toContain('max-age=300')
        ->toContain('must-revalidate')
        ->not->toContain('immutable')
        ->not->toContain('private');
});

test('assetlinks endpoint returns configured digital asset links statements', function () {
    Config::set('pwa.assetlinks.statements', [[
        'relation' => ['delegate_permission/common.handle_all_urls'],
        'target' => [
            'namespace' => 'android_app',
            'package_name' => 'pl.prawkonaraz.app',
            'sha256_cert_fingerprints' => ['AA:BB:CC'],
        ],
    ]]);

    $response = $this->get('/.well-known/assetlinks.json');

    $response
        ->assertOk()
        ->assertJsonPath('0.target.package_name', 'pl.prawkonaraz.app')
        ->assertJsonPath('0.target.sha256_cert_fingerprints.0', 'AA:BB:CC');

    expect($response->headers->get('cache-control'))
        ->toContain('public')
        ->toContain('max-age=300')
        ->toContain('must-revalidate')
        ->not->toContain('immutable')
        ->not->toContain('private');
});
