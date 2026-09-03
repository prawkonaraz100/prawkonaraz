<?php

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionTopic;
use App\Support\QuestionTopicAssigner;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-delta-report.json'));
});

afterEach(function (): void {
    File::delete(storage_path('app/testing/question-topic-delta-report.json'));
});

test('question topic assignment delta report command writes current vs proposed topic deltas', function () {
    app(QuestionTopicAssigner::class)->seedTopics();

    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $specialCaution = QuestionTopic::query()->where('key', 'special_caution_exiting_and_securing_vehicle')->firstOrFail();

    Question::factory()
        ->for($categoryB, 'licenseCategory')
        ->create([
            'external_id' => 'B-DELTA-001',
            'source' => 'gov.pl-mi',
            'prompt' => 'Czy czerwone swiatlo sygnalizatora zabrania wjazdu za sygnalizator?',
            'question_topic_id' => $specialCaution->getKey(),
            'metadata' => [],
        ]);

    $reportPath = storage_path('app/testing/question-topic-delta-report.json');

    $this->artisan('content:report-question-topic-assignment-delta', [
        '--category' => 'B',
        '--json' => $reportPath,
    ])
        ->expectsOutputToContain('changed_questions=1')
        ->assertSuccessful();

    $payload = json_decode((string) File::get($reportPath), true);

    expect($payload['changed_questions'])->toBe(1);
    expect($payload['changed_topic_rows'])->toBe(2);
    expect($payload['delta_by_category']['B'])->toEqualCanonicalizing([
        [
            'topic_key' => 'special_caution_exiting_and_securing_vehicle',
            'topic_label' => 'Szczegolna ostroznosc i pojazd',
            'current_count' => 1,
            'proposed_count' => 0,
            'delta' => -1,
        ],
        [
            'topic_key' => 'traffic_lights_and_controller_signals',
            'topic_label' => 'Sygnaly swietlne i kierujacy ruchem',
            'current_count' => 0,
            'proposed_count' => 1,
            'delta' => 1,
        ],
    ]);
});
