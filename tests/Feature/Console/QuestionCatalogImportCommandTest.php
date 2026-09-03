<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\QuestionTopic;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-imports'));
});

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/testing-imports'));
});

test('question catalog import command imports categories, questions and media and writes a report', function () {
    config([
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-imports');
    File::ensureDirectoryExists($directory);

    $payload = [
        'batch_id' => 'batch-success',
        'categories' => [
            [
                'code' => 'B',
                'name' => 'Kategoria B',
                'description' => 'Samochody osobowe',
                'questions' => [
                    [
                        'external_id' => 'B-001',
                        'prompt' => 'Czy przed ruszeniem nalezy zapinac pasy?',
                        'explanation' => 'Tak, pasy nalezy zapinac przed rozpoczeciem jazdy.',
                        'option_a' => 'Tak',
                        'option_b' => 'Nie',
                        'correct_answer' => 'a',
                        'question_type' => 'boolean',
                        'difficulty' => 1,
                        'points' => 3,
                        'media' => [
                            [
                                'kind' => 'image',
                                'path' => 'questions/b/b-001.webp',
                                'mime_type' => 'image/webp',
                                'width' => 1280,
                                'height' => 720,
                                'variant' => 'full',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $catalogPath = $directory.'/catalog.json';
    $reportPath = $directory.'/report.json';

    File::put($catalogPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->artisan('catalog:import-json', [
        'path' => $catalogPath,
        '--skip-sitemap' => true,
        '--report' => $reportPath,
    ])->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(1);
    expect(Question::query()->count())->toBe(1);
    expect(QuestionMedia::query()->count())->toBe(1);

    $question = Question::query()->first();

    expect($question)->not->toBeNull();
    expect($question->licenseCategory->code)->toBe('B');
    expect($question->questionTopic)->toBeInstanceOf(QuestionTopic::class);
    expect($question->questionTopic?->key)->toBe('safety_equipment_and_restraints');
    expect($question->media)->toHaveCount(1);
    expect($question->media->first()->disk)->toBe('r2');
    expect($question->media->first()->path)->toBe('questions/b/b-001.webp');

    expect(File::exists($reportPath))->toBeTrue();

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['batch_id'])->toBe('batch-success');
    expect($report['dry_run'])->toBeFalse();
    expect($report['questions_created'])->toBe(1);
    expect($report['media_created'])->toBe(1);
    expect($report['errors_count'])->toBe(0);
});

test('question catalog import command supports dry run without persisting changes', function () {
    config([
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-imports');
    File::ensureDirectoryExists($directory);

    $payload = [
        'batch_id' => 'batch-dry-run',
        'categories' => [
            [
                'code' => 'B',
                'name' => 'Kategoria B',
                'questions' => [
                    [
                        'external_id' => 'B-010',
                        'prompt' => 'Czy nalezy zachowac ostroznosc przed przejsciem?',
                        'option_a' => 'Tak',
                        'option_b' => 'Nie',
                        'correct_answer' => 'a',
                        'question_type' => 'boolean',
                        'media' => [
                            [
                                'kind' => 'image',
                                'path' => 'questions/b/b-010.webp',
                                'mime_type' => 'image/webp',
                                'variant' => 'full',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $catalogPath = $directory.'/catalog-dry-run.json';
    $reportPath = $directory.'/dry-run-report.json';

    File::put($catalogPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->artisan('catalog:import-json', [
        'path' => $catalogPath,
        '--dry-run' => true,
        '--report' => $reportPath,
    ])->assertSuccessful();

    expect(LicenseCategory::query()->count())->toBe(0);
    expect(Question::query()->count())->toBe(0);
    expect(QuestionMedia::query()->count())->toBe(0);

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['batch_id'])->toBe('batch-dry-run');
    expect($report['dry_run'])->toBeTrue();
    expect($report['categories_created'])->toBe(1);
    expect($report['questions_created'])->toBe(1);
    expect($report['media_created'])->toBe(1);
    expect($report['errors_count'])->toBe(0);
});

test('question catalog import command reports invalid records and returns failure while keeping valid ones', function () {
    config([
        'media.default_disk' => 'r2',
    ]);

    $directory = storage_path('app/testing-imports');
    File::ensureDirectoryExists($directory);

    $payload = [
        'batch_id' => 'batch-invalid-records',
        'categories' => [
            [
                'code' => 'B',
                'name' => 'Kategoria B',
                'questions' => [
                    [
                        'external_id' => 'B-020',
                        'prompt' => 'Czy nalezy zatrzymac sie przed sygnalem STOP?',
                        'option_a' => 'Tak',
                        'option_b' => 'Nie',
                        'correct_answer' => 'a',
                        'question_type' => 'boolean',
                    ],
                    [
                        'external_id' => 'B-021',
                        'prompt' => 'Czy to pytanie jest niepoprawne?',
                        'option_a' => 'Tak',
                        'option_b' => 'Nie',
                        'correct_answer' => 'c',
                        'question_type' => 'boolean',
                    ],
                ],
            ],
        ],
    ];

    $catalogPath = $directory.'/catalog-invalid.json';
    $reportPath = $directory.'/invalid-report.json';

    File::put($catalogPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->artisan('catalog:import-json', [
        'path' => $catalogPath,
        '--report' => $reportPath,
    ])->assertFailed();

    expect(LicenseCategory::query()->count())->toBe(1);
    expect(Question::query()->count())->toBe(1);
    expect(Question::query()->first()?->external_id)->toBe('B-020');

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['batch_id'])->toBe('batch-invalid-records');
    expect($report['questions_total'])->toBe(2);
    expect($report['questions_created'])->toBe(1);
    expect($report['errors_count'])->toBe(1);
    expect($report['errors'][0]['path'])->toContain('questions.1');
});
