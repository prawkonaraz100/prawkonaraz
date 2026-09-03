<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopicOverride;
use App\Support\QuestionTopicAssigner;
use App\Support\QuestionTopicOverrideResolver;

test('question topic override resolver resolves override by category source and external id', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    app(QuestionTopicAssigner::class)->seedTopics();

    $question = Question::factory()
        ->for($category, 'licenseCategory')
        ->create([
            'external_id' => 'EXT-RESOLVE-001',
            'source' => 'gov.pl-mi',
        ]);

    $override = QuestionTopicOverride::query()->create([
        'license_category_code' => 'B',
        'source' => 'gov.pl-mi',
        'external_id' => 'EXT-RESOLVE-001',
        'question_topic_key' => 'road_markings',
        'reason' => 'Audit PJ360',
        'is_active' => true,
    ]);

    $resolution = app(QuestionTopicOverrideResolver::class)->resolve($question);

    expect($resolution)->not->toBeNull();
    expect($resolution)->toMatchArray([
        'key' => 'road_markings',
        'matched_by' => 'override:road_markings',
        'reason' => 'Audit PJ360',
        'override_id' => $override->getKey(),
        'category_code' => 'B',
    ]);
});

test('question topic assigner uses override and does not leak between categories', function () {
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryC = LicenseCategory::factory()->withCode('C')->create();
    $assigner = app(QuestionTopicAssigner::class);
    $assigner->seedTopics();

    $questionB = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'EXT-ASSIGN-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    $questionC = Question::factory()
        ->for($categoryC, 'licenseCategory')
        ->create([
            'external_id' => 'EXT-ASSIGN-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'metadata' => [
                'structure_scope' => 'PODSTAWOWY',
            ],
        ]);

    QuestionTopicOverride::query()->create([
        'license_category_code' => 'B',
        'source' => 'gov.pl-mi',
        'external_id' => 'EXT-ASSIGN-001',
        'question_topic_key' => 'speed_limits',
        'reason' => 'Kontrolowany override dla B',
        'is_active' => true,
    ]);

    $topicB = $assigner->assign($questionB->fresh());
    $topicC = $assigner->assign($questionC->fresh());

    expect($topicB?->key)->toBe('speed_limits');
    expect($topicC?->key)->toBe('traffic_lights_and_controller_signals');
    expect(data_get($questionB->fresh()->metadata, 'structure_scope'))->toBe('SPECJALISTYCZNY');
    expect(data_get($questionC->fresh()->metadata, 'structure_scope'))->toBe('PODSTAWOWY');
});
