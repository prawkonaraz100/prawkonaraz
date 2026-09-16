<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentAuthor;
use App\Models\ContentTag;
use App\Models\ContentTopic;
use App\Support\ContentArticlePublicCatalogService;
use App\Support\ContentArticlePublicResolution;
use App\Support\NewsroomRouteContract;
use Symfony\Component\HttpFoundation\Response;

test('public catalog resolves only publicly visible canonical articles in the requested route family', function () {
    $news = ContentArticle::factory()->published()->create(['slug' => 'publiczny-news']);
    $guide = ContentArticle::factory()->published()->guide()->create(['slug' => 'publiczny-poradnik']);
    $needsReview = ContentArticle::factory()->needsReview()->create(['slug' => 'do-weryfikacji']);
    $archived = ContentArticle::factory()->archived()->create(['slug' => 'historyczny-news']);
    $draft = ContentArticle::factory()->draft()->create(['slug' => 'draft-news']);
    $scheduled = ContentArticle::factory()->scheduled()->create(['slug' => 'zaplanowany-news']);
    $neverPublishedArchive = ContentArticle::factory()->create([
        'slug' => 'nigdy-nie-opublikowany',
        'workflow_status' => ContentArticleWorkflowStatus::Archived->value,
        'published_at' => null,
        'first_published_at' => null,
        'archived_at' => now()->subMinute(),
    ]);

    $catalog = app(ContentArticlePublicCatalogService::class);

    expect($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $news->slug)?->id)
        ->toBe($news->id)
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_GUIDES, $guide->slug)?->id)
        ->toBe($guide->id)
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $needsReview->slug)?->id)
        ->toBe($needsReview->id)
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $archived->slug)?->id)
        ->toBe($archived->id)
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_GUIDES, $news->slug))
        ->toBeNull()
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $draft->slug))
        ->toBeNull()
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $scheduled->slug))
        ->toBeNull()
        ->and($catalog->findPubliclyVisibleBySlug(NewsroomRouteContract::FAMILY_NEWSROOM, $neverPublishedArchive->slug))
        ->toBeNull();

    expect(fn () => $catalog->findPubliclyVisibleBySlug('unsupported', $news->slug))
        ->toThrow(InvalidArgumentException::class);
});

test('public catalog resolves canonical withdrawn tombstones explicitly as gone', function () {
    $visible = ContentArticle::factory()->published()->create(['slug' => 'widoczny']);
    $withdrawn = ContentArticle::factory()->withdrawn()->create(['slug' => 'wycofany']);
    $neverPublishedWithdrawn = ContentArticle::factory()->create([
        'slug' => 'nigdy-nie-publiczny-wycofany',
        'workflow_status' => ContentArticleWorkflowStatus::Withdrawn->value,
        'published_at' => null,
        'first_published_at' => null,
        'withdrawn_at' => now()->subMinute(),
        'withdrawal_reason' => 'Stan testowy bez historycznej publikacji.',
    ]);

    $catalog = app(ContentArticlePublicCatalogService::class);

    $visibleResolution = $catalog->resolveDetailBySlug(
        NewsroomRouteContract::FAMILY_NEWSROOM,
        $visible->slug,
    );
    $goneResolution = $catalog->resolveDetailBySlug(
        NewsroomRouteContract::FAMILY_NEWSROOM,
        $withdrawn->slug,
    );
    $hiddenResolution = $catalog->resolveDetailBySlug(
        NewsroomRouteContract::FAMILY_NEWSROOM,
        $neverPublishedWithdrawn->slug,
    );
    $missingResolution = $catalog->resolveDetailBySlug(
        NewsroomRouteContract::FAMILY_NEWSROOM,
        'brak-materialu',
    );

    expect($visibleResolution->status)->toBe(ContentArticlePublicResolution::STATUS_VISIBLE)
        ->and($visibleResolution->httpStatus())->toBe(Response::HTTP_OK)
        ->and($visibleResolution->article?->id)->toBe($visible->id)
        ->and($goneResolution->status)->toBe(ContentArticlePublicResolution::STATUS_GONE)
        ->and($goneResolution->isGone())->toBeTrue()
        ->and($goneResolution->httpStatus())->toBe(Response::HTTP_GONE)
        ->and($goneResolution->article?->id)->toBe($withdrawn->id)
        ->and($catalog->findPubliclyVisibleBySlug(
            NewsroomRouteContract::FAMILY_NEWSROOM,
            $withdrawn->slug,
        ))->toBeNull()
        ->and($hiddenResolution->isNotFound())->toBeTrue()
        ->and($hiddenResolution->httpStatus())->toBe(Response::HTTP_NOT_FOUND)
        ->and($missingResolution->isNotFound())->toBeTrue();

    expect(array_key_exists('withdrawal_reason', $goneResolution->article?->getAttributes() ?? []))
        ->toBeFalse();
});

test('actively distributed query is family scoped chronological and excludes non distributed states', function () {
    $older = ContentArticle::factory()->published()->create([
        'slug' => 'starszy',
        'published_at' => now()->subHours(3),
        'first_published_at' => now()->subHours(3),
    ]);
    $tieA = ContentArticle::factory()->published()->create([
        'slug' => 'remis-a',
        'published_at' => now()->subHours(2),
        'first_published_at' => now()->subHours(2),
    ]);
    $tieB = ContentArticle::factory()->published()->create([
        'slug' => 'remis-b',
        'published_at' => now()->subHours(2),
        'first_published_at' => now()->subHours(2),
    ]);
    $noindex = ContentArticle::factory()->published()->noindex()->create([
        'slug' => 'aktywny-noindex',
        'published_at' => now()->subHour(),
        'first_published_at' => now()->subHour(),
    ]);
    $guide = ContentArticle::factory()->published()->guide()->create(['slug' => 'poradnik']);
    $needsReview = ContentArticle::factory()->needsReview()->create(['slug' => 'needs-review']);
    $archived = ContentArticle::factory()->archived()->create(['slug' => 'archived']);
    $withdrawn = ContentArticle::factory()->withdrawn()->create(['slug' => 'withdrawn']);
    $scheduled = ContentArticle::factory()->scheduled()->create(['slug' => 'scheduled']);
    $draft = ContentArticle::factory()->draft()->create(['slug' => 'draft']);

    $catalog = app(ContentArticlePublicCatalogService::class);

    $newsIds = $catalog
        ->activelyDistributedQuery(NewsroomRouteContract::FAMILY_NEWSROOM)
        ->pluck('id')
        ->all();
    $guideIds = $catalog
        ->activelyDistributedQuery(NewsroomRouteContract::FAMILY_GUIDES)
        ->pluck('id')
        ->all();

    expect($newsIds)->toBe([
        $noindex->id,
        $tieB->id,
        $tieA->id,
        $older->id,
    ])->not->toContain(
        $guide->id,
        $needsReview->id,
        $archived->id,
        $withdrawn->id,
        $scheduled->id,
        $draft->id,
    );

    expect($guideIds)->toBe([$guide->id]);
});

test('public detail eager load policy exposes public relations without private source or article notes', function () {
    $author = ContentAuthor::factory()->published()->create();
    $reviewer = ContentAuthor::factory()->published()->create();
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'bezpieczne-relacje',
        'author_id' => $author->id,
        'reviewer_id' => $reviewer->id,
        'editorial_note' => 'Prywatna notatka redakcyjna.',
        'image_license_note' => 'Prywatna notatka licencyjna.',
    ]);
    $publicSource = ContentArticleSource::factory()->for($article, 'article')->create([
        'sort_order' => 10,
        'note' => 'Prywatna notatka do publicznego źródła.',
    ]);
    $privateSource = ContentArticleSource::factory()
        ->for($article, 'article')
        ->privateEvidence()
        ->create(['sort_order' => 20]);
    $tag = ContentTag::factory()->create();
    $publishedTopic = ContentTopic::factory()->published()->create();
    $draftTopic = ContentTopic::factory()->create();

    $article->tags()->attach($tag->id);
    $article->topics()->attach([$publishedTopic->id, $draftTopic->id]);

    $resolved = app(ContentArticlePublicCatalogService::class)
        ->findPubliclyVisibleBySlug(
            NewsroomRouteContract::FAMILY_NEWSROOM,
            $article->slug,
        );

    expect($resolved)->not->toBeNull()
        ->and($resolved?->relationLoaded('category'))->toBeTrue()
        ->and($resolved?->relationLoaded('author'))->toBeTrue()
        ->and($resolved?->relationLoaded('reviewer'))->toBeTrue()
        ->and($resolved?->relationLoaded('sources'))->toBeTrue()
        ->and($resolved?->relationLoaded('tags'))->toBeTrue()
        ->and($resolved?->relationLoaded('topics'))->toBeTrue()
        ->and($resolved?->sources->pluck('id')->all())->toBe([$publicSource->id])
        ->and($resolved?->tags->pluck('id')->all())->toBe([$tag->id])
        ->and($resolved?->topics->pluck('id')->all())->toBe([$publishedTopic->id])
        ->and($resolved?->sources->pluck('id')->all())->not->toContain($privateSource->id);

    expect(array_key_exists('editorial_note', $resolved?->getAttributes() ?? []))->toBeFalse()
        ->and(array_key_exists('image_license_note', $resolved?->getAttributes() ?? []))->toBeFalse()
        ->and(array_key_exists('note', $resolved?->sources->first()?->getAttributes() ?? []))->toBeFalse();
});
