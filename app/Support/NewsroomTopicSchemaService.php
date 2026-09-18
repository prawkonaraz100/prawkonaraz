<?php

namespace App\Support;

use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomTopicSchemaService
{
    public function __construct(
        private readonly PublicUrlResolver $publicUrlResolver,
        private readonly SchemaIds $schemaIds,
        private readonly SchemaRenderer $schemaRenderer,
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  array{id:int,title:string,slug:string,description:string,seo_title:?string,seo_description:?string,published_at:?string}  $topic
     * @param  array<string,mixed>|null  $featured
     * @param  LengthAwarePaginator<int,array<string,mixed>>  $articles
     * @param  list<array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    public function build(
        array $topic,
        ?array $featured,
        LengthAwarePaginator $articles,
        array $breadcrumbs,
        string $canonicalUrl,
        string $description,
    ): array {
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $itemListId = $this->schemaIds->fragment($canonicalUrl, 'articles');
        $items = [];

        if ($featured !== null) {
            $items[] = $featured;
        }

        foreach ($articles->items() as $article) {
            $items[] = $article;
        }

        $itemListElements = [];
        foreach ($items as $index => $article) {
            $url = $this->publicUrlResolver->normalize((string) ($article['url'] ?? ''));

            if ($url === null) {
                continue;
            }

            $itemListElements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => $url,
                'name' => (string) ($article['title'] ?? ''),
            ];
        }

        $collectionPage = [
            '@id' => $webPageId,
            '@type' => 'CollectionPage',
            'name' => (string) $topic['title'],
            'description' => $description,
            'url' => $canonicalUrl,
            'inLanguage' => 'pl-PL',
            'isPartOf' => ['@id' => $this->schemaIds->website()],
            'breadcrumb' => ['@id' => $breadcrumbId],
            'author' => ['@id' => $this->schemaIds->organization()],
            'publisher' => ['@id' => $this->schemaIds->organization()],
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
                    'name' => 'Materiały w temacie '.$topic['title'],
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
