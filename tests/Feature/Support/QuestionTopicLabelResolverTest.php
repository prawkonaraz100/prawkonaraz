<?php

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use App\Support\QuestionTopicLabelResolver;
use Illuminate\Database\QueryException;

test('question topic label resolver returns category specific custom label', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
    ]);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Znaki ostrzegawcze w kategorii B',
        'is_active' => true,
    ]);

    expect(app(QuestionTopicLabelResolver::class)->labelFor($category, $topic))
        ->toBe('Znaki ostrzegawcze w kategorii B');
});

test('question topic label resolver falls back to classifier display label', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'traffic_lights_and_controller_signals',
        'name' => 'Sygnaly swietlne, sygnaly dawane przez kierujacego ruchem',
    ]);

    expect(app(QuestionTopicLabelResolver::class)->labelFor($category, $topic))
        ->toBe('Sygnaly swietlne i kierujacy ruchem');
});

test('question topic label resolver falls back to topic name for unknown classifier key', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'custom_unknown_topic',
        'name' => 'Własny dział testowy',
    ]);

    expect(app(QuestionTopicLabelResolver::class)->labelFor($category, $topic))
        ->toBe('Własny dział testowy');
});

test('question topic label resolver ignores inactive custom labels', function () {
    $category = LicenseCategory::factory()->withCode('AM')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
    ]);

    QuestionTopicCategoryLabel::factory()->inactive()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Nieaktywna nazwa AM',
    ]);

    expect(app(QuestionTopicLabelResolver::class)->labelFor($category, $topic))
        ->toBe('Znaki ostrzegawcze');
});

test('question topic label resolver does not leak labels between categories', function () {
    $categoryAm = LicenseCategory::factory()->withCode('AM')->create();
    $categoryB = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'name' => 'Znaki ostrzegawcze',
    ]);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $categoryAm->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Znaki ostrzegawcze dla motorowerów',
    ]);

    $resolver = app(QuestionTopicLabelResolver::class);

    expect($resolver->labelFor($categoryAm, $topic))->toBe('Znaki ostrzegawcze dla motorowerów');
    expect($resolver->labelFor($categoryB, $topic))->toBe('Znaki ostrzegawcze');
});

test('question topic category labels are unique per category and topic', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
    ]);

    QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Pierwsza nazwa',
    ]);

    expect(fn () => QuestionTopicCategoryLabel::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'display_name' => 'Druga nazwa',
    ]))->toThrow(QueryException::class);
});
