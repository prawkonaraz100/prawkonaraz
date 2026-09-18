<?php

namespace App\Support;

use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomGuideHubSchemaService
{
    public function __construct(
        private readonly PublicUrlResolver $publicUrlResolver,
        private readonly SchemaIds $schemaIds,
        private readonly SchemaRenderer $schemaRenderer,
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, array<string,mixed>>  $guides
     * @param  list<array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    public function build(
        LengthAwarePaginator $guides,
        array $breadcrumbs,
        string $canonicalUrl,
        string $description,
    ): array {
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $itemListId = $this->schemaIds->fragment($canonicalUrl, 'guides');
        $itemListElements = [];
        $position = $guides->firstItem() ?? 1;

        foreach ($guides->items() as $guide) {
            $url = $this->publicUrlResolver->normalize((string) ($guide['url'] ?? ''));

            if ($url === null) {
                continue;
            }

            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $url,
                'name' => (string) ($guide['title'] ?? ''),
            ];
        }

        $collectionPage = [
            '@id' => $webPageId,
            '@type' => 'CollectionPage',
            'name' => 'Poradniki',
            'description' => $description,
            'url' => $canonicalUrl,
            'inLanguage' => 'pl-PL',
            'isPartOf' => [
                '@id' => $this->schemaIds->website(),
            ],
            'breadcrumb' => [
                '@id' => $breadcrumbId,
            ],
            'author' => [
                '@id' => $this->schemaIds->organization(),
            ],
            'publisher' => [
                '@id' => $this->schemaIds->organization(),
            ],
        ];

        if ($itemListElements !== []) {
            $collectionPage['mainEntity'] = ['@id' => $itemListId];
        }

        return $this->schemaRenderer->graph([
            $this->siteIdentitySchema->organization(),
            $this->siteIdentitySchema->website(),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            $collectionPage,
            $itemListElements !== []
                ? [
                    '@id' => $itemListId,
                    '@type' => 'ItemList',
                    'name' => 'Poradniki',
                    'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                    'numberOfItems' => count($itemListElements),
                    'itemListElement' => $itemListElements,
                ]
                : null,
        ]);
    }

    /**
     * @param  list<array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    private function breadcrumbSchema(array $breadcrumbs, string $breadcrumbId): array
    {
        return [
            '@id' => $breadcrumbId,
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }
}
