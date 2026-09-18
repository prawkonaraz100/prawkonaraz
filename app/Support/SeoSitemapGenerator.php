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

        if (! $dryRun) {
            $this->prepareSitemapDirectory();

            foreach ($files as $relativePath => $meta) {
                $this->writeAtomically(public_path($relativePath), $meta['contents']);
            }
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
        $sitemapIndexItems = $this->builder->sitemapIndexItems($articleShards);
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

        foreach ($files as $relativePath => $meta) {
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

    protected function prepareSitemapDirectory(): void
    {
        $directory = public_path('sitemaps');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        foreach (File::glob($directory.DIRECTORY_SEPARATOR.'*.xml') ?: [] as $file) {
            File::delete($file);
        }
    }

    protected function writeAtomically(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $temporaryPath = $path.'.tmp.'.bin2hex(random_bytes(4));

        File::put($temporaryPath, $contents, true);

        if (@simplexml_load_string($contents) === false) {
            File::delete($temporaryPath);

            throw new \RuntimeException('Generated sitemap XML is invalid: '.$path);
        }

        File::move($temporaryPath, $path);
    }
}
