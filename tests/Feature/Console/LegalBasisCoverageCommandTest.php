<?php

use App\Models\LegalAct;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\Support\LegalBasisCoverageService;

test('legal basis coverage command reports canonical public question progress', function () {
    $categoryA = LicenseCategory::factory()->categoryA()->create([
        'name' => 'Kategoria A',
        'sort_order' => 1,
    ]);
    $categoryB = LicenseCategory::factory()->categoryB()->create([
        'name' => 'Kategoria B',
        'sort_order' => 2,
    ]);
    $hiddenCategory = LicenseCategory::factory()->withCode('X')->create([
        'name' => 'Kategoria X',
        'sort_order' => 99,
    ]);

    $question99 = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create(['external_id' => '99']);
    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create(['external_id' => 'pj360:99']);
    $question100 = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create(['external_id' => '100']);
    $question101 = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create(['external_id' => '101']);
    $hiddenQuestion = Question::factory()
        ->for($hiddenCategory, 'licenseCategory')
        ->create(['external_id' => '999']);

    $legalAct = LegalAct::query()->create([
        'slug' => 'prawo-o-ruchu-drogowym',
        'title' => 'Prawo o ruchu drogowym',
        'short_title' => 'Prawo o ruchu drogowym',
        'source_url' => 'https://isap.sejm.gov.pl/',
        'status' => LegalAct::STATUS_VERIFIED,
    ]);
    $legalTopic = LegalTopic::query()->create([
        'slug' => 'piesi-i-przejscia',
        'title' => 'Piesi i przejscia',
        'status' => LegalTopic::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);
    $contentPage = LegalContentPage::query()->create([
        'legal_topic_id' => $legalTopic->getKey(),
        'slug' => 'piesi-i-przejscia',
        'title' => 'Piesi i przejscia',
        'status' => LegalContentPage::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
    ]);
    $articleUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'article',
        'label' => 'art. 20',
        'slug' => 'art-20',
        'title' => 'Dopuszczalne predkosci',
        'source_url' => 'https://isap.sejm.gov.pl/',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);
    $preciseUnit = LegalUnit::query()->create([
        'legal_act_id' => $legalAct->getKey(),
        'type' => 'section',
        'label' => 'art. 26 ust. 6',
        'slug' => 'art-26-ust-6',
        'title' => 'Przystanek tramwajowy bez wysepki',
        'source_url' => 'https://isap.sejm.gov.pl/',
        'status' => LegalUnit::STATUS_VERIFIED,
    ]);

    QuestionLegalReference::query()->create([
        'question_id' => $question99->getKey(),
        'legal_unit_id' => $articleUnit->getKey(),
        'legal_topic_id' => $legalTopic->getKey(),
        'legal_content_page_id' => $contentPage->getKey(),
        'public_note' => 'Publiczna notatka prawna.',
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now(),
    ]);
    QuestionLegalReference::query()->create([
        'question_id' => $question101->getKey(),
        'legal_unit_id' => $preciseUnit->getKey(),
        'legal_topic_id' => $legalTopic->getKey(),
        'legal_content_page_id' => null,
        'public_note' => 'Druga publiczna notatka prawna.',
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now(),
    ]);
    QuestionLegalReference::query()->create([
        'question_id' => $hiddenQuestion->getKey(),
        'legal_unit_id' => $preciseUnit->getKey(),
        'legal_topic_id' => $legalTopic->getKey(),
        'legal_content_page_id' => null,
        'public_note' => 'Ta relacja nie powinna liczyc sie do publicznego raportu.',
        'status' => QuestionLegalReference::STATUS_VERIFIED,
        'verified_at' => now(),
    ]);

    $summary = app(LegalBasisCoverageService::class)->summary(sampleLimit: 5);

    expect($summary['question_rows']['total'])->toBe(4)
        ->and($summary['canonical_questions']['total'])->toBe(3)
        ->and($summary['canonical_questions']['with_verified_reference'])->toBe(2)
        ->and($summary['canonical_questions']['missing_verified_reference'])->toBe(1)
        ->and($summary['canonical_questions']['with_verified_reference_with_article'])->toBe(1)
        ->and($summary['canonical_questions']['with_verified_reference_without_article'])->toBe(1)
        ->and($summary['canonical_questions']['with_article_level_verified_reference'])->toBe(1)
        ->and($summary['question_legal_references']['status_counts'])->toBe([
            QuestionLegalReference::STATUS_VERIFIED => 2,
        ])
        ->and($summary['samples']['missing_verified_reference'][0]['external_id'])->toBe('100');

    $this->artisan('legal-basis:coverage', ['--sample-limit' => 2])
        ->expectsOutputToContain('Publiczne rekordy pytan: 4')
        ->expectsOutputToContain('Publiczne pytania kanoniczne: 3')
        ->expectsOutputToContain('Zweryfikowana podstawa prawna: 2/3 (66.7%)')
        ->expectsOutputToContain('Bez opublikowanego artykulu: 1')
        ->expectsOutputToContain('Wskazanie na poziomie samego artykulu: 1')
        ->assertSuccessful();

    $this->artisan('legal-basis:coverage', ['--json' => true, '--sample-limit' => 1])
        ->expectsOutputToContain('"with_verified_reference": 2')
        ->assertSuccessful();
});
