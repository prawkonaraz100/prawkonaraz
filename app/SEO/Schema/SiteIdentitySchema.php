<?php

namespace App\SEO\Schema;

use App\Support\PublicUrlResolver;

class SiteIdentitySchema
{
    public function __construct(
        protected PublicUrlResolver $publicUrlResolver,
        protected SchemaIds $schemaIds,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $sameAs = array_values(array_filter(
            (array) config('content.organization.same_as', []),
            fn (mixed $url): bool => is_string($url) && trim($url) !== '',
        ));
        $email = trim((string) config('content.organization.email', ''));
        $legalName = trim((string) config('content.organization.legal_name', ''));
        $logoUrl = $this->publicUrlResolver->normalize(
            (string) config('content.organization.logo_url', '/favicon.png'),
        );

        return array_filter([
            '@id' => $this->schemaIds->organization(),
            '@type' => 'Organization',
            'name' => $this->siteName(),
            'legalName' => $legalName !== '' ? $legalName : null,
            'url' => $this->publicUrlResolver->currentRoot(),
            'description' => trim((string) config('content.organization.description', '')),
            'logo' => $logoUrl !== null ? array_filter([
                '@type' => 'ImageObject',
                'url' => $logoUrl,
                'contentUrl' => $logoUrl,
                'width' => $this->positiveInt(config('content.organization.logo_width')),
                'height' => $this->positiveInt(config('content.organization.logo_height')),
            ], fn (mixed $value): bool => $value !== null && $value !== '') : null,
            'email' => $email !== '' ? $email : null,
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        return array_filter([
            '@id' => $this->schemaIds->website(),
            '@type' => 'WebSite',
            'url' => $this->publicUrlResolver->currentRoot(),
            'name' => $this->siteName(),
            'alternateName' => $this->alternateName(),
            'publisher' => [
                '@id' => $this->schemaIds->organization(),
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('public.questions.hub').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function siteName(): string
    {
        $name = trim((string) config('content.organization.name', ''));

        if ($name !== '') {
            return $name;
        }

        $fallback = trim((string) config('app.name', ''));

        return $fallback !== '' ? $fallback : 'prawkonaraz.pl';
    }

    protected function alternateName(): ?string
    {
        $alternateName = trim((string) config('app.name', ''));

        if ($alternateName === '' || mb_strtolower($alternateName) === mb_strtolower($this->siteName())) {
            return null;
        }

        return $alternateName;
    }

    protected function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
