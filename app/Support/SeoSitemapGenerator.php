<?php

namespace App\Support;

use App\Models\QuestionSeoTopic;
use Illuminate\Support\Facades\File;

class SeoSitemapGenerator
{
    public const MAX_URLS_PER_FILE = 50000;

    public const MAX_UNCOMPRESSED_BYTES = 52428800;

    public function __construct(
        protected SeoSitemapBuilder $builder,
        protected SeoSitemapXmlRenderer $renderer,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
    ) {}

    /**
     * @return array<string, array{path: string, urls: int, bytes: int}>
     */
    public function generate(bool $dryRun = false): array
    {
        $files = $this->buildFiles();

        $this->assertProtocolLimits($files);
        $this->assertValidXmlPayloads($files);

        if (! $dryRun) {
            $this->publishFiles($files);
        }

        return collect($files)
            ->map(fn (array $meta, string $relativePath): array => [
                'path' => $relativePath,
                'urls' => $meta['urls'],
                'bytes' => strlen($meta['contents']),
            ])
            ->all();
    }

    /**
     * @return array<string, array{contents: string, urls: int}>
     */
    protected function buildFiles(): array
    {
        $articleShards = $this->builder->articleSitemapShards();
        $newsShards = $this->builder->newsSitemapShards();
        $sitemapIndexItems = $this->builder->sitemapIndexItems($articleShards, $newsShards);
        $staticUrls = $this->builder->staticUrls();
        $questionHubUrls = $this->publicQuestionCatalogService->hubSitemapUrls();
        $questionCategoryUrls = $this->publicQuestionCatalogService->categorySitemapUrls();
        $questionTopicUrls = QuestionSeoTopic::query()
            ->indexable()
            ->orderBy('slug')
            ->get()
            ->map(fn (QuestionSeoTopic $topic): array => [
                'loc' => route('public.questions.topics.show', $topic->slug),
                'lastmod' => $topic->updated_at?->toIso8601String(),
                'images' => [],
            ])
            ->all();
        $questionVideoUrls = $this->publicQuestionCatalogService->videoSitemapUrls();
        $questionSitemapIndexItems = $this->builder->questionSitemapIndexItems();
        $trafficSignUrls = $this->builder->trafficSignUrls();
        $supportingPageUrls = $this->builder->supportingPageUrls();
        $trafficSignCategoryUrls = $this->builder->trafficSignCategoryUrls();
        $authorUrls = $this->builder->authorUrls();
        $legalContentUrls = $this->builder->legalContentUrls();

        $files = [
            'sitemap.xml' => [
                'contents' => $this->renderer->sitemapIndex($sitemapIndexItems),
                'urls' => count($sitemapIndexItems),
            ],
            ltrim(SeoSitemapBuilder::STATIC_SITEMAP_PATH, '/') => [
                'contents' => $this->renderer->urlset($staticUrls),
                'urls' => count($staticUrls),
            ],
            'sitemaps/question-hub.xml' => [
                'contents' => $this->renderer->urlset($questionHubUrls),
                'urls' => count($questionHubUrls),
            ],
            'sitemaps/question-categories.xml' => [
                'contents' => $this->renderer->urlset($questionCategoryUrls),
                'urls' => count($questionCategoryUrls),
            ],
            'sitemaps/question-topics.xml' => [
                'contents' => $this->renderer->urlset($questionTopicUrls),
                'urls' => count($questionTopicUrls),
            ],
            ltrim(SeoSitemapBuilder::LEGACY_QUESTIONS_SITEMAP_PATH, '/') => [
                'contents' => $this->renderer->sitemapIndex($questionSitemapIndexItems),
                'urls' => count($questionSitemapIndexItems),
            ],
            ltrim(SeoSitemapBuilder::VIDEO_SITEMAP_PATH, '/') => [
                'contents' => $this->renderer->videoUrlset($questionVideoUrls),
                'urls' => count($questionVideoUrls),
            ],
            'sitemaps/traffic-signs.xml' => [
                'contents' => $this->renderer->urlset($trafficSignUrls, true),
                'urls' => count($trafficSignUrls),
            ],
            'sitemaps/traffic-sign-supporting-pages.xml' => [
                'contents' => $this->renderer->urlset($supportingPageUrls),
                'urls' => count($supportingPageUrls),
            ],
            'sitemaps/traffic-sign-categories.xml' => [
                'contents' => $this->renderer->urlset($trafficSignCategoryUrls),
                'urls' => count($trafficSignCategoryUrls),
            ],
            'sitemaps/authors.xml' => [
                'contents' => $this->renderer->urlset($authorUrls),
                'urls' => count($authorUrls),
            ],
            'sitemaps/legal-content.xml' => [
                'contents' => $this->renderer->urlset($legalContentUrls),
                'urls' => count($legalContentUrls),
            ],
        ];

        foreach ($articleShards as $relativePath => $shard) {
            $files[$relativePath] = [
                'contents' => $this->renderer->urlset($shard['urls']),
                'urls' => count($shard['urls']),
            ];
        }

        foreach ($newsShards as $relativePath => $shard) {
            $files[$relativePath] = [
                'contents' => $this->renderer->newsUrlset($shard['urls']),
                'urls' => count($shard['urls']),
            ];
        }

        foreach ($this->publicQuestionCatalogService->visibleCategories() as $category) {
            $urls = $this->publicQuestionCatalogService->sitemapUrlsForCanonicalCategory($category);

            if ($urls === []) {
                continue;
            }

            $includeImages = collect($urls)
                ->contains(fn (array $item): bool => ($item['images'] ?? []) !== []);

            $files[ltrim($this->builder->questionSitemapPathForCategory($category), '/')] = [
                'contents' => $this->renderer->urlset($urls, $includeImages),
                'urls' => count($urls),
            ];
        }

        return $files;
    }

    /**
     * @param  array<string, array{contents:string,urls:int}>  $files
     */
    protected function assertProtocolLimits(array $files): void
    {
        $maxUrls = max(
            1,
            (int) config('seo.sitemap_max_urls_per_file', self::MAX_URLS_PER_FILE),
        );
        $maxBytes = max(
            1,
            (int) config('seo.sitemap_max_uncompressed_bytes', self::MAX_UNCOMPRESSED_BYTES),
        );

        $newsMaxEntries = max(
            1,
            min(
                SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES,
                (int) config('newsroom.news_sitemap_max_entries', SeoSitemapBuilder::NEWS_SITEMAP_MAX_ENTRIES),
            ),
        );

        foreach ($files as $relativePath => $meta) {
            if ($this->isNewsSitemapPath($relativePath) && $meta['urls'] > $newsMaxEntries) {
                throw new \RuntimeException(sprintf(
                    'Generated News sitemap exceeds entry limit: %s urls=%d limit=%d',
                    $relativePath,
                    $meta['urls'],
                    $newsMaxEntries,
                ));
            }

            if ($meta['urls'] > $maxUrls) {
                throw new \RuntimeException(sprintf(
                    'Generated sitemap exceeds URL limit: %s urls=%d limit=%d',
                    $relativePath,
                    $meta['urls'],
                    $maxUrls,
                ));
            }

            $bytes = strlen($meta['contents']);

            if ($bytes > $maxBytes) {
                throw new \RuntimeException(sprintf(
                    'Generated sitemap exceeds uncompressed byte limit: %s bytes=%d limit=%d',
                    $relativePath,
                    $bytes,
                    $maxBytes,
                ));
            }
        }
    }

    protected function isNewsSitemapPath(string $relativePath): bool
    {
        return $relativePath === ltrim(SeoSitemapBuilder::NEWS_SITEMAP_PATH, '/')
            || preg_match('#^sitemaps/news-\d+-\d+\.xml$#', $relativePath) === 1;
    }

    /**
     * @param  array<string, array{contents:string,urls:int}>  $files
     */
    protected function assertValidXmlPayloads(array $files): void
    {
        foreach ($files as $relativePath => $meta) {
            if (@simplexml_load_string($meta['contents']) === false) {
                throw new \RuntimeException('Generated sitemap XML is invalid: '.$relativePath);
            }
        }
    }

    /**
     * @param  array<string, array{contents:string,urls:int}>  $files
     */
    protected function publishFiles(array $files): void
    {
        $index = $files['sitemap.xml'] ?? null;

        if (! is_array($index)) {
            throw new \RuntimeException('Generated sitemap set is missing sitemap.xml.');
        }

        $this->ensureSitemapDirectory();

        foreach ($files as $relativePath => $meta) {
            if ($relativePath === 'sitemap.xml') {
                continue;
            }

            $this->writeAtomically(public_path($relativePath), $meta['contents']);
        }

        $this->writeAtomically(public_path('sitemap.xml'), $index['contents']);

        $this->removeObsoleteNewsroomSitemapFiles(array_keys($files));
    }

    protected function ensureSitemapDirectory(): void
    {
        $directory = public_path('sitemaps');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }

    /**
     * @param  list<string>  $publishedPaths
     */
    protected function removeObsoleteNewsroomSitemapFiles(array $publishedPaths): void
    {
        $published = array_fill_keys($publishedPaths, true);
        $directory = public_path('sitemaps');

        foreach (['articles*.xml', 'news*.xml'] as $pattern) {
            foreach (File::glob($directory.DIRECTORY_SEPARATOR.$pattern) ?: [] as $file) {
                $relativePath = 'sitemaps/'.basename($file);

                if (
                    $this->isManagedNewsroomSitemapPath($relativePath)
                    && ! isset($published[$relativePath])
                ) {
                    File::delete($file);
                }
            }
        }
    }

    protected function isManagedNewsroomSitemapPath(string $relativePath): bool
    {
        return $relativePath === ltrim(SeoSitemapBuilder::ARTICLES_SITEMAP_PATH, '/')
            || $relativePath === ltrim(SeoSitemapBuilder::NEWS_SITEMAP_PATH, '/')
            || preg_match('#^sitemaps/(?:articles|news)-\\d+-\\d+\\.xml$#', $relativePath) === 1;
    }

    protected function writeAtomically(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $temporaryPath = $path.'.tmp.'.bin2hex(random_bytes(4));

        if (File::put($temporaryPath, $contents, true) === false) {
            throw new \RuntimeException('Unable to stage sitemap XML: '.$path);
        }

        if (@simplexml_load_string($contents) === false) {
            File::delete($temporaryPath);

            throw new \RuntimeException('Generated sitemap XML is invalid: '.$path);
        }

        if (! File::move($temporaryPath, $path)) {
            File::delete($temporaryPath);

            throw new \RuntimeException('Unable to atomically publish sitemap XML: '.$path);
        }
    }
}
