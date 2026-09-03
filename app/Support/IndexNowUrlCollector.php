<?php

namespace App\Support;

class IndexNowUrlCollector
{
    public function __construct(
        protected SeoSitemapBuilder $sitemapBuilder,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
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
            ->unique()
            ->values()
            ->all();
    }
}
