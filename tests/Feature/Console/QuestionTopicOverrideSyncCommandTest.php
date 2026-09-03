<?php

use App\Models\LicenseCategory;
use App\Models\QuestionTopicOverride;
use App\Support\QuestionTopicAssigner;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-overrides.json'));
});

afterEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-overrides.json'));
});

test('question topic override sync command validates and stores overrides', function () {
    LicenseCategory::factory()->withCode('B')->create();
    app(QuestionTopicAssigner::class)->seedTopics();

    $payloadPath = storage_path('app/testing/question-topic-overrides.json');

    File::ensureDirectoryExists(dirname($payloadPath));
    File::put($payloadPath, json_encode([
        [
            'license_category_code' => 'B',
            'source' => 'gov.pl-mi',
            'external_id' => 'EXT-001',
            'question_topic_key' => 'road_markings',
            'reason' => 'Zgodnosc z PJ360 dla B.',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:sync-question-topic-overrides', [
        'path' => $payloadPath,
    ])
        ->expectsOutputToContain('Rekordy: 1, created: 1, updated: 0')
        ->assertSuccessful();

    $override = QuestionTopicOverride::query()->firstOrFail();

    expect($override->license_category_code)->toBe('B');
    expect($override->source)->toBe('gov.pl-mi');
    expect($override->external_id)->toBe('EXT-001');
    expect($override->question_topic_key)->toBe('road_markings');
    expect($override->reason)->toBe('Zgodnosc z PJ360 dla B.');
    expect($override->is_active)->toBeTrue();
});

test('question topic override sync command stores metadata payloads from exported candidates', function () {
    LicenseCategory::factory()->withCode('B')->create();
    app(QuestionTopicAssigner::class)->seedTopics();

    $payloadPath = storage_path('app/testing/question-topic-overrides.json');

    File::ensureDirectoryExists(dirname($payloadPath));
    File::put($payloadPath, json_encode([
        [
            'license_category_code' => 'B',
            'source' => 'gov.pl-mi',
            'external_id' => 'EXT-META-001',
            'question_topic_key' => 'road_markings',
            'metadata' => [
                'current_topic_key' => 'road_position_entry_exit_stopping',
                'classifier_matched_by' => 'keyword:road_markings_reflectors',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:sync-question-topic-overrides', [
        'path' => $payloadPath,
    ])->assertSuccessful();

    $override = QuestionTopicOverride::query()->firstOrFail();

    expect($override->metadata)->toMatchArray([
        'current_topic_key' => 'road_position_entry_exit_stopping',
        'classifier_matched_by' => 'keyword:road_markings_reflectors',
    ]);
});

test('question topic override sync command dry run does not mutate the database', function () {
    LicenseCategory::factory()->withCode('B')->create();
    app(QuestionTopicAssigner::class)->seedTopics();

    $payloadPath = storage_path('app/testing/question-topic-overrides.json');

    File::ensureDirectoryExists(dirname($payloadPath));
    File::put($payloadPath, json_encode([
        [
            'license_category_code' => 'B',
            'source' => 'gov.pl-mi',
            'external_id' => 'EXT-DRY-001',
            'question_topic_key' => 'lane_change_and_turning',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:sync-question-topic-overrides', [
        'path' => $payloadPath,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Dry-run synchronizacji overrideow zakonczony.')
        ->assertSuccessful();

    expect(QuestionTopicOverride::query()->count())->toBe(0);
});

test('question topic override sync command rejects duplicate override keys inside one payload', function () {
    LicenseCategory::factory()->withCode('B')->create();
    app(QuestionTopicAssigner::class)->seedTopics();

    $payloadPath = storage_path('app/testing/question-topic-overrides.json');

    File::ensureDirectoryExists(dirname($payloadPath));
    File::put($payloadPath, json_encode([
        [
            'license_category_code' => 'B',
            'source' => 'gov.pl-mi',
            'external_id' => 'EXT-DUP-001',
            'question_topic_key' => 'road_markings',
        ],
        [
            'license_category_code' => 'B',
            'source' => 'gov.pl-mi',
            'external_id' => 'EXT-DUP-001',
            'question_topic_key' => 'lane_change_and_turning',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:sync-question-topic-overrides', [
        'path' => $payloadPath,
    ])
        ->expectsOutputToContain('zduplikowany rekord')
        ->assertFailed();

    expect(QuestionTopicOverride::query()->count())->toBe(0);
});
