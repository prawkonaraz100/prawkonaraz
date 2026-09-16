<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use App\Support\ContentArticlePublishingService;
use App\Support\NewsroomHomeCompositionService;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function newsroomHomeComposer(): NewsroomHomeCompositionService
{
    return app(NewsroomHomeCompositionService::class);
}

function newsroomReadyScheduledArticle(
    ContentCategory $category,
    string $scheduledFor,
): ContentArticle {
    $article = ContentArticle::factory()->inReview()->create([
        'category_id' => $category->id,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $service = app(ContentArticlePublishingService::class);
    $reviewed = $service->markReviewed($article);

    return $service->schedule($reviewed, Carbon::parse($scheduledFor));
}

/**
 * @param  array<string, mixed>  $composition
 * @return list<int>
 */
function newsroomHomeCardIds(array $composition): array
{
    $ids = [];

    if ($composition['lead'] instanceof ContentArticle) {
        $ids[] = (int) $composition['lead']->id;
    }

    foreach ($composition['secondary'] as $article) {
        $ids[] = (int) $article->id;
    }

    foreach ($composition['latest'] as $article) {
        $ids[] = (int) $article->id;
    }

    foreach ($composition['categories'] as $block) {
        if ($block['lead'] instanceof ContentArticle) {
            $ids[] = (int) $block['lead']->id;
        }

        foreach ($block['items'] as $article) {
            $ids[] = (int) $article->id;
        }
    }

    if ($composition['guides']['lead'] instanceof ContentArticle) {
        $ids[] = (int) $composition['guides']['lead']->id;
    }

    foreach ($composition['guides']['items'] as $article) {
        $ids[] = (int) $article->id;
    }

    foreach ($composition['important_now'] as $article) {
        $ids[] = (int) $article->id;
    }

    return $ids;
}

test('manual lead placement wins only while its target remains actively distributed', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create([
        'position' => 10,
    ]);

    $manual = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'editorial_priority' => -10,
    ]);

    $fallback = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'editorial_priority' => 100,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $manual->id,
        'position' => 0,
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($composition['lead']?->id)->toBe($manual->id);

    $manual->forceFill([
        'workflow_status' => ContentArticleWorkflowStatus::NeedsReview,
        'needs_review_at' => now(),
    ])->save();

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($composition['lead']?->id)->toBe($fallback->id);
});

test('future and expired placements are ignored at the current render time', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create();

    $future = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'editorial_priority' => -50,
    ]);
    $expired = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'editorial_priority' => -40,
    ]);
    $fallback = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'editorial_priority' => 30,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $future->id,
        'starts_at' => now()->addHour(),
    ]);
    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'position' => 1,
        'article_id' => $expired->id,
        'ends_at' => now()->subMinute(),
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($composition['lead']?->id)->toBe($fallback->id);
});

test('future preview can resolve a valid scheduled manual placement only after its scheduled publish time', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $category = ContentCategory::factory()->create();
    $scheduled = newsroomReadyScheduledArticle($category, '2026-09-16 10:00:00');

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $scheduled->id,
    ]);

    $before = newsroomHomeComposer()->compose(
        Carbon::parse('2026-09-16 09:59:00'),
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    $after = newsroomHomeComposer()->compose(
        Carbon::parse('2026-09-16 10:00:00'),
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($before['lead'])->toBeNull()
        ->and($after['lead']?->id)->toBe($scheduled->id)
        ->and($scheduled->fresh()->workflow_status)->toBe(ContentArticleWorkflowStatus::Scheduled)
        ->and($scheduled->fresh()->first_published_at)->toBeNull();
});

test('future preview rejects scheduled placements that no longer satisfy publication requirements', function () {
    Carbon::setTestNow('2026-09-16 08:00:00');

    $category = ContentCategory::factory()->create();
    $scheduled = newsroomReadyScheduledArticle($category, '2026-09-16 10:00:00');
    $scheduled->sources()->delete();

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $scheduled->id,
    ]);

    $composition = newsroomHomeComposer()->compose(
        Carbon::parse('2026-09-16 10:00:00'),
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($composition['lead'])->toBeNull();
});

test('composition globally deduplicates cards in lead secondary latest category guides and important now order', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create([
        'slug' => 'egzaminy',
        'position' => 10,
    ]);

    $articles = ContentArticle::factory()
        ->published()
        ->count(9)
        ->sequence(fn ($sequence): array => [
            'category_id' => $category->id,
            'editorial_priority' => 100 - $sequence->index,
            'first_published_at' => now()->subMinutes($sequence->index + 1),
            'published_at' => now()->subMinutes($sequence->index + 1),
        ])
        ->create();

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $articles[0]->id,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_SECONDARY,
        'article_id' => $articles[0]->id,
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 2,
        latestLimit: 2,
        categoryItemsLimit: 2,
        guidesItemsLimit: 0,
        importantNowLimit: 2,
    );

    $ids = newsroomHomeCardIds($composition);

    expect($composition['lead']?->id)->toBe($articles[0]->id)
        ->and($composition['secondary'])->toHaveCount(2)
        ->and($ids)->toHaveCount(count(array_unique($ids)));
});

test('category lead placement is context aware and falls back to an article from the requested category', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $categoryA = ContentCategory::factory()->create([
        'slug' => 'egzaminy',
        'position' => 10,
    ]);
    $categoryB = ContentCategory::factory()->create([
        'slug' => 'przepisy',
        'position' => 20,
    ]);

    $pageLead = ContentArticle::factory()->published()->create([
        'category_id' => $categoryB->id,
        'editorial_priority' => 500,
    ]);
    $wrong = ContentArticle::factory()->published()->create([
        'category_id' => $categoryB->id,
        'editorial_priority' => 100,
    ]);
    $correct = ContentArticle::factory()->published()->create([
        'category_id' => $categoryA->id,
        'editorial_priority' => 90,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $pageLead->id,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_CATEGORY_LEAD,
        'context_key' => $categoryA->slug,
        'article_id' => $wrong->id,
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    $block = collect($composition['categories'])
        ->first(fn (array $candidate): bool => $candidate['category']->id === $categoryA->id);

    expect($block)->not->toBeNull()
        ->and($block['lead']?->id)->toBe($correct->id);
});

test('missing unique candidates shorten later modules instead of repeating cards', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create();

    $only = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $only->id,
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 4,
        latestLimit: 4,
        categoryItemsLimit: 4,
        guidesItemsLimit: 4,
        importantNowLimit: 6,
    );

    expect($composition['lead']?->id)->toBe($only->id)
        ->and($composition['secondary'])->toBe([])
        ->and($composition['latest'])->toBe([])
        ->and($composition['categories'])->toBe([])
        ->and($composition['guides']['lead'])->toBeNull()
        ->and($composition['guides']['items'])->toBe([])
        ->and($composition['important_now'])->toBe([]);
});

test('breaking strip may intentionally repeat the lead article', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create();

    $breaking = ContentArticle::factory()->breaking()->create([
        'category_id' => $category->id,
        'editorial_priority' => 100,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $breaking->id,
    ]);

    $composition = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    expect($composition['lead']?->id)->toBe($breaking->id)
        ->and($composition['breaking']?->id)->toBe($breaking->id);
});


test('composer can expose deterministic fallback without manual placements for admin UI', function () {
    Carbon::setTestNow('2026-09-16 09:00:00');

    $category = ContentCategory::factory()->create();

    $manual = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'editorial_priority' => -50,
    ]);

    $fallback = ContentArticle::factory()->published()->featured()->create([
        'category_id' => $category->id,
        'editorial_priority' => 100,
    ]);

    ContentHomePlacement::factory()->create([
        'slot_key' => ContentHomePlacement::SLOT_LEAD,
        'article_id' => $manual->id,
    ]);

    $withManual = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
    );

    $withoutManual = newsroomHomeComposer()->compose(
        secondaryLimit: 0,
        latestLimit: 0,
        categoryItemsLimit: 0,
        guidesItemsLimit: 0,
        importantNowLimit: 0,
        includeManualPlacements: false,
    );

    expect($withManual['lead']?->id)->toBe($manual->id)
        ->and($withoutManual['lead']?->id)->toBe($fallback->id);
});
