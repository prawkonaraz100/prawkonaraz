<?php

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\SEO\Schema\SchemaIds;
use App\Support\ContentArticleSchemaService;
use App\Support\ContentArticleSeoService;
use App\Support\NewsroomMediaStorage;
use Illuminate\Support\Carbon;

function newsroomSchemaNode(array $graph, string $id): array
{
    return collect($graph['@graph'])
        ->firstWhere('@id', $id) ?? [];
}

test('newsroom article schema builds one stable graph aligned with SEO canonical and dates', function () {
    $publishedAt = Carbon::parse('2026-09-15 10:30:00', 'Europe/Warsaw');
    $modifiedAt = Carbon::parse('2026-09-16 18:45:00', 'Europe/Warsaw');

    $article = ContentArticle::factory()->published()->create([
        'type' => ContentArticleType::News,
        'title' => 'Nowe zasady egzaminu',
        'slug' => 'nowe-zasady-egzaminu',
        'seo_description' => 'Opis dla wyszukiwarki.',
        'first_published_at' => $publishedAt,
        'published_at' => $publishedAt,
        'last_substantive_update_at' => $modifiedAt,
        'updated_at' => now()->addYear(),
    ])->load(['author', 'category']);

    $schema = app(ContentArticleSchemaService::class)->article($article);
    $canonical = url('/aktualnosci/nowe-zasady-egzaminu');
    $ids = app(SchemaIds::class);
    $articleId = $ids->contentArticle($canonical);
    $webPageId = $ids->contentArticleWebPage($canonical);
    $authorId = $ids->contentAuthorPerson($article->author);

    $articleNode = newsroomSchemaNode($schema, $articleId);
    $webPageNode = newsroomSchemaNode($schema, $webPageId);
    $authorNode = newsroomSchemaNode($schema, $authorId);

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($articleNode['@type'])->toBe('NewsArticle')
        ->and($articleNode['url'])->toBe($canonical)
        ->and($articleNode['headline'])->toBe('Nowe zasady egzaminu')
        ->and($articleNode['datePublished'])->toBe($publishedAt->toIso8601String())
        ->and($articleNode['dateModified'])->toBe($modifiedAt->toIso8601String())
        ->and($articleNode['dateModified'])->not->toBe($article->updated_at->toIso8601String())
        ->and($articleNode['mainEntityOfPage'])->toBe(['@id' => $webPageId])
        ->and($articleNode['author'])->toBe(['@id' => $authorId])
        ->and($articleNode['publisher'])->toBe(['@id' => $ids->organization()])
        ->and($articleNode['publishingPrinciples'])->toBe(route('about.editorial-principles'))
        ->and($articleNode['isPartOf'])->toBe(['@id' => $ids->website()])
        ->and($articleNode['articleSection'])->toBe($article->category->name)
        ->and($webPageNode['mainEntity'])->toBe(['@id' => $articleId])
        ->and($webPageNode['breadcrumb'])->toBe(['@id' => $ids->contentArticleBreadcrumb($canonical)])
        ->and($authorNode['@type'])->toBe('Person')
        ->and($authorNode['url'])->toBe(route('content-authors.show', $article->author->slug))
        ->and($authorNode['worksFor'])->toBe(['@id' => $ids->organization()])
        ->and(collect($schema['@graph'])->where('@id', $ids->organization()))->toHaveCount(1)
        ->and(collect($schema['@graph'])->where('@id', $ids->website()))->toHaveCount(1)
        ->and(collect($schema['@graph'])->where('@id', $authorId))->toHaveCount(1);
});

test('guide schema uses Article and guide breadcrumb without category hop', function () {
    $article = ContentArticle::factory()->published()->guide()->create([
        'title' => 'Jak przygotować się do egzaminu',
        'slug' => 'jak-przygotowac-sie-do-egzaminu',
    ])->load(['author', 'category']);

    $schema = app(ContentArticleSchemaService::class)->article($article);
    $canonical = url('/poradniki/jak-przygotowac-sie-do-egzaminu');
    $ids = app(SchemaIds::class);
    $articleNode = newsroomSchemaNode($schema, $ids->contentArticle($canonical));
    $breadcrumb = newsroomSchemaNode($schema, $ids->contentArticleBreadcrumb($canonical));
    $items = $breadcrumb['itemListElement'];

    expect($articleNode['@type'])->toBe('Article')
        ->and($articleNode['articleSection'])->toBe($article->category->name)
        ->and(array_column($items, 'name'))->toBe([
            'Strona główna',
            'Poradniki',
            'Jak przygotować się do egzaminu',
        ])
        ->and(array_column($items, 'item'))->toBe([
            route('home'),
            route('public.guides'),
            $canonical,
        ]);
});

test('newsroom article breadcrumb includes the primary category', function () {
    $category = ContentCategory::factory()->create([
        'name' => 'Przepisy',
        'slug' => 'przepisy',
    ]);
    $article = ContentArticle::factory()->published()->create([
        'category_id' => $category->id,
        'title' => 'Zmiana przepisów',
        'slug' => 'zmiana-przepisow',
    ])->load(['author', 'category']);

    $canonical = url('/aktualnosci/zmiana-przepisow');
    $ids = app(SchemaIds::class);
    $breadcrumb = newsroomSchemaNode(
        app(ContentArticleSchemaService::class)->article($article),
        $ids->contentArticleBreadcrumb($canonical),
    );

    expect(array_column($breadcrumb['itemListElement'], 'name'))->toBe([
        'Strona główna',
        'Aktualności',
        'Przepisy',
        'Zmiana przepisów',
    ])
        ->and($breadcrumb['itemListElement'][2]['item'])
        ->toBe(route('public.news.categories.show', 'przepisy'));
});

test('schema exposes only real hero and og image objects and deduplicates the same asset', function () {
    $heroPath = 'newsroom/articles/source/'.str_repeat('1', 26).'.webp';
    $ogPath = 'newsroom/articles/source/'.str_repeat('2', 26).'.webp';
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'obrazy-schema',
        'hero_image_path' => $heroPath,
        'hero_image_alt' => 'Hero artykułu',
        'hero_image_width' => 1600,
        'hero_image_height' => 900,
        'hero_image_caption' => 'Podpis hero',
        'og_image_path' => $ogPath,
        'og_image_alt' => 'Grafika społecznościowa',
        'og_image_width' => 1200,
        'og_image_height' => 630,
        'image_credit' => 'PrawkoNaRaz',
    ])->load(['author', 'category']);

    $schema = app(ContentArticleSchemaService::class)->article($article);
    $canonical = url('/aktualnosci/obrazy-schema');
    $ids = app(SchemaIds::class);
    $articleNode = newsroomSchemaNode($schema, $ids->contentArticle($canonical));
    $webPageNode = newsroomSchemaNode($schema, $ids->contentArticleWebPage($canonical));
    $heroNode = newsroomSchemaNode($schema, $ids->contentArticleHeroImage($canonical));
    $ogNode = newsroomSchemaNode($schema, $ids->contentArticleOgImage($canonical));

    expect($heroNode['url'])->toBe(app(NewsroomMediaStorage::class)->publicUrl($heroPath))
        ->and($heroNode['width'])->toBe(1600)
        ->and($heroNode['height'])->toBe(900)
        ->and($heroNode['caption'])->toBe('Podpis hero')
        ->and($ogNode['url'])->toBe(app(NewsroomMediaStorage::class)->publicUrl($ogPath))
        ->and($articleNode['image'])->toBe([
            ['@id' => $ids->contentArticleHeroImage($canonical)],
            ['@id' => $ids->contentArticleOgImage($canonical)],
        ])
        ->and($webPageNode['primaryImageOfPage'])
        ->toBe(['@id' => $ids->contentArticleHeroImage($canonical)]);

    $article->forceFill([
        'og_image_path' => $heroPath,
        'og_image_alt' => 'Ten sam asset',
        'og_image_width' => 1600,
        'og_image_height' => 900,
    ]);

    $deduplicated = app(ContentArticleSchemaService::class)->article($article);
    $deduplicatedArticle = newsroomSchemaNode($deduplicated, $ids->contentArticle($canonical));

    expect($deduplicatedArticle['image'])->toBe([
        ['@id' => $ids->contentArticleHeroImage($canonical)],
    ])
        ->and(newsroomSchemaNode($deduplicated, $ids->contentArticleOgImage($canonical)))->toBe([]);
});

test('explainer analysis report and guide use Article rather than NewsArticle', function (ContentArticleType $type) {
    $article = ContentArticle::factory()->published()->create([
        'type' => $type,
        'slug' => 'typ-'.$type->value,
    ])->load(['author', 'category']);

    $canonical = app(ContentArticleSeoService::class)->canonicalUrl($article);
    $node = newsroomSchemaNode(
        app(ContentArticleSchemaService::class)->article($article),
        app(SchemaIds::class)->contentArticle($canonical),
    );

    expect($node['@type'])->toBe('Article');
})->with([
    ContentArticleType::Explainer,
    ContentArticleType::Analysis,
    ContentArticleType::Report,
    ContentArticleType::Guide,
]);

test('schema refuses hidden articles unpublished authors and inactive categories', function () {
    $service = app(ContentArticleSchemaService::class);

    expect(fn () => $service->article(ContentArticle::factory()->draft()->create()))
        ->toThrow(DomainException::class);

    $unpublishedAuthor = ContentAuthor::factory()->create();
    $articleWithHiddenAuthor = ContentArticle::factory()->published()->create([
        'author_id' => $unpublishedAuthor->id,
    ]);

    expect(fn () => $service->article($articleWithHiddenAuthor))
        ->toThrow(DomainException::class);

    $inactiveCategory = ContentCategory::factory()->inactive()->create();
    $articleWithInactiveCategory = ContentArticle::factory()->published()->create([
        'category_id' => $inactiveCategory->id,
    ]);

    expect(fn () => $service->article($articleWithInactiveCategory))
        ->toThrow(DomainException::class);
});
