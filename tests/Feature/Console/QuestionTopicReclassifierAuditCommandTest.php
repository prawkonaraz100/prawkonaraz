<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicOverride;
use App\Support\QuestionTopicAssigner;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-reclassifier-active-ready.json'));
    File::delete(storage_path('app/testing/question-topic-reclassifier-all.json'));
});

afterEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-reclassifier-active-ready.json'));
    File::delete(storage_path('app/testing/question-topic-reclassifier-all.json'));
});

test('question topic reclassifier audit command reports changed questions without mutating the database', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $categoryC = LicenseCategory::factory()->withCode('C')->create();

    $trafficSignals = QuestionTopic::query()->where('key', 'traffic_lights_and_controller_signals')->firstOrFail();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    $changedQuestion = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-001',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-002',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $trafficSignals->getKey(),
            'metadata' => [],
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-003',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
            'delivery_issue' => Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA,
        ]);

    Question::factory()
        ->for($categoryC, 'licenseCategory')
        ->create([
            'external_id' => 'C-AUDIT-001',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-reclassifier-active-ready.json');

    $this->artisan('content:audit-question-topic-reclassifier', [
        '--category' => 'B',
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('zmiana tematu: 1')
        ->expectsOutputToContain('Zakres kategorii: B')
        ->assertSuccessful();

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['scope'])->toBe('active_ready');
    expect($report['category_filter'])->toBe(['B']);
    expect($report['processed_questions'])->toBe(2);
    expect($report['changed_questions'])->toBe(1);
    expect($report['unchanged_questions'])->toBe(1);
    expect($report['category_summaries']['B'])->toMatchArray([
        'processed_questions' => 2,
        'changed_questions' => 1,
        'unchanged_questions' => 1,
    ]);
    expect($report['entries'])->toHaveCount(1);
    expect($report['entries'][0])->toMatchArray([
        'question_id' => $changedQuestion->getKey(),
        'external_id' => 'B-AUDIT-001',
        'category_code' => 'B',
        'current_topic_key' => 'special_caution_exiting_and_securing_vehicle',
        'proposed_topic_key' => 'traffic_lights_and_controller_signals',
        'matched_by' => 'keyword:traffic_signal',
        'changed' => true,
    ]);
    expect($report['current_topic_counts']['B'])->toMatchArray([
        'special_caution_exiting_and_securing_vehicle' => 1,
        'traffic_lights_and_controller_signals' => 1,
    ]);
    expect($report['proposed_topic_counts']['B']['traffic_lights_and_controller_signals'])->toBe(2);
    expect($report['matched_by_counts']['B']['keyword:traffic_signal'])->toBe(2);
    expect($report['transition_counts']['B']['special_caution_exiting_and_securing_vehicle']['traffic_lights_and_controller_signals'])->toBe(1);

    expect($changedQuestion->fresh()->question_topic_id)->toBe($specialCaution->getKey());
});

test('question topic reclassifier audit command can include unchanged questions and inspect all records', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $speedLimits = QuestionTopic::query()->where('key', 'speed_limits')->firstOrFail();
    $mechanicalSafety = QuestionTopic::query()->where('key', 'mechanical_aspects_of_safety')->firstOrFail();

    $changedQuestion = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-ALL-001',
            'prompt' => 'Jaka jest dopuszczalna predkosc pojazdu na tym odcinku drogi?',
            'question_topic_id' => $mechanicalSafety->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
            'is_active' => false,
            'delivery_issue' => Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA,
        ]);

    $unchangedQuestion = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-ALL-002',
            'prompt' => 'Jaka jest dopuszczalna predkosc pojazdu na tym odcinku drogi?',
            'question_topic_id' => $speedLimits->getKey(),
            'metadata' => [
                'structure_scope' => 'SPECJALISTYCZNY',
            ],
            'is_active' => false,
            'delivery_issue' => Question::DELIVERY_ISSUE_MISSING_PRIMARY_MEDIA,
        ]);

    $reportPath = storage_path('app/testing/question-topic-reclassifier-all.json');

    $this->artisan('content:audit-question-topic-reclassifier', [
        '--category' => 'B',
        '--scope' => 'all',
        '--include-unchanged' => true,
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('Scope: all')
        ->assertSuccessful();

    $report = json_decode((string) File::get($reportPath), true);

    expect($report['scope'])->toBe('all');
    expect($report['processed_questions'])->toBe(2);
    expect($report['changed_questions'])->toBe(1);
    expect($report['entries'])->toHaveCount(2);
    expect(collect($report['entries'])->pluck('external_id')->all())->toEqualCanonicalizing([
        $changedQuestion->external_id,
        $unchangedQuestion->external_id,
    ]);
    expect(collect($report['entries'])->where('external_id', $unchangedQuestion->external_id)->first()['changed'])->toBeFalse();
    expect($report['transition_counts']['B']['mechanical_aspects_of_safety']['speed_limits'])->toBe(1);
});

test('question topic reclassifier audit command reports override and effective topic separately from classifier', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();

    $laneChange = QuestionTopic::query()->where('key', 'lane_change_and_turning')->firstOrFail();

    QuestionTopicOverride::query()->create([
        'license_category_code' => 'B',
        'source' => 'gov.pl-mi',
        'external_id' => 'B-AUDIT-OVERRIDE-001',
        'question_topic_key' => 'road_markings',
        'reason' => 'Audyt PJ360 dla B',
        'is_active' => true,
    ]);

    $question = Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-AUDIT-OVERRIDE-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy sygnalizator nadaje sygnal zielony dla Twojego pasa ruchu?',
            'question_topic_id' => $laneChange->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-reclassifier-active-ready.json');

    $this->artisan('content:audit-question-topic-reclassifier', [
        '--category' => 'B',
        '--json' => $reportPath,
    ])->assertSuccessful();

    $report = json_decode((string) File::get($reportPath), true);
    $entry = collect($report['entries'])
        ->firstWhere('question_id', $question->getKey());

    expect($entry)->not->toBeNull();
    expect($entry)->toMatchArray([
        'current_topic_key' => 'lane_change_and_turning',
        'classifier_topic_key' => 'traffic_lights_and_controller_signals',
        'classifier_matched_by' => 'keyword:traffic_signal',
        'override_topic_key' => 'road_markings',
        'override_reason' => 'Audyt PJ360 dla B',
        'resolution_source' => 'override',
        'proposed_topic_key' => 'road_markings',
        'matched_by' => 'override:road_markings',
        'changed' => true,
    ]);
    expect($report['classifier_topic_counts']['B']['traffic_lights_and_controller_signals'])->toBe(1);
    expect($report['proposed_topic_counts']['B']['road_markings'])->toBe(1);
    expect($report['matched_by_counts']['B']['override:road_markings'])->toBe(1);
    expect($report['classifier_matched_by_counts']['B']['keyword:traffic_signal'])->toBe(1);
});
