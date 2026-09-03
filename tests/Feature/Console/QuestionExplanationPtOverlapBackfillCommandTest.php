<?php

use App\Models\LicenseCategory;
use App\Models\Question;

test('content backfill pt overlap explanations previews candidates without mutating PT rows', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '13562',
            'prompt' => 'Czy predkosc pojazdu powinna byc dostosowana do widocznosci drogi?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Predkosc trzeba zawsze dostosowac do widocznosci i warunkow.',
        ]);

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '13562',
            'prompt' => 'Czy predkosc pojazdu powinna byc dostosowana do widocznosci drogi?',
            'correct_answer' => 'a',
            'explanation' => null,
        ]);

    $this->artisan('content:backfill-pt-overlap-explanations')
        ->expectsOutputToContain('Tryb: PREVIEW')
        ->expectsOutputToContain('Kandydaci external_id: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 0')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBeNull();
});

test('content backfill pt overlap explanations copies shared explanation into PT rows on write', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    $explanation = 'Tak. Widocznosc drogi wplywa bezposrednio na bezpieczna predkosc jazdy.';

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '13623',
            'prompt' => 'Czy zblizajac sie do przejazdu kolejowego powinienes zwracac uwage na sygnaly?',
            'correct_answer' => 'a',
            'explanation' => $explanation,
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '13623',
            'prompt' => 'Czy zblizajac sie do przejazdu kolejowego powinienes zwracac uwage na sygnaly?',
            'correct_answer' => 'a',
            'explanation' => $explanation,
        ]);

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '13623',
            'prompt' => 'Czy zblizajac sie do przejazdu kolejowego powinienes zwracac uwage na sygnaly?',
            'correct_answer' => 'a',
            'explanation' => '',
        ]);

    $this->artisan('content:backfill-pt-overlap-explanations', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Tryb: WRITE')
        ->expectsOutputToContain('Kandydaci external_id: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 1')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBe($explanation);
});

test('content backfill pt overlap explanations skips PT rows when donor explanations are inconsistent', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryPT = LicenseCategory::factory()->withCode('PT')->create();

    Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '13784',
            'prompt' => 'Czy na drodze tak oznakowanej osoba poruszajaca sie ma pierwszenstwo?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Pierwsza wersja wyjasnienia.',
        ]);

    Question::factory()
        ->booleanType()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '13784',
            'prompt' => 'Czy na drodze tak oznakowanej osoba poruszajaca sie ma pierwszenstwo?',
            'correct_answer' => 'a',
            'explanation' => 'Tak. Druga wersja wyjasnienia.',
        ]);

    $ptQuestion = Question::factory()
        ->booleanType()
        ->for($categoryPT, 'licenseCategory')
        ->create([
            'external_id' => '13784',
            'prompt' => 'Czy na drodze tak oznakowanej osoba poruszajaca sie ma pierwszenstwo?',
            'correct_answer' => 'a',
            'explanation' => null,
        ]);

    $this->artisan('content:backfill-pt-overlap-explanations', [
        '--write' => true,
    ])
        ->expectsOutputToContain('Pominiete przez niespojnego dawce: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze PT: 0')
        ->assertSuccessful();

    expect($ptQuestion->fresh()->explanation)->toBeNull();
});
