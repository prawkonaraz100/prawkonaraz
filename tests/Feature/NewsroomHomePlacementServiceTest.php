<?php

use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Support\NewsroomHomePlacementService;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomHomePlacementWriter(): NewsroomHomePlacementService
{
    return app(NewsroomHomePlacementService::class);
}

test('placement writer rejects an overlapping interval for the same slot tuple', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $first = ContentArticle::factory()->published()->create();
    $second = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 0,
        'article_id' => $first->id,
        'starts_at' => '2026-09-16 10:00:00',
        'ends_at' => '2026-09-16 12:00:00',
    ]);

    expect(fn () => $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 0,
        'article_id' => $second->id,
        'starts_at' => '2026-09-16 11:00:00',
        'ends_at' => '2026-09-16 13:00:00',
    ]))->toThrow(DomainException::class);
});

test('placement writer allows adjacent intervals and different positions', function () {
    $first = ContentArticle::factory()->published()->create();
    $second = ContentArticle::factory()->published()->create();
    $third = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 0,
        'article_id' => $first->id,
        'starts_at' => '2026-09-16 10:00:00',
        'ends_at' => '2026-09-16 12:00:00',
    ]);

    $adjacent = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 0,
        'article_id' => $second->id,
        'starts_at' => '2026-09-16 12:00:00',
        'ends_at' => '2026-09-16 14:00:00',
    ]);

    $parallelPosition = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 1,
        'article_id' => $third->id,
        'starts_at' => '2026-09-16 11:00:00',
        'ends_at' => '2026-09-16 13:00:00',
    ]);

    expect($adjacent)->not->toBeNull()
        ->and($parallelPosition)->not->toBeNull()
        ->and(ContentHomePlacement::query()->count())->toBe(3);
});

test('placement writer validates controlled category and guide slot contexts', function () {
    $categoryA = ContentCategory::factory()->create([
        'slug' => 'egzaminy',
    ]);
    $categoryB = ContentCategory::factory()->create([
        'slug' => 'przepisy',
    ]);

    $articleA = ContentArticle::factory()->published()->create([
        'category_id' => $categoryA->id,
    ]);
    $articleB = ContentArticle::factory()->published()->create([
        'category_id' => $categoryB->id,
    ]);
    $guide = ContentArticle::factory()->published()->guide()->create([
        'category_id' => $categoryA->id,
    ]);

    $service = newsroomHomePlacementWriter();

    $categoryPlacement = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_CATEGORY_LEAD,
        'context_key' => $categoryA->slug,
        'article_id' => $articleA->id,
    ]);

    $guidePlacement = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_GUIDES_LEAD,
        'article_id' => $guide->id,
    ]);

    expect($categoryPlacement->context_key)->toBe('egzaminy')
        ->and($guidePlacement->article_id)->toBe($guide->id)
        ->and(fn () => $service->create([
            'slot_key' => ContentHomePlacement::SLOT_CATEGORY_LEAD,
            'context_key' => $categoryA->slug,
            'position' => 1,
            'article_id' => $articleB->id,
        ]))->toThrow(DomainException::class)
        ->and(fn () => $service->create([
            'slot_key' => ContentHomePlacement::SLOT_GUIDES_LEAD,
            'position' => 1,
            'article_id' => $articleA->id,
        ]))->toThrow(DomainException::class);
});

test('placement writer update ignores itself but rejects overlap after tuple move', function () {
    $first = ContentArticle::factory()->published()->create();
    $second = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    $existing = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 0,
        'article_id' => $first->id,
        'starts_at' => '2026-09-16 10:00:00',
        'ends_at' => '2026-09-16 12:00:00',
    ]);

    $updated = $service->update(
        $existing,
        [
            'ends_at' => '2026-09-16 11:30:00',
        ],
        $service->editToken($existing),
    );

    expect($updated->ends_at?->toDateTimeString())->toBe('2026-09-16 11:30:00');

    $other = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'position' => 1,
        'article_id' => $second->id,
        'starts_at' => '2026-09-16 10:00:00',
        'ends_at' => '2026-09-16 12:00:00',
    ]);

    expect(fn () => $service->update(
        $other,
        [
            'position' => 0,
        ],
        $service->editToken($other),
    ))->toThrow(DomainException::class);
});

test('placement writer rejects invalid intervals unknown contexts and unsupported surface keys', function () {
    $article = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    expect(fn () => $service->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $article->id,
        'starts_at' => '2026-09-16 12:00:00',
        'ends_at' => '2026-09-16 11:00:00',
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $service->create([
            'slot_key' => ContentHomePlacement::SLOT_CATEGORY_LEAD,
            'context_key' => 'nie-istnieje',
            'article_id' => $article->id,
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $service->create([
            'surface_key' => 'custom_surface',
            'slot_key' => ContentHomePlacement::SLOT_LEAD,
            'article_id' => $article->id,
        ]))->toThrow(InvalidArgumentException::class);
});
