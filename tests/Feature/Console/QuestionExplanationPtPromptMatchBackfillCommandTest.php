<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;

test('content backfill pt prompt match explanations previews candidates for exact prompt answer and media matches', function () {
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    $donor = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '2567',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zachowania szczególnej ostrożności?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Zbliżając się do przejścia dla pieszych, musisz zachować szczególną ostrożność.',
        ]);

    QuestionMedia::factory()
        ->for($donor, 'question')
        ->create([
            'kind' => 'video',
            'sort_order' => 1,
            'path' => 'media/questions/b/2567/video/main.mp4',
        ]);

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '9002567',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zachowania szczególnej ostrożności?',
            'correct_answer' => 'a',
            'explanation' => null,
        ]);

    QuestionMedia::factory()
        ->for($ptQuestion, 'question')
        ->create([
            'kind' => 'video',
            'sort_order' => 1,
            'path' => 'media/questions/pt/9002567/video/main.mp4',
        ]);

    $this->artisan('content:backfill-pt-prompt-match-explanations')
        ->expectsOutputToContain('Tryb: PREVIEW')
        ->expectsOutputToContain('Kandydaci external_id: 1')
        ->expectsOutputToContain('Pominiete przez brak zgodnego dawcy: 0')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 0')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBeNull();
});

test('content backfill pt prompt match explanations copies shared explanation into PT rows on write', function () {
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    $explanation = 'Tak. Zbliżając się do przejścia dla pieszych, musisz zachować szczególną ostrożność.';

    $donor = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '2567',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zachowania szczególnej ostrożności?',
            'correct_answer' => 'a',
            'explanation' => $explanation,
        ]);

    QuestionMedia::factory()
        ->for($donor, 'question')
        ->create([
            'kind' => 'video',
            'sort_order' => 1,
            'path' => 'media/questions/b/2567/video/main.mp4',
        ]);

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '9002568',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zachowania szczególnej ostrożności?',
            'correct_answer' => 'a',
            'explanation' => '',
        ]);

    QuestionMedia::factory()
        ->for($ptQuestion, 'question')
        ->create([
            'kind' => 'video',
            'sort_order' => 1,
            'path' => 'media/questions/pt/9002568/video/main.mp4',
        ]);

    $this->artisan('content:backfill-pt-prompt-match-explanations', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('Kandydaci external_id: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 1')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBe($explanation);
});

test('content backfill pt prompt match explanations skips PT rows when same prompt donor explanations are inconsistent', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    $donorA = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '3494',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zastosować zasadę ograniczonego zaufania?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Pierwsza wersja wyjaśnienia.',
        ]);

    $donorB = Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '3495',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zastosować zasadę ograniczonego zaufania?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Druga wersja wyjaśnienia.',
        ]);

    foreach ([$donorA, $donorB] as $donor) {
        QuestionMedia::factory()
            ->for($donor, 'question')
            ->create([
                'kind' => 'video',
                'sort_order' => 1,
                'path' => 'media/questions/shared/video/main.mp4',
            ]);
    }

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '9003494',
            'prompt' => 'Czy w tej sytuacji masz obowiązek zastosować zasadę ograniczonego zaufania?',
            'correct_answer' => 'a',
            'explanation' => null,
        ]);

    QuestionMedia::factory()
        ->for($ptQuestion, 'question')
        ->create([
            'kind' => 'video',
            'sort_order' => 1,
            'path' => 'media/questions/pt/9003494/video/main.mp4',
        ]);

    $this->artisan('content:backfill-pt-prompt-match-explanations', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Pominiete przez niespojnego dawce: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 0')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBeNull();
});
