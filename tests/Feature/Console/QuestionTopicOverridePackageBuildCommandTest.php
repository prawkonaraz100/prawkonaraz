<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Support\QuestionTopicAssigner;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-override-package-spec.json'));
    File::delete(storage_path('app/testing/question-topic-override-package.json'));
});

afterEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-override-package-spec.json'));
    File::delete(storage_path('app/testing/question-topic-override-package.json'));
});

test('question topic override package build command writes a review only package from spec', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();
    $trafficLights = QuestionTopic::query()->where('key', 'traffic_lights_and_controller_signals')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-PACKAGE-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-PACKAGE-002',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $trafficLights->getKey(),
            'metadata' => [],
        ]);

    $specPath = storage_path('app/testing/question-topic-override-package-spec.json');
    $reportPath = storage_path('app/testing/question-topic-override-package.json');

    File::ensureDirectoryExists(dirname($specPath));
    File::put($specPath, json_encode([
        'category' => 'B',
        'scope' => 'active_ready',
        'rules' => [
            [
                'name' => 'b-traffic-signal',
                'target_topic_key' => 'traffic_lights_and_controller_signals',
                'reason' => 'PJ360 B: sygnaly swietlne',
                'current_topic_keys' => ['special_caution_exiting_and_securing_vehicle'],
                'classifier_matched_by' => ['keyword:traffic_signal'],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:build-question-topic-override-package', [
        'path' => $specPath,
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('exported=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['category'])->toBe('B');
    expect($payload['scope'])->toBe('active_ready');
    expect($payload['rules_count'])->toBe(1);
    expect($payload['exported_candidates'])->toBe(1);
    expect($payload['source_spec_path'])->toBe($specPath);
    expect($payload['rule_summaries'])->toHaveCount(1);
    expect($payload['rule_summaries'][0])->toMatchArray([
        'name' => 'b-traffic-signal',
        'target_topic_key' => 'traffic_lights_and_controller_signals',
        'matched_questions' => 1,
    ]);
    expect($payload['overrides'])->toHaveCount(1);
    expect($payload['overrides'][0])->toMatchArray([
        'license_category_code' => 'B',
        'source' => 'gov.pl-mi',
        'external_id' => 'B-PACKAGE-001',
        'question_topic_key' => 'traffic_lights_and_controller_signals',
        'reason' => 'PJ360 B: sygnaly swietlne',
    ]);
    expect(data_get($payload['overrides'][0], 'metadata.package_rule'))->toBe('b-traffic-signal');
    expect(data_get($payload['overrides'][0], 'metadata.classifier_matched_by'))->toBe('keyword:traffic_signal');
});

test('question topic override package build command deduplicates the same stable question identity across rules', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-PACKAGE-DUP-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    $specPath = storage_path('app/testing/question-topic-override-package-spec.json');
    $reportPath = storage_path('app/testing/question-topic-override-package.json');

    File::ensureDirectoryExists(dirname($specPath));
    File::put($specPath, json_encode([
        'category' => 'B',
        'rules' => [
            [
                'name' => 'first-rule',
                'target_topic_key' => 'traffic_lights_and_controller_signals',
                'reason' => 'Pierwsza regula',
                'classifier_matched_by' => ['keyword:traffic_signal'],
            ],
            [
                'name' => 'second-rule',
                'target_topic_key' => 'road_markings',
                'reason' => 'Druga regula',
                'classifier_matched_by' => ['keyword:traffic_signal'],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:build-question-topic-override-package', [
        'path' => $specPath,
        '--json' => $reportPath,
    ])->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['exported_candidates'])->toBe(1);
    expect($payload['overrides'])->toHaveCount(1);
    expect($payload['overrides'][0]['question_topic_key'])->toBe('traffic_lights_and_controller_signals');
    expect($payload['rule_summaries'])->toEqualCanonicalizing([
        [
            'name' => 'first-rule',
            'target_topic_key' => 'traffic_lights_and_controller_signals',
            'matched_questions' => 1,
        ],
        [
            'name' => 'second-rule',
            'target_topic_key' => 'road_markings',
            'matched_questions' => 0,
        ],
    ]);
});

test('question topic override package build command supports prompt media and external id filters', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-PACKAGE-FILTER-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy w tej sytuacji masz obowiązek ustąpić pierwszeństwa pieszemu na przejściu?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [
                'main_media_original' => 'D6_001org.jpg',
            ],
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-PACKAGE-FILTER-002',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy w tej sytuacji masz obowiązek ustąpić pierwszeństwa tramwajowi na skrzyżowaniu?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [
                'main_media_original' => 'D6_002org.jpg',
            ],
        ]);

    $specPath = storage_path('app/testing/question-topic-override-package-spec.json');
    $reportPath = storage_path('app/testing/question-topic-override-package.json');

    File::ensureDirectoryExists(dirname($specPath));
    File::put($specPath, json_encode([
        'category' => 'B',
        'rules' => [
            [
                'name' => 'pedestrian-d6-fallback-cleanup',
                'target_topic_key' => 'pedestrians_and_reduced_mobility',
                'reason' => 'PJ360 B: piesi na przejsciu przy znaku D-6',
                'current_topic_keys' => ['special_caution_exiting_and_securing_vehicle'],
                'prompt_contains_any' => ['pieszemu', 'przejściu'],
                'prompt_not_contains_any' => ['tramwajowi'],
                'media_contains_any' => ['d6'],
                'external_ids' => ['b-package-filter-001'],
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:build-question-topic-override-package', [
        'path' => $specPath,
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('exported=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['exported_candidates'])->toBe(1);
    expect($payload['rule_summaries'])->toEqualCanonicalizing([
        [
            'name' => 'pedestrian-d6-fallback-cleanup',
            'target_topic_key' => 'pedestrians_and_reduced_mobility',
            'matched_questions' => 1,
        ],
    ]);
    expect($payload['overrides'])->toHaveCount(1);
    expect($payload['overrides'][0])->toMatchArray([
        'external_id' => 'B-PACKAGE-FILTER-001',
        'question_topic_key' => 'pedestrians_and_reduced_mobility',
        'reason' => 'PJ360 B: piesi na przejsciu przy znaku D-6',
    ]);
    expect(data_get($payload['overrides'][0], 'metadata.main_media_original'))->toBe('D6_001org.jpg');
});

test('question topic override package build command rejects invalid spec', function () {
    $specPath = storage_path('app/testing/question-topic-override-package-spec.json');

    File::ensureDirectoryExists(dirname($specPath));
    File::put($specPath, json_encode([
        'category' => 'B',
        'rules' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $this->artisan('content:build-question-topic-override-package', [
        'path' => $specPath,
    ])
        ->expectsOutputToContain('Spec musi zawierac niepusta liste "rules".')
        ->assertFailed();
});
