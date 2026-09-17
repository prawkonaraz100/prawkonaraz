<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Support\Collection;

class SharedAuthorTrafficSignSchemaService extends TrafficSignSchemaService
{
    public function __construct(
        MediaUrlResolver $mediaUrlResolver,
        TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        PublicUrlResolver $publicUrlResolver,
        SchemaIds $schemaIds,
        SchemaRenderer $schemaRenderer,
        SiteIdentitySchema $siteIdentitySchema,
        protected ContentAuthorSchemaService $contentAuthorSchemaService,
    ) {
        parent::__construct(
            $mediaUrlResolver,
            $trafficSignBreadcrumbs,
            $publicUrlResolver,
            $schemaIds,
            $schemaRenderer,
            $siteIdentitySchema,
        );
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, TrafficSign>  $signs
     * @return list<array<string, mixed>>
     */
    public function author(ContentAuthor $author, array $breadcrumbs, Collection $signs): array
    {
        return $this->contentAuthorSchemaService->profile(
            $author,
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            $signs->take(5)->map(fn (TrafficSign $sign): string => $sign->publicTitle()),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function contentAuthorSchema(?ContentAuthor $author): ?array
    {
        return $author instanceof ContentAuthor
            ? $this->contentAuthorSchemaService->person($author)
            : null;
    }
}
