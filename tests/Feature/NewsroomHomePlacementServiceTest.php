<?php

use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Models\User;
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


test('placement writer rejects stale same-second update before overwriting concurrent state', function () {
    Carbon::setTestNow('2026-09-16 18:00:00');

    $first = ContentArticle::factory()->published()->create();
    $second = ContentArticle::factory()->published()->create();
    $third = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    $placement = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $first->id,
    ]);

    $loadedToken = $service->editToken($placement);

    ContentHomePlacement::query()
        ->whereKey($placement->id)
        ->update([
            'article_id' => $second->id,
            'updated_at' => $placement->updated_at,
        ]);

    expect(fn () => $service->update(
        $placement,
        [
            'article_id' => $third->id,
        ],
        $loadedToken,
    ))->toThrow(DomainException::class, 'changed concurrently');

    expect($placement->fresh()->article_id)->toBe($second->id);
});

test('placement delete uses stale token guard and audit user actor', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->published()->create();
    $service = newsroomHomePlacementWriter();

    $placement = $service->create([
        'slot_key' => ContentHomePlacement::SLOT_IMPORTANT_NOW,
        'position' => 0,
        'article_id' => $article->id,
    ], $admin);

    $loadedToken = $service->editToken($placement);

    expect(AuditLog::query()
        ->where('action', 'content_home_placement.created')
        ->where('entity_id', (string) $placement->id)
        ->where('actor_user_id', $admin->id)
        ->exists())->toBeTrue();

    $placement->forceFill([
        'ends_at' => now()->addHour(),
    ])->save();

    expect(fn () => $service->delete(
        $placement,
        $loadedToken,
        $admin,
    ))->toThrow(DomainException::class, 'changed concurrently');

    $fresh = $placement->fresh();
    expect($fresh)->not->toBeNull();

    $freshToken = $service->editToken($fresh);
    $service->delete($fresh, $freshToken, $admin);

    expect(ContentHomePlacement::query()->whereKey($placement->id)->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('action', 'content_home_placement.deleted')
            ->where('entity_id', (string) $placement->id)
            ->where('actor_user_id', $admin->id)
            ->exists())->toBeTrue();
});
