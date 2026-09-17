<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\SEO\Schema\SchemaIds;
use Illuminate\Support\Collection;

class ContentAuthorSchemaService
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
        protected SchemaIds $schemaIds,
    ) {}

    /**
     * @param  array<string, mixed>  $breadcrumbSchema
     * @param  Collection<int, string>  $knowsAbout
     * @return list<array<string, mixed>>
     */
    public function profile(ContentAuthor $author, array $breadcrumbSchema, Collection $knowsAbout): array
    {
        return [
            $breadcrumbSchema,
            [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'name' => $author->name,
                'url' => route('content-authors.show', $author->slug),
                'dateModified' => $author->updated_at?->toIso8601String(),
                'mainEntity' => $this->person($author, $knowsAbout),
            ],
        ];
    }

    /**
     * @param  Collection<int, string>  $knowsAbout
     * @return array<string, mixed>
     */
    public function person(ContentAuthor $author, ?Collection $knowsAbout = null): array
    {
        $sameAs = array_values(array_filter([
            $this->nonBlankString($author->linkedin_url),
            $this->nonBlankString($author->external_profile_url),
        ]));

        return array_filter([
            '@id' => $this->schemaIds->contentAuthorPerson($author),
            '@type' => 'Person',
            'name' => $author->name,
            'url' => route('content-authors.show', $author->slug),
            'jobTitle' => $this->nonBlankString($author->job_title),
            'description' => $this->nonBlankString($author->bio),
            'image' => $this->mediaUrlResolver->resolve($author->photo_path, 'public'),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
            'knowsAbout' => $knowsAbout?->filter()->values()->all() ?: null,
            'worksFor' => [
                '@id' => $this->schemaIds->organization(),
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    private function nonBlankString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
