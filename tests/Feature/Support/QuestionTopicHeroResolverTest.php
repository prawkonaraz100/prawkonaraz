<?php

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryHero;
use App\Support\QuestionTopicHeroResolver;
use Illuminate\Database\QueryException;

test('question topic hero resolver returns category specific hero before global fallback', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'speed_limits',
        'hero_image_path' => 'study/topic-heroes/default/speed-limits.webp',
        'hero_image_alt' => 'Domyślne zdjęcie prędkości',
        'hero_image_position' => 'left center',
    ]);

    QuestionTopicCategoryHero::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'hero_image_path' => 'study/topic-heroes/b/speed-limits.webp',
        'hero_image_alt' => 'Prędkości i ograniczenia w kategorii B',
        'hero_image_position' => 'right center',
    ]);

    $hero = app(QuestionTopicHeroResolver::class)->heroFor($category, $topic);

    expect($hero)
        ->not->toBeNull()
        ->and($hero['image_path'])->toBe('study/topic-heroes/b/speed-limits.webp')
        ->and($hero['image_alt'])->toBe('Prędkości i ograniczenia w kategorii B')
        ->and($hero['image_position'])->toBe('right center')
        ->and($hero['source'])->toBe('category');
});

test('question topic hero resolver falls back to global topic hero', function () {
    $category = LicenseCategory::factory()->withCode('C')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'hero_image_path' => 'study/topic-heroes/default/warning-signs.webp',
        'hero_image_alt' => 'Znaki ostrzegawcze',
        'hero_image_position' => 'center',
    ]);

    $hero = app(QuestionTopicHeroResolver::class)->heroFor($category, $topic);

    expect($hero)
        ->not->toBeNull()
        ->and($hero['image_path'])->toBe('study/topic-heroes/default/warning-signs.webp')
        ->and($hero['image_alt'])->toBe('Znaki ostrzegawcze')
        ->and($hero['image_position'])->toBe('center')
        ->and($hero['source'])->toBe('topic');
});

test('question topic hero resolver ignores inactive category hero', function () {
    $category = LicenseCategory::factory()->withCode('D')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'vehicle_load_and_passenger_safety',
        'hero_image_path' => 'study/topic-heroes/default/passengers.webp',
        'hero_image_alt' => 'Przewożone osoby',
    ]);

    QuestionTopicCategoryHero::factory()->inactive()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'hero_image_path' => 'study/topic-heroes/d/passengers.webp',
    ]);

    $hero = app(QuestionTopicHeroResolver::class)->heroFor($category, $topic);

    expect($hero)
        ->not->toBeNull()
        ->and($hero['image_path'])->toBe('study/topic-heroes/default/passengers.webp')
        ->and($hero['source'])->toBe('topic');
});

test('question topic hero resolver returns null when no hero exists', function () {
    $category = LicenseCategory::factory()->withCode('AM')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
        'hero_image_path' => null,
    ]);

    expect(app(QuestionTopicHeroResolver::class)->heroFor($category, $topic))
        ->toBeNull();
});

test('question topic category heroes are unique per category and topic', function () {
    $category = LicenseCategory::factory()->withCode('B')->create();
    $topic = QuestionTopic::factory()->create([
        'key' => 'warning_signs',
    ]);

    QuestionTopicCategoryHero::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'hero_image_path' => 'study/topic-heroes/b/first.webp',
    ]);

    expect(fn () => QuestionTopicCategoryHero::factory()->create([
        'license_category_id' => $category->getKey(),
        'question_topic_id' => $topic->getKey(),
        'hero_image_path' => 'study/topic-heroes/b/second.webp',
    ]))->toThrow(QueryException::class);
});

