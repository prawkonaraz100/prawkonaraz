<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Support\Facades\File;

function writeManualExplanationFixture(string $name, array $records): string
{
    $path = storage_path("app/testing/{$name}.json");

    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $path;
}

test('content apply manual explanation resolutions updates missing explanations by external id', function () {
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryB1 = LicenseCategory::factory()->withCode('B1')->create();

    $questionB = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => '1864',
            'prompt' => 'Jakiej kategorii prawo jazdy jest wymagane?',
            'explanation' => null,
        ]);

    $questionB1 = Question::factory()
        ->for($categoryB1, 'licenseCategory')
        ->create([
            'external_id' => '1864',
            'prompt' => 'Jakiej kategorii prawo jazdy jest wymagane?',
            'explanation' => '',
        ]);

    $fixturePath = writeManualExplanationFixture('manual-explanations-1864', [
        [
            'external_id' => '1864',
            'resolved_explanation' => 'Poprawna jest odpowiedz A, bo do kierowania czterokolowcem innym niz lekki wymagane jest prawo jazdy kategorii B1.',
        ],
    ]);

    $this->artisan('content:apply-manual-explanation-resolutions', [
        'path' => $fixturePath,
    ])
        ->expectsOutputToContain('Zaktualizowane external_id: 1')
        ->expectsOutputToContain('Zaktualizowane wiersze pytan: 2')
        ->assertSuccessful();

    expect($questionB->fresh()->explanation)->toContain('kategorii B1');
    expect($questionB1->fresh()->explanation)->toContain('kategorii B1');
});

test('content apply manual explanation resolutions does not overwrite existing explanations without flag', function () {
    $categoryA = LicenseCategory::factory()->withCode('A')->create();

    $question = Question::factory()
        ->booleanType()
        ->for($categoryA, 'licenseCategory')
        ->create([
            'external_id' => '11224',
            'prompt' => 'Czy w tej sytuacji mozesz wyprzedzic rowerzyste?',
            'explanation' => 'Istniejace lokalne wyjasnienie.',
        ]);

    $fixturePath = writeManualExplanationFixture('manual-explanations-11224', [
        [
            'external_id' => '11224',
            'resolved_explanation' => 'Nowe wyjasnienie.',
        ],
    ]);

    $this->artisan('content:apply-manual-explanation-resolutions', [
        'path' => $fixturePath,
    ])->assertSuccessful();

    expect($question->fresh()->explanation)->toBe('Istniejace lokalne wyjasnienie.');
});
