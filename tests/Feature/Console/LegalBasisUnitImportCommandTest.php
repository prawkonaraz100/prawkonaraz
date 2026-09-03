<?php

use App\Models\LegalAct;
use App\Models\LegalUnit;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-legal-units'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-legal-units'));
});

test('legal basis unit import previews and writes hierarchical units idempotently', function () {
    $path = testingLegalUnitManifestPath([
        'act' => [
            'slug' => 'prawo-o-ruchu-drogowym',
            'title' => 'Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym',
            'short_title' => 'Prawo o ruchu drogowym',
            'publisher' => 'Dziennik Ustaw',
            'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
            'eli_url' => 'https://eli.gov.pl/eli/DU/1997/602/ogl',
            'isap_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
            'effective_from' => '1998-01-01',
            'last_checked_at' => '2026-06-16 10:00:00',
            'status' => LegalAct::STATUS_VERIFIED,
        ],
        'units' => [
            [
                'type' => 'section',
                'label' => 'art. 26 ust. 6',
                'canonical_path' => '26/6',
                'parent_canonical_path' => '26',
                'slug' => 'art-26-ust-6',
                'title' => 'Obowiazek zatrzymania przy przystanku tramwajowym',
                'official_excerpt' => 'Kierujacy pojazdem jest obowiazany zatrzymac pojazd...',
                'status' => LegalUnit::STATUS_VERIFIED,
            ],
            [
                'type' => 'article',
                'label' => 'art. 26',
                'canonical_path' => '26',
                'slug' => 'art-26',
                'title' => 'Obowiazki kierujacego wobec pieszych',
                'status' => LegalUnit::STATUS_VERIFIED,
            ],
        ],
    ]);

    $this->artisan('legal-basis:import-units', ['path' => $path])
        ->expectsOutputToContain('Tryb: PREVIEW')
        ->expectsOutputToContain('Akt do utworzenia: tak')
        ->expectsOutputToContain('Jednostki: input=2 would_create=2 would_update=0 unchanged=0 invalid=0')
        ->assertSuccessful();

    expect(LegalAct::query()->count())->toBe(0)
        ->and(LegalUnit::query()->count())->toBe(0);

    $this->artisan('legal-basis:import-units', ['path' => $path, '--write' => true])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('Akt utworzony: tak')
        ->expectsOutputToContain('Jednostki: input=2 created=2 updated=0 unchanged=0 invalid=0')
        ->assertSuccessful();

    $act = LegalAct::query()->where('slug', 'prawo-o-ruchu-drogowym')->firstOrFail();
    $article = LegalUnit::query()
        ->where('legal_act_id', $act->getKey())
        ->where('canonical_path', '26')
        ->firstOrFail();
    $section = LegalUnit::query()
        ->where('legal_act_id', $act->getKey())
        ->where('canonical_path', '26/6')
        ->firstOrFail();

    expect($article->parent_legal_unit_id)->toBeNull()
        ->and($section->parent_legal_unit_id)->toBe($article->getKey())
        ->and($section->source_url)->toBe('https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602')
        ->and($section->effective_from)->toBeNull()
        ->and($act->effective_from?->toDateString())->toBe('1998-01-01');

    $this->artisan('legal-basis:import-units', ['path' => $path, '--write' => true])
        ->expectsOutputToContain('Jednostki: input=2 created=0 updated=0 unchanged=2 invalid=0')
        ->assertSuccessful();

    expect(LegalUnit::query()->count())->toBe(2);
});

test('legal basis unit import rejects missing parent references', function () {
    $path = testingLegalUnitManifestPath([
        'act' => [
            'slug' => 'prawo-o-ruchu-drogowym',
            'title' => 'Prawo o ruchu drogowym',
            'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        ],
        'units' => [
            [
                'type' => 'point',
                'label' => 'art. 26 ust. 6 pkt 1',
                'canonical_path' => '26/6/1',
                'parent_canonical_path' => '26/6',
                'slug' => 'art-26-ust-6-pkt-1',
                'title' => 'Niekompletna jednostka testowa',
            ],
        ],
    ]);

    $this->artisan('legal-basis:import-units', ['path' => $path])
        ->expectsOutputToContain('Jednostki: input=1 would_create=0 would_update=0 unchanged=0 invalid=1')
        ->expectsOutputToContain('[invalid] 26/6/1 missing_parent')
        ->assertFailed();
});

test('legal basis unit import does not downgrade verified records to needs review', function () {
    $act = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym',
        'title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $unit = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'article',
        'label' => 'art. 26',
        'slug' => 'art-26',
        'title' => 'Stary tytul',
        'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $path = testingLegalUnitManifestPath([
        'act' => [
            'slug' => 'prawo-o-ruchu-drogowym',
            'title' => 'Ustawa z dnia 20 czerwca 1997 r. - Prawo o ruchu drogowym',
            'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
            'status' => LegalAct::STATUS_NEEDS_REVIEW,
        ],
        'units' => [
            [
                'type' => 'article',
                'label' => 'art. 26',
                'canonical_path' => '26',
                'slug' => 'art-26',
                'title' => 'Nowy tytul z manifestu',
                'status' => LegalUnit::STATUS_NEEDS_REVIEW,
            ],
        ],
    ]);

    $this->artisan('legal-basis:import-units', ['path' => $path, '--write' => true])
        ->assertSuccessful();

    expect($act->fresh()->status)->toBe(LegalAct::STATUS_VERIFIED)
        ->and($unit->fresh()->status)->toBe(LegalUnit::STATUS_VERIFIED)
        ->and($unit->fresh()->canonical_path)->toBe('26')
        ->and($unit->fresh()->title)->toBe('Nowy tytul z manifestu');
});

test('legal basis unit import preserves existing editorial title when generated title is only a label', function () {
    $act = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym',
        'title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $unit = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'article',
        'label' => 'art. 26',
        'slug' => 'art-26',
        'title' => 'Obowiazki kierujacego wobec pieszych',
        'summary' => 'Opis redakcyjny.',
        'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $path = testingLegalUnitManifestPath([
        'act' => [
            'slug' => 'prawo-o-ruchu-drogowym',
            'title' => 'Prawo o ruchu drogowym',
            'source_url' => 'https://isap.sejm.gov.pl/isap.nsf/DocDetails.xsp?id=WDU19970980602',
        ],
        'units' => [
            [
                'type' => 'article',
                'label' => 'art. 26',
                'canonical_path' => '26',
                'slug' => 'art-26',
                'title' => 'art. 26',
                'summary' => null,
            ],
        ],
    ]);

    $this->artisan('legal-basis:import-units', ['path' => $path, '--write' => true])
        ->assertSuccessful();

    expect($unit->fresh()->title)->toBe('Obowiazki kierujacego wobec pieszych')
        ->and($unit->fresh()->summary)->toBe('Opis redakcyjny.')
        ->and($unit->fresh()->canonical_path)->toBe('26');
});

function testingLegalUnitManifestPath(array $payload): string
{
    $directory = storage_path('app/testing-legal-units');

    File::ensureDirectoryExists($directory);

    $path = $directory.'/manifest.json';
    File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    return $path;
}
