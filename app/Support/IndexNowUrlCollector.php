<?php

namespace App\Support;

class IndexNowUrlCollector
{
    public function __construct(
        protected SeoSitemapBuilder $sitemapBuilder,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected NewsroomPublicGate $newsroomPublicGate,
    ) {}

    /**
     * @return list<string>
     */
    public function publicPageUrls(): array
    {
        $items = [
            ...$this->sitemapBuilder->staticUrls(),
            ...$this->publicQuestionCatalogService->hubSitemapUrls(),
            ...$this->publicQuestionCatalogService->categorySitemapUrls(),
            ...$this->sitemapBuilder->trafficSignUrls(),
            ...$this->sitemapBuilder->supportingPageUrls(),
            ...$this->sitemapBuilder->trafficSignCategoryUrls(),
            ...$this->sitemapBuilder->authorUrls(),
            ...$this->sitemapBuilder->legalContentUrls(),
        ];

        foreach ($this->publicQuestionCatalogService->visibleCategories() as $category) {
            $items = [
                ...$items,
                ...$this->publicQuestionCatalogService->sitemapUrlsForCanonicalCategory($category),
            ];
        }

        return collect($items)
            ->pluck('loc')
            ->filter(fn (mixed $url): bool => is_string($url) && trim($url) !== '')
            ->map(fn (string $url): string => trim($url))
            ->reject(fn (string $url): bool => $this->newsroomPublicGate->disabled() && $this->isNewsroomUrl($url))
            ->unique()
            ->values()
            ->all();
    }

    private function isNewsroomUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        return $path === '/aktualnosci'
            || str_starts_with($path, '/aktualnosci/')
            || $path === '/poradniki'
            || str_starts_with($path, '/poradniki/');
    }
}
