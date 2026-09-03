<?php

use App\Models\LegalAct;
use App\Models\LegalSourceCheck;
use App\Models\LegalUnit;
use Illuminate\Support\Facades\File;

test('legal basis unit audit previews and promotes only clean imported units', function () {
    $sourcePath = storage_path('framework/testing/legal-unit-audit.html');
    $sourceUrl = 'https://example.test/eli/DU/2026/1/text.html';

    File::ensureDirectoryExists(dirname($sourcePath));
    File::put($sourcePath, <<<'HTML'
        <html>
            <body>
                <div class="unit unit_arti" id="arti_26">
                    <h3>Art. 26.</h3>
                    <div class="unit-inner"></div>
                    <div class="unit unit_pass" id="arti_26-pass_1">
                        <h3>1.</h3>
                        <div class="unit-inner">
                            <div data-template="xText">Kierujący pojazdem zachowuje szczególną ostrożność.</div>
                        </div>
                    </div>
                </div>
            </body>
        </html>
        HTML);

    $act = LegalAct::query()->create([
        'slug' => 'auditowany-akt',
        'title' => 'Auditowany akt prawny',
        'short_title' => 'Auditowany akt',
        'source_url' => $sourceUrl,
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $article = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'type' => 'article',
        'label' => 'art. 26',
        'canonical_path' => '26',
        'slug' => 'art-26',
        'title' => 'art. 26',
        'source_url' => $sourceUrl.'#arti_26',
        'status' => LegalUnit::STATUS_NEEDS_REVIEW,
    ]);
    $section = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'parent_legal_unit_id' => $article->getKey(),
        'type' => 'section',
        'label' => 'art. 26 ust. 1',
        'canonical_path' => '26/1',
        'slug' => 'art-26-ust-1',
        'title' => 'art. 26 ust. 1',
        'official_excerpt' => 'Kierujący pojazdem zachowuje szczególną ostrożność.',
        'source_url' => $sourceUrl.'#arti_26-pass_1',
        'status' => LegalUnit::STATUS_NEEDS_REVIEW,
    ]);
    $broken = LegalUnit::query()->create([
        'legal_act_id' => $act->getKey(),
        'parent_legal_unit_id' => $article->getKey(),
        'type' => 'section',
        'label' => 'art. 26 ust. 2',
        'canonical_path' => '26/2',
        'slug' => 'art-26-ust-2',
        'title' => 'art. 26 ust. 2',
        'official_excerpt' => 'Nie istnieje w testowym źródle.',
        'source_url' => $sourceUrl.'#arti_26-pass_2',
        'status' => LegalUnit::STATUS_NEEDS_REVIEW,
    ]);

    $this->artisan('legal-basis:audit-units', [
        'act_slug' => 'auditowany-akt',
        '--source' => $sourcePath,
        '--source-url' => $sourceUrl,
        '--sample-limit' => 5,
    ])
        ->expectsOutputToContain('Przechodza kontrole: 2')
        ->expectsOutputToContain('Nie przechodza kontroli: 1')
        ->expectsOutputToContain('missing_in_source: 1')
        ->assertSuccessful();

    expect($article->fresh()?->status)->toBe(LegalUnit::STATUS_NEEDS_REVIEW)
        ->and($section->fresh()?->status)->toBe(LegalUnit::STATUS_NEEDS_REVIEW)
        ->and(LegalSourceCheck::query()->count())->toBe(0);

    $this->artisan('legal-basis:audit-units', [
        'act_slug' => 'auditowany-akt',
        '--source' => $sourcePath,
        '--source-url' => $sourceUrl,
        '--write' => true,
        '--sample-limit' => 5,
    ])
        ->expectsOutputToContain('Promowane do verified: 2')
        ->expectsOutputToContain('Nie przechodza kontroli: 1')
        ->assertSuccessful();

    expect($article->fresh()?->status)->toBe(LegalUnit::STATUS_VERIFIED)
        ->and($section->fresh()?->status)->toBe(LegalUnit::STATUS_VERIFIED)
        ->and($broken->fresh()?->status)->toBe(LegalUnit::STATUS_NEEDS_REVIEW)
        ->and(LegalSourceCheck::query()->count())->toBe(2);
});
