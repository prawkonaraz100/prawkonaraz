<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionExplanationDraft;
use Illuminate\Support\Facades\File;

function pj360Candidate(array $overrides = []): array
{
    return array_replace_recursive([
        'gov_id' => '109',
        'source_queue' => 'tier_a',
        'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
        'categories' => ['A', 'B'],
        'question_type' => 'boolean',
        'question_media_kind' => 'video',
        'structure_scope' => 'PODSTAWOWY',
        'accepted_answer' => 'nie',
        'accepted_answer_label' => 'B',
        'external_site_question_id' => '3171',
        'external_url' => 'https://example.test/pytanie-3171',
        'draft_text' => 'Nie, bo w tej sytuacji przepisy albo okolicznosci na to nie pozwalaja.',
        'source_summary' => 'Trzeba zwrocic uwage na oznakowanie i przebieg sytuacji.',
        'quality_flags' => [],
        'publish_ready' => true,
        'tier_b_decision' => null,
        'tier_b_note' => null,
        'resolution_method' => null,
    ], $overrides);
}

function writePj360CandidateFixture(string $name, array $records): string
{
    $path = storage_path("app/testing/{$name}.json");

    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $path;
}

test('pj360 stage explanation drafts creates one staged entry per external id and maps all local categories', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $questionA = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => null,
        ]);

    $questionB = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => 'Istniejace lokalne wyjasnienie.',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-stage-clean', [
        pj360Candidate(),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])
        ->expectsOutputToContain('Zaladowano kandydatow: 1')
        ->expectsOutputToContain('Staged: 1')
        ->expectsOutputToContain('Konflikty stagingu: 0')
        ->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($draft->external_id)->toBe('109');
    expect($draft->status)->toBe(QuestionExplanationDraft::STATUS_STAGED);
    expect($draft->source_queue)->toBe('tier_a');
    expect($draft->local_question_count)->toBe(2);
    expect($draft->local_existing_explanation_count)->toBe(1);
    expect($draft->local_question_ids)->toEqualCanonicalizing([$questionA->id, $questionB->id]);
    expect($draft->local_category_codes)->toEqualCanonicalizing(['A', 'B']);
    expect($draft->missing_category_codes)->toBe([]);
    expect($draft->staging_flags)->toContain('existing_explanations_present');
});

test('pj360 stage explanation drafts marks missing categories as staging conflict', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-stage-conflict', [
        pj360Candidate(),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])
        ->expectsOutputToContain('Staged: 0')
        ->expectsOutputToContain('Konflikty stagingu: 1')
        ->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($draft->status)->toBe(QuestionExplanationDraft::STATUS_STAGING_CONFLICT);
    expect($draft->staging_issue)->toBe('missing_local_categories');
    expect($draft->missing_category_codes)->toBe(['B']);
    expect($draft->staging_flags)->toContain('missing_local_categories');
});

test('pj360 explanation draft summary reports counts and can export json', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-stage-summary', [
        pj360Candidate(),
        pj360Candidate([
            'gov_id' => '555',
            'prompt' => 'Czy mozesz wykonac ten manewr?',
            'categories' => ['A', 'B'],
            'external_site_question_id' => '9999',
            'external_url' => 'https://example.test/pytanie-9999',
        ]),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $summaryPath = storage_path('app/testing/pj360-stage-summary-output.json');

    $this->artisan('pj360:explanation-draft-summary', [
        '--json' => $summaryPath,
    ])
        ->expectsOutputToContain('Liczba wpisow: 2')
        ->expectsOutputToContain('Rekordy lokalne w zasiegu: 2')
        ->assertSuccessful();

    $summary = json_decode((string) File::get($summaryPath), true);

    expect($summary['total_entries'])->toBe(2);
    expect($summary['status_counts']['staged'])->toBe(1);
    expect($summary['status_counts']['staging_conflict'])->toBe(1);
    expect($summary['staging_issue_counts']['missing_local_questions'])->toBe(1);
});

test('pj360 stage explanation drafts ignores harmless unicode formatting differences in prompt comparison', function () {
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '8416',
            'prompt' => 'Czy masz prawo prowadzic pojazd gdy obecnosc alkoholu w wydychanym powietrzu w Twoim organizmie przekracza 0,1 mg w dm³?',
            'correct_answer' => 'b',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-stage-unicode-normalization', [
        pj360Candidate([
            'gov_id' => '8416',
            'categories' => ['B'],
            'prompt' => 'Czy masz prawo prowadzic pojazd gdy obecnosc alkoholu w wydychanym powietrzu w Twoim organizmie przekracza 0,1 mg w dm3?',
        ]),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($draft->status)->toBe(QuestionExplanationDraft::STATUS_STAGED);
    expect($draft->staging_flags)->not->toContain('local_prompt_mismatch');
});

test('pj360 apply explanation drafts previews changes without mutating questions or draft statuses', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $questionA = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => null,
        ]);

    $questionB = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => 'Istniejace wyjasnienie.',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-apply-preview', [
        pj360Candidate(),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $this->artisan('pj360:apply-explanation-drafts', [
        '--json' => storage_path('app/testing/pj360-apply-preview-summary.json'),
    ])
        ->expectsOutputToContain('Tryb: PREVIEW')
        ->expectsOutputToContain('Wpisy czesciowo stosowalne: 1')
        ->expectsOutputToContain('Wyjatki na koncu raportu: 1')
        ->expectsOutputToContain('External ID wyjatkow: 109')
        ->expectsOutputToContain('To byl tylko preview.')
        ->assertSuccessful();

    expect($questionA->fresh()->explanation)->toBeNull();
    expect($questionB->fresh()->explanation)->toBe('Istniejace wyjasnienie.');
    expect(QuestionExplanationDraft::query()->firstOrFail()->status)->toBe(QuestionExplanationDraft::STATUS_STAGED);

    $summary = json_decode((string) File::get(storage_path('app/testing/pj360-apply-preview-summary.json')), true);

    expect($summary['exception_count'])->toBe(1);
    expect($summary['exception_external_ids'])->toBe(['109']);
    expect($summary['exception_entries'])->toHaveCount(1);
    expect($summary['exception_entries'][0]['external_id'])->toBe('109');
    expect($summary['entry_reports'][array_key_last($summary['entry_reports'])]['external_id'])->toBe('109');
});

test('pj360 apply explanation drafts writes only missing explanations by default and marks partial status', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $questionA = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => null,
        ]);

    $questionB = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => 'Istniejace wyjasnienie.',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-apply-partial', [
        pj360Candidate(),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $this->artisan('pj360:apply-explanation-drafts', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('Wpisy czesciowo stosowalne: 1')
        ->expectsOutputToContain('Wyjatki na koncu raportu: 1')
        ->expectsOutputToContain('External ID wyjatkow: 109')
        ->expectsOutputToContain('Rekordy pytan zaktualizowane: 1')
        ->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($questionA->fresh()->explanation)->toBe($draft->draft_text);
    expect($questionB->fresh()->explanation)->toBe('Istniejace wyjasnienie.');
    expect($draft->status)->toBe(QuestionExplanationDraft::STATUS_APPLIED_WITH_SKIPS);
    expect($draft->applied_question_count)->toBe(1);
    expect($draft->skipped_existing_question_count)->toBe(1);
});

test('pj360 apply explanation drafts can overwrite existing explanations when explicitly allowed', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $questionA = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => null,
        ]);

    $questionB = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '109',
            'prompt' => 'Czy w przedstawionej sytuacji masz prawo kontynuowac jazde?',
            'correct_answer' => 'b',
            'explanation' => 'Istniejace wyjasnienie.',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-apply-overwrite', [
        pj360Candidate(),
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $this->artisan('pj360:apply-explanation-drafts', [
        '--write' => true,
        '--overwrite-existing' => true,
    ])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('Wpisy w pelni stosowalne: 1')
        ->expectsOutputToContain('Wyjatki na koncu raportu: 0')
        ->expectsOutputToContain('Rekordy pytan zaktualizowane: 2')
        ->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($questionA->fresh()->explanation)->toBe($draft->draft_text);
    expect($questionB->fresh()->explanation)->toBe($draft->draft_text);
    expect($draft->status)->toBe(QuestionExplanationDraft::STATUS_APPLIED);
    expect($draft->applied_question_count)->toBe(2);
    expect($draft->skipped_existing_question_count)->toBe(0);
});

test('pj360 apply exception resolutions updates targeted draft texts before final apply', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '11402',
            'prompt' => 'Czy za tym znakiem dozwolone jest wyprzedzanie rowerzysty?',
            'correct_answer' => 'a',
            'explanation' => null,
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '11402',
            'prompt' => 'Czy za tym znakiem dozwolone jest wyprzedzanie rowerzysty?',
            'correct_answer' => 'a',
            'explanation' => 'Istniejace lokalne wyjasnienie.',
        ]);

    $fixturePath = writePj360CandidateFixture('pj360-exception-resolution-base', [
        pj360Candidate([
            'gov_id' => '11402',
            'categories' => ['A', 'B'],
            'prompt' => 'Czy za tym znakiem dozwolone jest wyprzedzanie rowerzysty?',
            'accepted_answer' => 'tak',
            'accepted_answer_label' => 'A',
            'draft_text' => 'Stary draft do podmiany.',
            'source_summary' => 'Stare podsumowanie.',
        ]),
    ]);

    $resolutionPath = writePj360CandidateFixture('pj360-exception-resolution-manual', [
        [
            'external_id' => '11402',
            'resolved_draft_text' => 'Tak. Ten znak ostrzega, ale sam nie zabrania wyprzedzania rowerzysty.',
            'resolved_source_summary' => 'Znak ostrzega, ale nie wprowadza zakazu wyprzedzania.',
            'resolution_note' => 'Manual resolution for the exception.',
        ],
    ]);

    $this->artisan('pj360:stage-explanation-drafts', [
        'path' => $fixturePath,
        '--reset' => true,
    ])->assertSuccessful();

    $this->artisan('pj360:apply-exception-resolutions', [
        'path' => $resolutionPath,
    ])
        ->expectsOutputToContain('Ręczne rozstrzygniecia: 1')
        ->expectsOutputToContain('Zaktualizowane drafty: 1')
        ->expectsOutputToContain('External ID zaktualizowane: 11402')
        ->assertSuccessful();

    $draft = QuestionExplanationDraft::query()->firstOrFail();

    expect($draft->draft_text)->toBe('Tak. Ten znak ostrzega, ale sam nie zabrania wyprzedzania rowerzysty.');
    expect($draft->source_summary)->toBe('Znak ostrzega, ale nie wprowadza zakazu wyprzedzania.');
    expect($draft->quality_flags)->toContain('manual_exception_override');
    expect($draft->staging_flags)->toContain('manual_exception_resolved');
    expect($draft->source_payload['manual_exception_resolution']['resolution_note'])->toBe('Manual resolution for the exception.');
});
