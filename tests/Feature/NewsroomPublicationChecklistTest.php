<?php

use App\Filament\Resources\ContentArticles\Pages\EditContentArticle;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use App\Models\ContentCategory;
use App\Models\User;
use App\Support\ContentArticlePublicationChecklist;
use App\Support\ContentArticlePublishingService;
use App\Support\NewsroomArticleMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('publication checklist exposes precise blocking items for an incomplete article', function () {
    $article = ContentArticle::factory()->draft()->create([
        'title' => '',
        'slug' => '',
        'lead' => '',
        'author_id' => null,
        'body_blocks' => [],
    ]);

    $items = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->keyBy('key');

    expect($items['title']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['slug']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['author']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['lead']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['body']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['sources']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['review']['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($items['title']['message'])->toContain('title is required')
        ->and($items['sources']['message'])->toContain('requires at least one source');
});

test('publication warnings do not block a valid reviewed article from publishing', function () {
    $article = ContentArticle::factory()->inReview()->create([
        'hero_image_path' => null,
        'seo_description' => null,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $service = app(ContentArticlePublishingService::class);
    $reviewed = $service->markReviewed($article);

    $items = collect(app(ContentArticlePublicationChecklist::class)->items($reviewed));
    $blocking = $items->where('state', ContentArticlePublicationChecklist::STATE_BLOCKING);
    $warnings = $items->where('state', ContentArticlePublicationChecklist::STATE_WARNING);

    expect($blocking)->toBeEmpty()
        ->and($warnings->pluck('key')->all())->toContain('hero_missing', 'seo_description_missing', 'related_questions_missing');

    expect($service->publish($reviewed)->workflow_status->value)->toBe('published');
});

test('publication checklist and publishing service share the same active category blocker', function () {
    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    $article = ContentArticle::factory()->inReview()->create([
        'category_id' => $inactiveCategory->id,
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $categoryItem = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->firstWhere('key', 'category');

    expect($categoryItem['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($categoryItem['message'])->toContain('active category');

    $service = app(ContentArticlePublishingService::class);

    expect(fn () => $service->markReviewed($article))
        ->toThrow(DomainException::class, 'active category');

    expect(fn () => $service->publish($article))
        ->toThrow(DomainException::class, 'active category');
});

test('dedicated og asset without alt remains a blocking domain requirement', function () {
    Storage::fake('public');
    config()->set('media.public_disk', 'public');
    config()->set('media.newsroom_disk', 'public');
    config()->set('media.newsroom_prefix', 'newsroom/articles');
    config()->set('media.public_base_url', 'https://cdn.example.test/media');

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQMcAAAAASUVORK5CYII=', true);

    $heroFile = tempnam(sys_get_temp_dir(), 'newsroom-hero-');
    file_put_contents($heroFile, $png);
    $hero = app(NewsroomArticleMediaService::class)->store(
        new UploadedFile($heroFile, 'hero.png', 'image/png', null, true),
    );

    $ogFile = tempnam(sys_get_temp_dir(), 'newsroom-og-');
    file_put_contents($ogFile, $png);
    $og = app(NewsroomArticleMediaService::class)->store(
        new UploadedFile($ogFile, 'og.png', 'image/png', null, true),
    );

    $article = ContentArticle::factory()->inReview()->create([
        'hero_image_path' => $hero['path'],
        'hero_image_alt' => 'Opis hero',
        'hero_image_width' => $hero['width'],
        'hero_image_height' => $hero['height'],
        'og_image_path' => $og['path'],
        'og_image_alt' => null,
        'og_image_width' => $og['width'],
        'og_image_height' => $og['height'],
    ]);

    ContentArticleSource::factory()
        ->for($article, 'article')
        ->create();

    $ogItem = collect(app(ContentArticlePublicationChecklist::class)->items($article))
        ->firstWhere('key', 'og');

    expect($ogItem['state'])->toBe(ContentArticlePublicationChecklist::STATE_BLOCKING)
        ->and($ogItem['message'])->toContain('requires its own alt');

    expect(fn () => app(ContentArticlePublishingService::class)->markReviewed($article))
        ->toThrow(DomainException::class, 'requires its own alt');
});

test('article edit form renders the computed publication checklist', function () {
    $admin = User::factory()->admin()->create();
    $article = ContentArticle::factory()->draft()->create([
        'author_id' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditContentArticle::class, ['record' => $article->getRouteKey()])
        ->assertSee('Checklista publikacyjna')
        ->assertSee('Gotowość do publikacji')
        ->assertSee('BLOKUJE')
        ->assertSee('OSTRZEŻENIE')
        ->assertSee('Content article author is required.')
        ->assertSee('News article requires at least one source.');
});
