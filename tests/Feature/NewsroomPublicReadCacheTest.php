<?php

use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Support\ContentArticlePublishingService;
use App\Support\NewsroomCategoryReadModelService;
use App\Support\NewsroomHomePlacementService;
use App\Support\NewsroomHomeReadModelService;
use App\Support\NewsroomPublicReadCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomCacheReviewedArticle(ContentCategory $category, array $attributes = []): ContentArticle
{
    $article = ContentArticle::factory()->inReview()->create([
        'category_id' => $category->id,
        ...$attributes,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    return app(ContentArticlePublishingService::class)->markReviewed($article);
}

test('home and category read models reuse cached snapshots until invalidated', function () {
    Carbon::setTestNow('2026-09-18 09:00:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Egzaminy',
        'slug' => 'egzaminy-cache',
    ]);

    $article = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Tytuł przed cache',
        'editorial_priority' => 100,
    ]);

    $home = app(NewsroomHomeReadModelService::class);
    $categoryRead = app(NewsroomCategoryReadModelService::class);
    $cache = app(NewsroomPublicReadCache::class);

    expect($home->build()['lead']['title'])->toBe('Tytuł przed cache')
        ->and($categoryRead->build($category->slug)['articles']->items()[0]['title'])->toBe('Tytuł przed cache');

    ContentArticle::query()
        ->whereKey($article->id)
        ->update(['title' => 'Tytuł zmieniony poza eventem']);

    expect($home->build()['lead']['title'])->toBe('Tytuł przed cache')
        ->and($categoryRead->build($category->slug)['articles']->items()[0]['title'])->toBe('Tytuł przed cache');

    $cache->invalidateAll();

    expect($home->build()['lead']['title'])->toBe('Tytuł zmieniony poza eventem')
        ->and($categoryRead->build($category->slug)['articles']->items()[0]['title'])->toBe('Tytuł zmieniony poza eventem');
});

test('publish invalidation exposes the article in cached home and category surfaces', function () {
    Carbon::setTestNow('2026-09-18 09:10:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'slug' => 'publikacja-cache',
    ]);
    $reviewed = newsroomCacheReviewedArticle($category, [
        'title' => 'Artykuł pojawia się po publikacji',
        'slug' => 'artykul-po-publikacji-cache',
        'is_featured' => true,
        'editorial_priority' => 100,
    ]);

    $home = app(NewsroomHomeReadModelService::class);
    $categoryRead = app(NewsroomCategoryReadModelService::class);

    expect($home->build()['lead'])->toBeNull()
        ->and($categoryRead->build($category->slug)['articles']->total())->toBe(0);

    app(ContentArticlePublishingService::class)->publish($reviewed->fresh());

    expect($home->build()['lead']['id'])->toBe($reviewed->id)
        ->and($categoryRead->build($category->slug)['articles']->total())->toBe(1)
        ->and($categoryRead->build($category->slug)['articles']->items()[0]['id'])->toBe($reviewed->id);
});

test('placement change invalidates cached home composition', function () {
    Carbon::setTestNow('2026-09-18 09:20:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create();

    $fallbackLead = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Fallback lead',
        'editorial_priority' => 100,
    ]);
    $manualLead = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Manual lead',
        'editorial_priority' => 0,
    ]);

    $home = app(NewsroomHomeReadModelService::class);

    expect($home->build()['lead']['id'])->toBe($fallbackLead->id);

    app(NewsroomHomePlacementService::class)->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $manualLead->id,
    ]);

    expect($home->build()['lead']['id'])->toBe($manualLead->id);
});

test('archive invalidation removes an article from cached active distribution sections', function () {
    Carbon::setTestNow('2026-09-18 09:30:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'slug' => 'archiwum-cache',
    ]);

    $article = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'title' => 'Artykuł przed archiwizacją',
        'editorial_priority' => 100,
    ]);

    $home = app(NewsroomHomeReadModelService::class);
    $categoryRead = app(NewsroomCategoryReadModelService::class);

    expect($home->build()['lead']['id'])->toBe($article->id)
        ->and($categoryRead->build($category->slug)['articles']->total())->toBe(1);

    app(ContentArticlePublishingService::class)->archive($article->fresh());

    expect($home->build()['lead'])->toBeNull()
        ->and($categoryRead->build($category->slug)['articles']->total())->toBe(0);
});

test('category metadata change invalidates cached home and category projections', function () {
    Carbon::setTestNow('2026-09-18 09:40:00');
    config(['newsroom.public_enabled' => true]);

    $category = ContentCategory::factory()->create([
        'name' => 'Stara nazwa',
        'slug' => 'metadata-cache',
        'position' => 10,
    ]);

    ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    $home = app(NewsroomHomeReadModelService::class);
    $categoryRead = app(NewsroomCategoryReadModelService::class);

    expect($home->build()['categories'][0]['category']['name'])->toBe('Stara nazwa')
        ->and($categoryRead->build($category->slug)['category']['name'])->toBe('Stara nazwa');

    $category->update(['name' => 'Nowa nazwa']);

    expect($home->build()['categories'][0]['category']['name'])->toBe('Nowa nazwa')
        ->and($categoryRead->build($category->slug)['category']['name'])->toBe('Nowa nazwa');
});

test('public read invalidation event is deferred until the surrounding transaction commits', function () {
    Carbon::setTestNow('2026-09-18 09:50:00');
    config(['newsroom.public_enabled' => true]);

    $article = ContentArticle::factory()->published()->create([
        'is_featured' => false,
        'editorial_priority' => 0,
    ]);
    $cache = app(NewsroomPublicReadCache::class);
    $before = $cache->homeKey();

    DB::beginTransaction();

    try {
        app(ContentArticlePublishingService::class)->setFeatured(
            $article->fresh(),
            true,
            100,
        );

        expect($cache->homeKey())->toBe($before);
    } finally {
        DB::rollBack();
    }

    expect($cache->homeKey())->toBe($before)
        ->and($article->fresh()->is_featured)->toBeFalse();

    app(ContentArticlePublishingService::class)->setFeatured(
        $article->fresh(),
        true,
        100,
    );

    expect($cache->homeKey())->not->toBe($before);
});
