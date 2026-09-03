<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Support\QuestionTopicAssigner;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-override-candidates.json'));
});

afterEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-override-candidates.json'));
});

test('question topic override candidate export command writes review-only candidates for changed classifier results', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-EXPORT-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-override-candidates.json');

    $this->artisan('content:export-question-topic-override-candidates', [
        '--category' => 'B',
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('exported=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['exported_candidates'])->toBe(1);
    expect($payload['overrides'])->toHaveCount(1);
    expect($payload['overrides'][0])->toMatchArray([
        'license_category_code' => 'B',
        'source' => 'gov.pl-mi',
        'external_id' => 'B-EXPORT-001',
        'question_topic_key' => 'traffic_lights_and_controller_signals',
    ]);
    expect(data_get($payload['overrides'][0], 'metadata.current_topic_key'))->toBe('special_caution_exiting_and_securing_vehicle');
    expect(data_get($payload['overrides'][0], 'metadata.classifier_matched_by'))->toBe('keyword:traffic_signal');
    expect(data_get($payload['overrides'][0], 'metadata.audit_generated_at'))->not->toBeNull();
});

test('question topic override candidate export command skips fallback-only candidates by default', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $laneChange = QuestionTopic::query()->where('key', 'lane_change_and_turning')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-EXPORT-FALLBACK-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy w tej sytuacji masz prawo - mimo podawanego sygnalu - skrecic w prawo?',
            'question_topic_id' => $laneChange->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-override-candidates.json');

    $this->artisan('content:export-question-topic-override-candidates', [
        '--category' => 'B',
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('skipped_low_confidence=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['exported_candidates'])->toBe(0);
    expect($payload['skipped_low_confidence'])->toBe(1);
    expect($payload['overrides'])->toBe([]);
});

test('question topic override candidate export command can narrow candidates by matched_by and topic filters', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-EXPORT-FILTER-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-EXPORT-FILTER-002',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy w tej sytuacji widoczna linia przerywana wyznacza Ci miejsce zatrzymania w zwiazku ze znakiem stop?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-override-candidates.json');

    $this->artisan('content:export-question-topic-override-candidates', [
        '--category' => 'B',
        '--matched-by' => 'keyword:traffic_signal',
        '--topic' => 'traffic_lights_and_controller_signals',
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('exported=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['matched_by_filter'])->toBe(['keyword:traffic_signal']);
    expect($payload['topic_filter'])->toBe(['traffic_lights_and_controller_signals']);
    expect($payload['exported_candidates'])->toBe(1);
    expect($payload['overrides'][0]['external_id'])->toBe('B-EXPORT-FILTER-001');
    expect($payload['skipped_by_matched_by_filter'])->toBeGreaterThanOrEqual(1);
});
