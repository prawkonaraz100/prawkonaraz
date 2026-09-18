<?php

namespace App\Support;

use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomCategorySchemaService
{
    public function __construct(
        private readonly PublicUrlResolver $publicUrlResolver,
        private readonly SchemaIds $schemaIds,
        private readonly SchemaRenderer $schemaRenderer,
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  array{id:int,name:string,slug:string,description:?string,seo_title:?string,seo_description:?string}  $category
     * @param  LengthAwarePaginator<int, array<string,mixed>>  $articles
     * @param  list<array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    public function build(
        array $category,
        LengthAwarePaginator $articles,
        array $breadcrumbs,
        string $canonicalUrl,
        string $description,
    ): array {
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $itemListId = $this->schemaIds->fragment($canonicalUrl, 'articles');
        $itemListElements = [];
        $position = $articles->firstItem() ?? 1;

        foreach ($articles->items() as $article) {
            $url = $this->publicUrlResolver->normalize((string) ($article['url'] ?? ''));

            if ($url === null) {
                continue;
            }

            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => $url,
                'name' => (string) ($article['title'] ?? ''),
            ];
        }

        $collectionPage = [
            '@id' => $webPageId,
            '@type' => 'CollectionPage',
            'name' => (string) $category['name'],
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
                    'name' => 'Materiały w kategorii '.$category['name'],
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
