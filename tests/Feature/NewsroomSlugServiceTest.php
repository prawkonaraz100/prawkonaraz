<?php

use App\Enums\ContentArticleType;
use App\Models\AuditLog;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\ContentCategory;
use App\Models\User;
use App\Support\ContentArticlePathResolver;
use App\Support\ContentArticleSlugService;
use App\Support\NewsroomRouteContract;

function newsroomSlugService(): ContentArticleSlugService
{
    return app(ContentArticleSlugService::class);
}

test('slug service generates deterministic initial slug and allocates a unique suffix', function () {
    $category = ContentCategory::factory()->create();
    $service = newsroomSlugService();

    $first = $service->create([
        'type' => ContentArticleType::News->value,
        'category_id' => $category->id,
        'title' => 'Nowe zasady egzaminu',
    ]);

    $second = $service->create([
        'type' => ContentArticleType::News->value,
        'category_id' => $category->id,
        'title' => 'Nowe zasady egzaminu',
    ]);

    expect($first->slug)->toBe('nowe-zasady-egzaminu')
        ->and($second->slug)->toBe('nowe-zasady-egzaminu-2');
});

test('explicit duplicate current slug is rejected', function () {
    $category = ContentCategory::factory()->create();
    $service = newsroomSlugService();

    $service->create([
        'type' => ContentArticleType::News->value,
        'category_id' => $category->id,
        'title' => 'Pierwszy artykuł',
        'slug' => 'ten-sam-slug',
    ]);

    expect(fn () => $service->create([
        'type' => ContentArticleType::Guide->value,
        'category_id' => $category->id,
        'title' => 'Drugi artykuł',
        'slug' => 'ten-sam-slug',
    ]))->toThrow(DomainException::class);
});

test('draft slug change does not create redirect history', function () {
    $article = ContentArticle::factory()->draft()->create([
        'slug' => 'wersja-robocza',
    ]);

    $updated = newsroomSlugService()->changeSlug($article, 'nowa wersja robocza');

    expect($updated->slug)->toBe('nowa-wersja-robocza')
        ->and(ContentArticleRedirect::query()->count())->toBe(0);
});

test('published slug change creates direct redirect and later changes rewrite history one hop', function () {
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'pierwszy-adres',
    ]);
    $service = newsroomSlugService();

    $service->changeSlug($article, 'drugi-adres');

    expect(ContentArticleRedirect::query()->where('from_path', '/aktualnosci/pierwszy-adres')->first())
        ->not->toBeNull()
        ->and(ContentArticleRedirect::query()->where('from_path', '/aktualnosci/pierwszy-adres')->value('to_path'))
        ->toBe('/aktualnosci/drugi-adres');

    $service->changeSlug($article->fresh(), 'trzeci-adres');

    $redirects = ContentArticleRedirect::query()
        ->where('article_id', $article->id)
        ->orderBy('from_path')
        ->pluck('to_path', 'from_path')
        ->all();

    expect($redirects)->toBe([
        '/aktualnosci/drugi-adres' => '/aktualnosci/trzeci-adres',
        '/aktualnosci/pierwszy-adres' => '/aktualnosci/trzeci-adres',
    ]);
});

test('published article can reclaim its own historical path and redirects remain one hop', function () {
    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'adres-a',
    ]);
    $service = newsroomSlugService();

    $service->changeSlug($article, 'adres-b');
    $service->changeSlug($article->fresh(), 'adres-c');
    $updated = $service->changeSlug($article->fresh(), 'adres-a');

    $redirects = ContentArticleRedirect::query()
        ->where('article_id', $article->id)
        ->orderBy('from_path')
        ->pluck('to_path', 'from_path')
        ->all();

    expect($updated->slug)->toBe('adres-a')
        ->and($redirects)->toBe([
            '/aktualnosci/adres-b' => '/aktualnosci/adres-a',
            '/aktualnosci/adres-c' => '/aktualnosci/adres-a',
        ])
        ->and(ContentArticleRedirect::query()->where('from_path', '/aktualnosci/adres-a')->exists())
        ->toBeFalse();
});

test('historical full path is reserved against another article but not against a different route family path', function () {
    $category = ContentCategory::factory()->create();
    $published = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'historyczny-adres',
    ]);
    $service = newsroomSlugService();

    $service->changeSlug($published, 'aktualny-adres');

    expect(fn () => $service->create([
        'type' => ContentArticleType::News->value,
        'category_id' => $category->id,
        'title' => 'Kolizja historii',
        'slug' => 'historyczny-adres',
    ]))->toThrow(DomainException::class);

    $guide = $service->create([
        'type' => ContentArticleType::Guide->value,
        'category_id' => $category->id,
        'title' => 'Dozwolona rodzina',
        'slug' => 'historyczny-adres',
    ]);

    expect($guide->slug)->toBe('historyczny-adres')
        ->and(NewsroomRouteContract::canonicalPath($guide->type->value, $guide->slug))
        ->toBe('/poradniki/historyczny-adres');
});

test('route family transition is allowed before publication blocked across families after publication and allowed within family', function () {
    $service = newsroomSlugService();

    $draftGuide = ContentArticle::factory()->draft()->guide()->create([
        'slug' => 'zmiana-rodziny',
    ]);
    $draftNews = $service->changeType($draftGuide, ContentArticleType::News);

    expect($draftNews->type)->toBe(ContentArticleType::News);

    $publishedGuide = ContentArticle::factory()->published()->guide()->create([
        'slug' => 'opublikowany-poradnik',
    ]);

    expect(fn () => $service->changeType($publishedGuide, ContentArticleType::News))
        ->toThrow(DomainException::class);

    $publishedNews = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'opublikowany-news',
    ]);
    $analysis = $service->changeType($publishedNews, ContentArticleType::Analysis);

    expect($analysis->type)->toBe(ContentArticleType::Analysis)
        ->and(NewsroomRouteContract::canonicalPath($analysis->type->value, $analysis->slug))
        ->toBe('/aktualnosci/opublikowany-news');
});

test('public path resolver never resolves the same article under both route families', function () {
    $news = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News->value,
        'slug' => 'tylko-aktualnosci',
    ]);
    $guide = ContentArticle::factory()->published()->guide()->create([
        'slug' => 'tylko-poradniki',
    ]);
    $resolver = app(ContentArticlePathResolver::class);

    expect($resolver->findPublicCanonical(NewsroomRouteContract::FAMILY_NEWSROOM, $news->slug)?->id)
        ->toBe($news->id)
        ->and($resolver->findPublicCanonical(NewsroomRouteContract::FAMILY_GUIDES, $news->slug))
        ->toBeNull()
        ->and($resolver->findPublicCanonical(NewsroomRouteContract::FAMILY_GUIDES, $guide->slug)?->id)
        ->toBe($guide->id)
        ->and($resolver->findPublicCanonical(NewsroomRouteContract::FAMILY_NEWSROOM, $guide->slug))
        ->toBeNull();
});

test('reserved newsroom slug is rejected while the same segment remains valid for guides', function () {
    $category = ContentCategory::factory()->create();
    $service = newsroomSlugService();

    expect(fn () => $service->create([
        'type' => ContentArticleType::News->value,
        'category_id' => $category->id,
        'title' => 'Reserved',
        'slug' => 'kategoria',
    ]))->toThrow(InvalidArgumentException::class);

    $guide = $service->create([
        'type' => ContentArticleType::Guide->value,
        'category_id' => $category->id,
        'title' => 'Guide reserved segment',
        'slug' => 'kategoria',
    ]);

    expect($guide->slug)->toBe('kategoria');
});

test('slug change audit stores compact path metadata and actor', function () {
    $actor = User::factory()->create();
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'stary-audyt',
    ]);

    newsroomSlugService()->changeSlug($article, 'nowy-audyt', $actor);

    $audit = AuditLog::query()
        ->where('action', 'content_article.slug_changed')
        ->where('entity_id', (string) $article->id)
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_user_id)->toBe($actor->id)
        ->and($audit->metadata['old_slug'])->toBe('stary-audyt')
        ->and($audit->metadata['new_slug'])->toBe('nowy-audyt')
        ->and($audit->metadata['from_path'])->toBe('/aktualnosci/stary-audyt')
        ->and($audit->metadata['to_path'])->toBe('/aktualnosci/nowy-audyt')
        ->and($audit->metadata)->not->toHaveKey('body_blocks');
});
