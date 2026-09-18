<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentArticleRedirect;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class SeoSitemapAuditor
{
    public function __construct(
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @return list<string>
     */
    public function audit(): array
    {
        $errors = [];
        $robotsPath = public_path('robots.txt');
        $sitemapPath = public_path('sitemap.xml');

        if (! File::exists($robotsPath)) {
            $errors[] = 'Missing public/robots.txt.';
        } else {
            $robots = File::get($robotsPath);

            if (! str_contains($robots, 'Sitemap: https://prawkonaraz.pl/sitemap.xml')) {
                $errors[] = 'robots.txt does not point at https://prawkonaraz.pl/sitemap.xml.';
            }
        }

        if (! File::exists($sitemapPath)) {
            $errors[] = 'Missing public/sitemap.xml. Run php artisan seo:generate-sitemaps.';

            return $errors;
        }

        $allLocs = [];
        $newsLocs = [];
        $indexLocs = [];
        $articleFiles = [];
        $newsFiles = [];
        $articlesByUrl = $this->newsroomArticlesByCanonicalUrl();
        $redirectSourcePaths = ContentArticleRedirect::query()
            ->pluck('from_path')
            ->mapWithKeys(fn (string $path): array => ['/'.ltrim($path, '/') => true])
            ->all();

        $index = $this->loadXml($sitemapPath, $errors);

        if ($index === null) {
            return $errors;
        }

        $this->validateFileLimits($sitemapPath, 'sitemap.xml', $index, $errors);

        foreach ($index->children() as $sitemap) {
            $loc = trim((string) $sitemap->loc);
            $this->validateLoc($loc, 'sitemap index', $errors);

            if (isset($indexLocs[$loc])) {
                $errors[] = 'Duplicate sitemap index loc: '.$loc;
            } else {
                $indexLocs[$loc] = true;
            }

            $relativePath = $this->relativePathFromPublicUrl($loc);

            if ($relativePath === null) {
                $errors[] = 'Sitemap index loc is outside prawkonaraz.pl: '.$loc;

                continue;
            }

            $isArticleSitemap = $this->isArticleSitemapPath($relativePath);
            $isNewsSitemap = $this->isNewsSitemapPath($relativePath);

            if ($isArticleSitemap) {
                $articleFiles[$relativePath] = true;
            }

            if ($isNewsSitemap) {
                $newsFiles[$relativePath] = true;
            }

            $childPath = public_path($relativePath);

            if (! File::exists($childPath)) {
                $errors[] = 'Missing child sitemap file for '.$loc.' at public/'.$relativePath.'.';

                continue;
            }

            $child = $this->loadXml($childPath, $errors);

            if ($child === null) {
                continue;
            }

            $this->validateFileLimits($childPath, $relativePath, $child, $errors);

            if ($isNewsSitemap) {
                $this->validateNewsSitemapFile($relativePath, $child, $errors);
            }

            foreach ($child->children() as $entry) {
                if ($entry->getName() === 'sitemap') {
                    $childLoc = trim((string) $entry->loc);
                    $this->validateLoc($childLoc, $relativePath, $errors);

                    continue;
                }

                $url = trim((string) $entry->loc);
                $this->validateLoc($url, $relativePath, $errors);
                $isVideoSitemap = $relativePath === 'sitemaps/videos.xml';
                $isSupplementalSitemap = $isVideoSitemap || $isNewsSitemap;

                if (! $isSupplementalSitemap && isset($allLocs[$url])) {
                    $errors[] = 'Duplicate sitemap URL: '.$url;
                }

                if (! $isSupplementalSitemap) {
                    $allLocs[$url] = true;
                }

                if ($isNewsSitemap) {
                    if (isset($newsLocs[$url])) {
                        $errors[] = 'Duplicate News sitemap URL: '.$url;
                    } else {
                        $newsLocs[$url] = true;
                    }

                    $this->validateNewsEntry(
                        $entry,
                        $url,
                        $relativePath,
                        $articlesByUrl,
                        $redirectSourcePaths,
                        $errors,
                    );
                } elseif ($isArticleSitemap) {
                    $this->validateArticleEntry(
                        $url,
                        $relativePath,
                        $articlesByUrl,
                        $redirectSourcePaths,
                        $errors,
                    );
                }

                if ($isVideoSitemap) {
                    $this->validateVideoEntry($entry, $url, $errors);
                }
            }
        }

        $this->validateNewsroomSitemapTopology($articleFiles, 'articles', $errors);
        $this->validateNewsroomSitemapTopology($newsFiles, 'news', $errors);
        $this->validateObsoleteNewsroomSitemapFiles($articleFiles, $newsFiles, $errors);

        return $errors;
    }

    /**
     * @return array<string, ContentArticle>
     */
    protected function newsroomArticlesByCanonicalUrl(): array
    {
        return ContentArticle::query()
            ->with([
                'category:id,is_active',
                'author:id,is_published,published_at',
            ])
            ->get()
            ->mapWithKeys(function (ContentArticle $article): array {
                $type = $article->type instanceof ContentArticleType
                    ? $article->type->value
                    : (string) $article->type;

                try {
                    $path = NewsroomRouteContract::canonicalPath($type, (string) $article->slug);
                } catch (\InvalidArgumentException) {
                    return [];
                }

                return ['https://prawkonaraz.pl'.$path => $article];
            })
            ->all();
    }

    /**
     * @param  array<string, ContentArticle>  $articlesByUrl
     * @param  array<string, bool>  $redirectSourcePaths
     * @param  list<string>  $errors
     */
    protected function validateArticleEntry(
        string $url,
        string $source,
        array $articlesByUrl,
        array $redirectSourcePaths,
        array &$errors,
    ): void {
        $article = $articlesByUrl[$url] ?? null;

        if (! $article instanceof ContentArticle) {
            $errors[] = 'Article sitemap URL is not a current canonical newsroom article in '.$source.': '.$url;

            return;
        }

        if (! $article->isIndexable()) {
            $errors[] = 'Non-indexable newsroom article found in '.$source.': '.$url;
        }

        if (! $article->category?->is_active) {
            $errors[] = 'Newsroom article with inactive category found in '.$source.': '.$url;
        }

        if (! $article->author?->isPubliclyVisible()) {
            $errors[] = 'Newsroom article with non-public author found in '.$source.': '.$url;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if (isset($redirectSourcePaths['/'.ltrim($path, '/')])) {
            $errors[] = 'Redirect-source newsroom URL found in '.$source.': '.$url;
        }
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateNewsSitemapFile(
        string $source,
        \SimpleXMLElement $xml,
        array &$errors,
    ): void {
        $newsNamespace = 'http://www.google.com/schemas/sitemap-news/0.9';
        $namespaces = $xml->getDocNamespaces(true);

        if (! in_array($newsNamespace, $namespaces, true)) {
            $errors[] = 'Missing Google News namespace in '.$source.'.';
        }

        $maxEntries = max(
            1,
            min(
                SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES,
                (int) config('newsroom.news_sitemap_max_entries', SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES),
            ),
        );
        $entries = count($xml->children());

        if ($entries > $maxEntries) {
            $errors[] = sprintf(
                'News sitemap entry limit exceeded in %s: entries=%d limit=%d.',
                $source,
                $entries,
                $maxEntries,
            );
        }
    }

    /**
     * @param  array<string, ContentArticle>  $articlesByUrl
     * @param  array<string, bool>  $redirectSourcePaths
     * @param  list<string>  $errors
     */
    protected function validateNewsEntry(
        \SimpleXMLElement $entry,
        string $url,
        string $source,
        array $articlesByUrl,
        array $redirectSourcePaths,
        array &$errors,
    ): void {
        $this->validateArticleEntry($url, $source, $articlesByUrl, $redirectSourcePaths, $errors);

        $newsNamespace = 'http://www.google.com/schemas/sitemap-news/0.9';
        $namespaced = $entry->children($newsNamespace);

        if (! isset($namespaced->news)) {
            $errors[] = 'Missing news:news block in '.$source.': '.$url;

            return;
        }

        $news = $namespaced->news->children($newsNamespace);
        $publication = isset($news->publication)
            ? $news->publication->children($newsNamespace)
            : null;

        $name = $publication instanceof \SimpleXMLElement
            ? trim((string) $publication->name)
            : '';
        $language = $publication instanceof \SimpleXMLElement
            ? trim((string) $publication->language)
            : '';
        $publicationDate = trim((string) $news->publication_date);
        $title = trim((string) $news->title);

        foreach ([
            'news:publication/news:name' => $name,
            'news:publication/news:language' => $language,
            'news:publication_date' => $publicationDate,
            'news:title' => $title,
        ] as $tag => $value) {
            if ($value === '') {
                $errors[] = 'Missing '.$tag.' in '.$source.': '.$url;
            }
        }

        if ($name !== '' && $name !== $this->siteIdentitySchema->siteName()) {
            $errors[] = 'Unexpected news:name in '.$source.': '.$url;
        }

        if ($language !== '' && $language !== 'pl') {
            $errors[] = 'Unexpected news:language in '.$source.': '.$url;
        }

        $article = $articlesByUrl[$url] ?? null;

        if ($article instanceof ContentArticle) {
            $type = $article->type instanceof ContentArticleType
                ? $article->type
                : ContentArticleType::tryFrom((string) $article->type);

            if ($type !== ContentArticleType::News) {
                $errors[] = 'Non-news article found in News sitemap '.$source.': '.$url;
            }

            if (
                $article->workflow_status !== ContentArticleWorkflowStatus::Published
                || ! $article->isActivelyDistributed()
            ) {
                $errors[] = 'Non-active published article found in News sitemap '.$source.': '.$url;
            }

            if (
                $article->first_published_at === null
                || $article->first_published_at->lt(now()->subDays(SeoSitemapBuilder::NEWS_SITEMAP_WINDOW_DAYS))
            ) {
                $errors[] = 'Article older than Google News window found in '.$source.': '.$url;
            }

            if ($title !== '' && $title !== trim((string) $article->title)) {
                $errors[] = 'news:title does not match visible article title in '.$source.': '.$url;
            }
        }

        if ($publicationDate === '') {
            return;
        }

        try {
            $parsedPublicationDate = Carbon::parse($publicationDate);
        } catch (\Throwable) {
            $errors[] = 'Invalid news:publication_date in '.$source.': '.$url;

            return;
        }

        if ($parsedPublicationDate->lt(now()->subDays(SeoSitemapBuilder::NEWS_SITEMAP_WINDOW_DAYS))) {
            $errors[] = 'Old news:publication_date found in '.$source.': '.$url;
        }

        if (
            $article instanceof ContentArticle
            && $article->first_published_at !== null
            && ! $article->first_published_at->equalTo($parsedPublicationDate)
        ) {
            $errors[] = 'news:publication_date does not match first_published_at in '.$source.': '.$url;
        }
    }

    /**
     * @param  array<string, bool>  $files
     * @param  list<string>  $errors
     */
    protected function validateNewsroomSitemapTopology(
        array $files,
        string $kind,
        array &$errors,
    ): void {
        if ($files === []) {
            return;
        }

        $single = 'sitemaps/'.$kind.'.xml';
        $shardPattern = '#^sitemaps/'.preg_quote($kind, '#').'-(\d+)-(\d+)\.xml$#';
        $shards = [];

        foreach (array_keys($files) as $relativePath) {
            if (preg_match($shardPattern, $relativePath, $matches) !== 1) {
                continue;
            }

            $start = (int) $matches[1];
            $end = (int) $matches[2];

            if ($start < 1 || $end < $start) {
                $errors[] = 'Invalid '.$kind.' sitemap shard range: '.$relativePath;

                continue;
            }

            $shards[] = [
                'path' => $relativePath,
                'start' => $start,
                'end' => $end,
            ];
        }

        if (isset($files[$single]) && $shards !== []) {
            $errors[] = 'Sitemap index mixes single and sharded '.$kind.' sitemap topology.';
        }

        usort($shards, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $previous = null;

        foreach ($shards as $shard) {
            if ($previous !== null && $shard['start'] <= $previous['end']) {
                $errors[] = sprintf(
                    'Overlapping %s sitemap shard ranges: %s and %s.',
                    $kind,
                    $previous['path'],
                    $shard['path'],
                );
            }

            $previous = $shard;
        }
    }

    /**
     * @param  array<string, bool>  $articleFiles
     * @param  array<string, bool>  $newsFiles
     * @param  list<string>  $errors
     */
    protected function validateObsoleteNewsroomSitemapFiles(
        array $articleFiles,
        array $newsFiles,
        array &$errors,
    ): void {
        foreach (File::glob(public_path('sitemaps/articles*.xml')) ?: [] as $file) {
            $relativePath = 'sitemaps/'.basename($file);

            if ($this->isArticleSitemapPath($relativePath) && ! isset($articleFiles[$relativePath])) {
                $errors[] = 'Obsolete unreferenced article sitemap file: '.$relativePath;
            }
        }

        foreach (File::glob(public_path('sitemaps/news*.xml')) ?: [] as $file) {
            $relativePath = 'sitemaps/'.basename($file);

            if ($this->isNewsSitemapPath($relativePath) && ! isset($newsFiles[$relativePath])) {
                $errors[] = 'Obsolete unreferenced News sitemap file: '.$relativePath;
            }
        }
    }

    protected function isArticleSitemapPath(string $relativePath): bool
    {
        return $relativePath === ltrim(SeoSitemapBuilder::ARTICLES_SITEMAP_PATH, '/')
            || preg_match('#^sitemaps/articles-\d+-\d+\.xml$#', $relativePath) === 1;
    }

    protected function isNewsSitemapPath(string $relativePath): bool
    {
        return $relativePath === ltrim(SeoSitemapBuilder::NEWS_SITEMAP_PATH, '/')
            || preg_match('#^sitemaps/news-\d+-\d+\.xml$#', $relativePath) === 1;
    }

    /**
     * @param  list<string>  $errors
     */
    protected function loadXml(string $path, array &$errors): ?\SimpleXMLElement
    {
        $xml = @simplexml_load_file($path);

        if (! $xml instanceof \SimpleXMLElement) {
            $errors[] = 'Invalid XML: '.$path;

            return null;
        }

        return $xml;
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateLoc(string $loc, string $source, array &$errors): void
    {
        if ($loc === '') {
            $errors[] = 'Empty loc in '.$source.'.';

            return;
        }

        if ($loc !== 'https://prawkonaraz.pl' && ! str_starts_with($loc, 'https://prawkonaraz.pl/')) {
            $errors[] = 'Non-canonical loc in '.$source.': '.$loc;
        }

        foreach (['http://', 'prawkoapp.pl', 'prawkobit.pl', '/admin', '/api/', '/profile', '/nauka'] as $forbidden) {
            if (str_contains($loc, $forbidden)) {
                $errors[] = 'Forbidden loc pattern "'.$forbidden.'" in '.$source.': '.$loc;
            }
        }
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateFileLimits(
        string $path,
        string $source,
        \SimpleXMLElement $xml,
        array &$errors,
    ): void {
        $maxUrls = max(
            1,
            (int) config('seo.sitemap_max_urls_per_file', SeoSitemapGenerator::MAX_URLS_PER_FILE),
        );
        $maxBytes = max(
            1,
            (int) config('seo.sitemap_max_uncompressed_bytes', SeoSitemapGenerator::MAX_UNCOMPRESSED_BYTES),
        );
        $entries = count($xml->children());

        if ($entries > $maxUrls) {
            $errors[] = sprintf(
                'Sitemap entry limit exceeded in %s: entries=%d limit=%d.',
                $source,
                $entries,
                $maxUrls,
            );
        }

        $bytes = File::size($path);

        if ($bytes > $maxBytes) {
            $errors[] = sprintf(
                'Sitemap byte limit exceeded in %s: bytes=%d limit=%d.',
                $source,
                $bytes,
                $maxBytes,
            );
        }
    }

    /**
     * @param  list<string>  $errors
     */
    protected function validateVideoEntry(\SimpleXMLElement $entry, string $pageUrl, array &$errors): void
    {
        $videoNamespace = 'http://www.google.com/schemas/sitemap-video/1.1';
        $videos = $entry->children($videoNamespace);

        if (! isset($videos->video)) {
            $errors[] = 'Video sitemap entry has no video:video block: '.$pageUrl;

            return;
        }

        foreach ($videos->video as $video) {
            $thumbnail = trim((string) $video->thumbnail_loc);
            $title = trim((string) $video->title);
            $description = trim((string) $video->description);
            $content = trim((string) $video->content_loc);
            $duration = trim((string) $video->duration);

            foreach ([
                'video:thumbnail_loc' => $thumbnail,
                'video:title' => $title,
                'video:description' => $description,
                'video:content_loc' => $content,
            ] as $tag => $value) {
                if ($value === '') {
                    $errors[] = 'Missing '.$tag.' in video sitemap entry: '.$pageUrl;
                }
            }

            foreach ([
                'video:thumbnail_loc' => $thumbnail,
                'video:content_loc' => $content,
            ] as $tag => $url) {
                if ($url !== '' && ! str_starts_with($url, 'https://')) {
                    $errors[] = 'Non-HTTPS '.$tag.' in video sitemap entry: '.$url;
                }
            }

            if ($content !== '' && $content === $pageUrl) {
                $errors[] = 'video:content_loc must not equal page loc: '.$pageUrl;
            }

            if ($duration !== '' && (! ctype_digit($duration) || (int) $duration <= 0)) {
                $errors[] = 'Invalid video:duration in video sitemap entry: '.$pageUrl;
            }

            if (mb_strlen($description) > 2048) {
                $errors[] = 'video:description is longer than 2048 characters in video sitemap entry: '.$pageUrl;
            }

            if (preg_match('/^(tak|nie)(?:[\s,.!?]|$)/iu', $description) === 1) {
                $errors[] = 'video:description looks like an answer explanation instead of a neutral video description: '.$pageUrl;
            }
        }
    }

    protected function relativePathFromPublicUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'prawkonaraz.pl') {
            return null;
        }

        $path = ltrim((string) ($parts['path'] ?? ''), '/');

        return $path !== '' ? $path : null;
    }
}
