<?php

use App\Models\ContentArticle;
use App\Support\ContentArticleSeoService;
use App\Support\NewsroomMediaStorage;
use Illuminate\Support\Carbon;

test('article seo service builds canonical social metadata and substantive dates for newsroom article', function () {
    config()->set('content.organization.name', 'PrawkoNaRaz');

    $firstPublishedAt = Carbon::parse('2026-09-15 10:30:00', 'Europe/Warsaw');
    $lastSubstantiveUpdateAt = Carbon::parse('2026-09-16 18:45:00', 'Europe/Warsaw');
    $ogPath = 'newsroom/articles/source/'.str_repeat('0', 26).'.webp';
    $heroPath = 'newsroom/articles/source/'.str_repeat('1', 26).'.webp';

    $article = ContentArticle::factory()->published()->create([
        'title' => 'Tytuł redakcyjny',
        'slug' => 'nowe-zasady-egzaminu',
        'lead' => 'Lead redakcyjny.',
        'seo_title' => '<b>Nowe zasady egzaminu</b>',
        'seo_description' => '<p>Najważniejsze zmiany &amp; praktyczne informacje dla kandydatów.</p>',
        'robots' => null,
        'first_published_at' => $firstPublishedAt,
        'published_at' => $firstPublishedAt,
        'last_substantive_update_at' => $lastSubstantiveUpdateAt,
        'updated_at' => now()->addYear(),
        'hero_image_path' => $heroPath,
        'hero_image_alt' => 'Zdjęcie główne artykułu',
        'hero_image_width' => 1600,
        'hero_image_height' => 900,
        'og_image_path' => $ogPath,
        'og_image_alt' => 'Dedykowana grafika social',
        'og_image_width' => 1200,
        'og_image_height' => 630,
    ])->load('author');

    $meta = app(ContentArticleSeoService::class)->article($article);

    expect($meta)
        ->toMatchArray([
            'title' => 'Nowe zasady egzaminu - PrawkoNaRaz',
            'description' => 'Najważniejsze zmiany & praktyczne informacje dla kandydatów.',
            'canonical' => url('/aktualnosci/nowe-zasady-egzaminu'),
            'robots' => ContentArticleSeoService::DEFAULT_ROBOTS,
            'image_alt' => 'Dedykowana grafika social',
            'image_width' => 1200,
            'image_height' => 630,
            'preload_image' => app(NewsroomMediaStorage::class)->publicUrl($heroPath),
            'og_type' => 'article',
            'author_name' => $article->author->name,
            'published_time' => $firstPublishedAt->toIso8601String(),
            'modified_time' => $lastSubstantiveUpdateAt->toIso8601String(),
        ])
        ->and($meta['image'])->toBe(app(NewsroomMediaStorage::class)->publicUrl($ogPath))
        ->and($meta['modified_time'])->not->toBe($article->updated_at->toIso8601String());
});

test('guide seo canonical uses only the guide route family and does not duplicate brand', function () {
    config()->set('content.organization.name', 'PrawkoNaRaz');

    $article = ContentArticle::factory()->published()->guide()->create([
        'slug' => 'jak-przygotowac-sie-do-egzaminu',
        'seo_title' => 'Jak przygotować się do egzaminu - PrawkoNaRaz',
    ])->load('author');

    $meta = app(ContentArticleSeoService::class)->article($article);

    expect($meta['canonical'])->toBe(url('/poradniki/jak-przygotowac-sie-do-egzaminu'))
        ->and($meta['canonical'])->not->toContain('/aktualnosci/')
        ->and($meta['title'])->toBe('Jak przygotować się do egzaminu - PrawkoNaRaz');
});

test('article seo service falls back to headline lead hero and preserves explicit robots policy', function () {
    $heroPath = 'newsroom/articles/source/'.str_repeat('2', 26).'.jpg';
    $longLead = '<p>'.str_repeat('Bardzo ważna informacja dla kierowców. ', 8).'</p>';

    $article = ContentArticle::factory()->archived()->create([
        'title' => 'Historyczny materiał',
        'slug' => 'historyczny-material',
        'lead' => $longLead,
        'seo_title' => null,
        'seo_description' => null,
        'robots' => 'noindex,follow',
        'hero_image_path' => $heroPath,
        'hero_image_alt' => 'Historyczne zdjęcie',
        'hero_image_width' => 1400,
        'hero_image_height' => 788,
        'og_image_path' => null,
        'og_image_alt' => null,
        'og_image_width' => null,
        'og_image_height' => null,
        'last_substantive_update_at' => null,
    ])->load('author');

    $meta = app(ContentArticleSeoService::class)->article($article);

    expect($meta['title'])->toBe('Historyczny materiał - PrawkoNaRaz')
        ->and($meta['description'])->not->toContain('<p>')
        ->and(mb_strlen($meta['description']))->toBeLessThanOrEqual(160)
        ->and($meta['robots'])->toBe('noindex,follow')
        ->and($meta['image'])->toBe(app(NewsroomMediaStorage::class)->publicUrl($heroPath))
        ->and($meta['image_alt'])->toBe('Historyczne zdjęcie')
        ->and($meta['modified_time'])->toBe($article->first_published_at->toIso8601String());
});

test('article seo service refuses metadata for non public and withdrawn states', function () {
    $draft = ContentArticle::factory()->draft()->create();
    $scheduled = ContentArticle::factory()->scheduled()->create();
    $withdrawn = ContentArticle::factory()->withdrawn()->create();

    $service = app(ContentArticleSeoService::class);

    expect(fn () => $service->article($draft))->toThrow(DomainException::class)
        ->and(fn () => $service->article($scheduled))->toThrow(DomainException::class)
        ->and(fn () => $service->article($withdrawn))->toThrow(DomainException::class);
});

test('article seo canonical respects the trusted public request origin without cms override', function () {
    $article = ContentArticle::factory()->published()->create([
        'slug' => 'proxy-canonical',
    ]);

    $this
        ->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'seo.example.test',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_HOST' => 'seo.example.test',
            'REMOTE_ADDR' => '10.0.0.1',
        ])
        ->get('/aktualnosci');

    $canonical = app(ContentArticleSeoService::class)->canonicalUrl($article);

    expect($canonical)->toBe('https://seo.example.test/aktualnosci/proxy-canonical');
});
