<?php

use App\Models\Question;
use App\Models\QuestionMedia;
use App\Support\QuestionMediaPayloadBuilder;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'media.public_disk' => 'public',
        'media.public_base_url' => 'https://media.example.test',
    ]);
});

test('forQuestion prefers full image variant when both full and thumb exist', function () {
    $question = Question::factory()->create();

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-1/full.webp',
            'variant' => 'full',
            'width' => 1600,
            'height' => 900,
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'group-1'],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-1/thumb.webp',
            'variant' => 'thumb',
            'width' => 480,
            'height' => 270,
            'sort_order' => 1,
            'metadata' => ['asset_group' => 'group-1'],
        ]);

    $payload = app(QuestionMediaPayloadBuilder::class)->forQuestion($question->media()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['kind'])->toBe('image')
        ->and($payload[0]['url'])->toBe('https://media.example.test/questions/b/group-1/full.webp')
        ->and($payload[0]['full_url'])->toBe('https://media.example.test/questions/b/group-1/full.webp')
        ->and($payload[0]['thumb_url'])->toBe('https://media.example.test/questions/b/group-1/thumb.webp')
        ->and($payload[0]['variant'])->toBe('full')
        ->and($payload[0]['width'])->toBe(1600)
        ->and($payload[0]['height'])->toBe(900);
});

test('forCatalog prefers thumb image variant but keeps full dimensions', function () {
    $question = Question::factory()->create();

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-2/full.webp',
            'variant' => 'full',
            'width' => 1280,
            'height' => 720,
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'group-2'],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-2/thumb.webp',
            'variant' => 'thumb',
            'width' => 480,
            'height' => 270,
            'sort_order' => 1,
            'metadata' => ['asset_group' => 'group-2'],
        ]);

    $payload = app(QuestionMediaPayloadBuilder::class)->forCatalog($question->media()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['url'])->toBe('https://media.example.test/questions/b/group-2/thumb.webp')
        ->and($payload[0]['full_url'])->toBe('https://media.example.test/questions/b/group-2/full.webp')
        ->and($payload[0]['thumb_url'])->toBe('https://media.example.test/questions/b/group-2/thumb.webp')
        ->and($payload[0]['variant'])->toBe('thumb')
        ->and($payload[0]['width'])->toBe(1280)
        ->and($payload[0]['height'])->toBe(720);
});

test('forQuestion groups media variants by asset_group and keeps separate assets', function () {
    $question = Question::factory()->create();

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-a/full.webp',
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'group-a'],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-a/thumb.webp',
            'variant' => 'thumb',
            'sort_order' => 1,
            'metadata' => ['asset_group' => 'group-a'],
        ]);

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-b/full.webp',
            'variant' => 'full',
            'sort_order' => 2,
            'metadata' => ['asset_group' => 'group-b'],
        ]);

    $payload = app(QuestionMediaPayloadBuilder::class)->forQuestion($question->media()->get());
    $urls = array_column($payload, 'url');

    expect($payload)->toHaveCount(2)
        ->and($urls)->toContain('https://media.example.test/questions/b/group-a/full.webp')
        ->and($urls)->toContain('https://media.example.test/questions/b/group-b/full.webp');
});

test('forQuestion falls back to thumb variant when full image is missing', function () {
    $question = Question::factory()->create();

    QuestionMedia::factory()
        ->for($question)
        ->create([
            'kind' => 'image',
            'disk' => 'public',
            'path' => 'questions/b/group-thumb-only/thumb.webp',
            'variant' => 'thumb',
            'width' => 640,
            'height' => 360,
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'group-thumb-only'],
        ]);

    $payload = app(QuestionMediaPayloadBuilder::class)->forQuestion($question->media()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['url'])->toBe('https://media.example.test/questions/b/group-thumb-only/thumb.webp')
        ->and($payload[0]['full_url'])->toBeNull()
        ->and($payload[0]['thumb_url'])->toBe('https://media.example.test/questions/b/group-thumb-only/thumb.webp')
        ->and($payload[0]['variant'])->toBe('thumb')
        ->and($payload[0]['width'])->toBe(640)
        ->and($payload[0]['height'])->toBe(360);
});

test('video payload uses full video url and poster url', function () {
    $question = Question::factory()->create();

    QuestionMedia::factory()
        ->for($question)
        ->video()
        ->create([
            'disk' => 'public',
            'path' => 'questions/b/video/full.mp4',
            'poster_path' => 'questions/b/video/poster.webp',
            'variant' => 'full',
            'duration_seconds' => 17,
            'width' => 1920,
            'height' => 1080,
            'sort_order' => 0,
            'metadata' => ['asset_group' => 'video-group'],
        ]);

    $payload = app(QuestionMediaPayloadBuilder::class)->forQuestion($question->media()->get());

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['kind'])->toBe('video')
        ->and($payload[0]['url'])->toBe('https://media.example.test/questions/b/video/full.mp4')
        ->and($payload[0]['full_url'])->toBe('https://media.example.test/questions/b/video/full.mp4')
        ->and($payload[0]['thumb_url'])->toBeNull()
        ->and($payload[0]['poster_url'])->toBe('https://media.example.test/questions/b/video/poster.webp')
        ->and($payload[0]['duration_seconds'])->toBe(17)
        ->and($payload[0]['width'])->toBe(1920)
        ->and($payload[0]['height'])->toBe(1080);
});
