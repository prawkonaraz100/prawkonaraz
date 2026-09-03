<?php

use App\Models\LegalUnit;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-legal-manifest-build'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-legal-manifest-build'));
});

test('legal basis manifest build command converts eli html into importable units', function () {
    $directory = storage_path('app/testing-legal-manifest-build');
    File::ensureDirectoryExists($directory);

    $htmlPath = $directory.'/eli-text.html';
    $manifestPath = $directory.'/manifest.json';

    File::put($htmlPath, <<<'HTML'
<!DOCTYPE html>
<html lang="pl">
<body>
<div class="unit unit_arti pro-text false" id="arti_26" data-id="arti_26">
    <h3><b>Art.&nbsp;26.</b></h3>
    <div class="unit-inner">
        <div class="unit unit_pass pro-text false" id="arti_26-pass_6" data-id="pass_6">
            <h3>6.</h3>
            <div class="unit-inner">
                <div data-template="xText" class="pro-text">Kierujący pojazdem jest obowiązany zachować szczególną ostrożność przy przejeżdżaniu obok oznaczonego przystanku tramwajowego.</div>
            </div>
        </div>
        <div class="unit unit_pass pro-text false" id="arti_26-pass_7" data-id="pass_7">
            <h3>7.</h3>
            <div class="unit-inner">
                <div data-template="xText" class="pro-text">Zabrania się:</div>
                <div class="unit unit_pint pro-text false" id="arti_26-pass_7-pint_1" data-id="pint_1">
                    <h3>1)</h3>
                    <div class="unit-inner">
                        <div data-template="xText" class="pro-text">omijania pojazdu, który zatrzymał się przed przejściem,</div>
                        <div class="unit unit_lett pro-text false" id="arti_26-pass_7-pint_1-lett_a" data-id="lett_a">
                            <h3>a)</h3>
                            <div class="unit-inner">
                                <div data-template="xText" class="pro-text">jeżeli ogranicza to bezpieczeństwo pieszego.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="unit unit_arti pro-rplc-text false" id="foreign-arti_96a" data-id="arti_96a">
    <h3><b>Art.&nbsp;96a.</b></h3>
    <div class="unit-inner"><div data-template="xText" class="pro-rplc-text">Tekst zmiany innego aktu.</div></div>
</div>
</body>
</html>
HTML);

    $this->artisan('legal-basis:build-units-manifest', [
        'source' => $htmlPath,
        '--output' => $manifestPath,
        '--act-status' => 'verified',
        '--unit-status' => 'verified',
        '--last-checked-at' => '2026-06-16 10:00:00',
    ])
        ->expectsOutputToContain('Jednostki w manifeście: 5')
        ->expectsOutputToContain('Pominiete fragmenty zmian innych aktow: 1')
        ->assertSuccessful();

    $manifest = json_decode((string) File::get($manifestPath), true);

    expect($manifest['act']['slug'])->toBe('prawo-o-ruchu-drogowym')
        ->and($manifest['units'])->toHaveCount(5)
        ->and($manifest['units'][1]['label'])->toBe('art. 26 ust. 6')
        ->and($manifest['units'][1]['canonical_path'])->toBe('26/6')
        ->and($manifest['units'][4]['label'])->toBe('art. 26 ust. 7 pkt 1 lit. a')
        ->and($manifest['units'][4]['canonical_path'])->toBe('26/7/1/a')
        ->and($manifest['units'][4]['parent_canonical_path'])->toBe('26/7/1');

    $this->artisan('legal-basis:import-units', [
        'path' => $manifestPath,
        '--write' => true,
    ])->assertSuccessful();

    $letter = LegalUnit::query()
        ->where('canonical_path', '26/7/1/a')
        ->firstOrFail();

    expect(LegalUnit::query()->count())->toBe(5)
        ->and($letter->parent?->canonical_path)->toBe('26/7/1')
        ->and($letter->official_excerpt)->toBe('jeżeli ogranicza to bezpieczeństwo pieszego.');
});
