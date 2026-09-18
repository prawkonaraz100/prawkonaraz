<?php

use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Support\SeoSitemapAuditor;
use App\Support\SeoSitemapBuilder;
use App\Support\SeoSitemapGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config()->set('app.url', 'https://prawkonaraz.pl');
    config()->set('newsroom.public_enabled', true);
    config()->set('newsroom.article_sitemap_shard_id_span', 10000);
    config()->set('newsroom.news_sitemap_max_entries', SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES);
    config()->set('seo.sitemap_max_urls_per_file', SeoSitemapGenerator::MAX_URLS_PER_FILE);
    config()->set('seo.sitemap_max_uncompressed_bytes', SeoSitemapGenerator::MAX_UNCOMPRESSED_BYTES);
    config()->set('content.organization.name', 'PrawkoNaRaz');

    URL::forceRootUrl('https://prawkonaraz.pl');
    URL::forceScheme('https');

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

afterEach(function (): void {
    Carbon::setTestNow();
    URL::forceRootUrl(null);
    URL::forceScheme(null);

    File::delete(public_path('sitemap.xml'));

    foreach (File::glob(public_path('sitemaps/*.xml')) ?: [] as $file) {
        File::delete($file);
    }
});

/**
 * @return array{article:ContentArticle,category:ContentCategory,author:ContentAuthor}
 */
function newsroomN5006FreshNews(array $articleOverrides = []): array
{
    $category = ContentCategory::factory()->create();
    $author = ContentAuthor::factory()->published()->create();
    $article = ContentArticle::factory()->published()->create(array_replace([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'first_published_at' => now()->subHour(),
        'published_at' => now()->subHour(),
    ], $articleOverrides));

    return compact('article', 'category', 'author');
}

test('N5-006 sitemap auditor accepts valid newsroom article and News sitemap output', function () {
    newsroomN5006FreshNews([
        'title' => 'Poprawny newsroom audit',
        'slug' => 'poprawny-newsroom-audit',
    ]);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    expect(app(SeoSitemapAuditor::class)->audit())->toBe([]);
});

test('N5-006 sitemap auditor detects duplicate index loc and missing child file', function () {
    newsroomN5006FreshNews([
        'slug' => 'duplicate-index-audit',
    ]);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    $indexPath = public_path('sitemap.xml');
    $index = File::get($indexPath);

    preg_match(
        '#<sitemap>\s*<loc>https://prawkonaraz\.pl/sitemaps/articles\.xml</loc>.*?</sitemap>#s',
        $index,
        $match,
    );

    expect($match[0] ?? null)->not->toBeNull();

    File::put(
        $indexPath,
        str_replace('</sitemapindex>', ($match[0] ?? '').'</sitemapindex>', $index),
    );
    File::delete(public_path('sitemaps/articles.xml'));

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)
        ->toContain('Duplicate sitemap index loc: https://prawkonaraz.pl/sitemaps/articles.xml')
        ->toContain('Missing child sitemap file for https://prawkonaraz.pl/sitemaps/articles.xml at public/sitemaps/articles.xml.');
});

test('N5-006 sitemap auditor detects missing Google News namespace', function () {
    newsroomN5006FreshNews([
        'slug' => 'news-namespace-audit',
    ]);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    $path = public_path('sitemaps/news.xml');
    $xml = File::get($path);
    File::put(
        $path,
        str_replace(
            'http://www.google.com/schemas/sitemap-news/0.9',
            'https://invalid.example/news-sitemap',
            $xml,
        ),
    );

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)->toContain('Missing Google News namespace in sitemaps/news.xml.');
});

test('N5-006 sitemap auditor detects missing News tags and duplicate News loc', function () {
    newsroomN5006FreshNews([
        'slug' => 'news-tags-audit',
    ]);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    $path = public_path('sitemaps/news.xml');
    $xml = File::get($path);

    preg_match('#<url>.*?</url>#s', $xml, $urlMatch);
    expect($urlMatch[0] ?? null)->not->toBeNull();

    $entry = preg_replace('#<news:title>.*?</news:title>#s', '', (string) ($urlMatch[0] ?? ''));
    expect($entry)->not->toBeNull();

    $mutated = str_replace((string) ($urlMatch[0] ?? ''), $entry.$entry, $xml);
    File::put($path, $mutated);

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)
        ->toContain('Missing news:title in sitemaps/news.xml: https://prawkonaraz.pl/aktualnosci/news-tags-audit')
        ->toContain('Duplicate News sitemap URL: https://prawkonaraz.pl/aktualnosci/news-tags-audit');
});

test('N5-006 sitemap auditor detects News entry limit and old article state', function () {
    Carbon::setTestNow('2026-09-18 12:00:00');

    $first = newsroomN5006FreshNews(['slug' => 'news-limit-audit-1'])['article'];
    newsroomN5006FreshNews(['slug' => 'news-limit-audit-2']);
    newsroomN5006FreshNews(['slug' => 'news-limit-audit-3']);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    config()->set('newsroom.news_sitemap_max_entries', 2);
    $first->update([
        'first_published_at' => now()->subDays(3),
    ]);

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)
        ->toContain('News sitemap entry limit exceeded in sitemaps/news.xml: entries=3 limit=2.')
        ->toContain('Article older than Google News window found in sitemaps/news.xml: https://prawkonaraz.pl/aktualnosci/news-limit-audit-1');
});

test('N5-006 sitemap auditor detects stale draft noindex and redirect-source article entries', function () {
    $draft = newsroomN5006FreshNews(['slug' => 'stale-draft-audit'])['article'];
    $noindex = newsroomN5006FreshNews(['slug' => 'stale-noindex-audit'])['article'];
    $redirect = newsroomN5006FreshNews(['slug' => 'stale-redirect-audit'])['article'];

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    $draft->update([
        'workflow_status' => ContentArticleWorkflowStatus::Draft->value,
    ]);
    $noindex->update([
        'robots' => 'noindex,follow',
    ]);
    ContentArticleRedirect::query()->create([
        'article_id' => $redirect->id,
        'from_path' => '/aktualnosci/stale-redirect-audit',
        'to_path' => '/aktualnosci/stale-noindex-audit',
        'http_status' => 301,
    ]);

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)
        ->toContain('Non-indexable newsroom article found in sitemaps/articles.xml: https://prawkonaraz.pl/aktualnosci/stale-draft-audit')
        ->toContain('Non-indexable newsroom article found in sitemaps/articles.xml: https://prawkonaraz.pl/aktualnosci/stale-noindex-audit')
        ->toContain('Redirect-source newsroom URL found in sitemaps/articles.xml: https://prawkonaraz.pl/aktualnosci/stale-redirect-audit');
});

test('N5-006 sitemap auditor detects mixed topology and obsolete unreferenced newsroom shard', function () {
    newsroomN5006FreshNews([
        'slug' => 'topology-audit',
    ]);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    File::copy(
        public_path('sitemaps/articles.xml'),
        public_path('sitemaps/articles-000001-000002.xml'),
    );
    File::copy(
        public_path('sitemaps/news.xml'),
        public_path('sitemaps/news-000001-000002.xml'),
    );

    $indexPath = public_path('sitemap.xml');
    $index = File::get($indexPath);
    $extra = '<sitemap><loc>https://prawkonaraz.pl/sitemaps/articles-000001-000002.xml</loc></sitemap>';
    File::put($indexPath, str_replace('</sitemapindex>', $extra.'</sitemapindex>', $index));

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)
        ->toContain('Sitemap index mixes single and sharded articles sitemap topology.')
        ->toContain('Obsolete unreferenced News sitemap file: sitemaps/news-000001-000002.xml');
});

test('N5-006 sitemap auditor applies existing generic article shard count guard', function () {
    newsroomN5006FreshNews(['slug' => 'article-limit-audit-1']);
    newsroomN5006FreshNews(['slug' => 'article-limit-audit-2']);

    $this->artisan('seo:generate-sitemaps')->assertSuccessful();

    config()->set('seo.sitemap_max_urls_per_file', 1);

    $errors = app(SeoSitemapAuditor::class)->audit();

    expect($errors)->toContain(
        'Sitemap entry limit exceeded in sitemaps/articles.xml: entries=2 limit=1.',
    );
});
