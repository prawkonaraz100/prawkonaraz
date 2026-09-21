<?php

namespace App\Support;

use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;

final class NewsroomHomeSchemaService
{
    public function __construct(
        private readonly SchemaIds $schemaIds,
        private readonly SchemaRenderer $schemaRenderer,
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  list<array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    public function build(
        array $breadcrumbs,
        string $canonicalUrl,
        string $description,
    ): array {
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');

        return $this->schemaRenderer->graph([
            $this->siteIdentitySchema->organization(),
            $this->siteIdentitySchema->website(),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => 'Aktualności',
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
            ],
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
