<?php

namespace App\SEO\Schema;

use App\Support\PublicUrlResolver;

class SiteIdentitySchema
{
    public function __construct(
        protected PublicUrlResolver $publicUrlResolver,
        protected SchemaIds $schemaIds,
    ) {}

    public function name(): string
    {
        $name = trim((string) config('content.organization.name', config('app.name', 'PrawkoNaRaz')));

        return $name !== '' ? $name : 'PrawkoNaRaz';
    }

    public function alternateName(): ?string
    {
        $alternateName = trim((string) config('content.organization.alternate_name', config('app.name', 'prawkonaraz.pl')));

        if ($alternateName === '' || strcasecmp($alternateName, $this->name()) === 0) {
            return null;
        }

        return $alternateName;
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrlResolver->normalize(
            (string) config('content.organization.logo_url', '/images/site-header-logo-20260728.png'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function organization(bool $includeContactPoint = false): array
    {
        $sameAs = (array) config('content.organization.same_as', []);
        $email = trim((string) config('content.organization.email', ''));
        $legalName = trim((string) config('content.organization.legal_name', ''));
        $logoUrl = $this->logoUrl();
        $logoWidth = max(0, (int) config('content.organization.logo_width', 0));
        $logoHeight = max(0, (int) config('content.organization.logo_height', 0));

        return array_filter([
            '@id' => $this->schemaIds->organization(),
            '@type' => 'Organization',
            'name' => $this->name(),
            'alternateName' => $this->alternateName(),
            'legalName' => $legalName !== '' ? $legalName : null,
            'url' => $this->publicUrlResolver->currentRoot(),
            'description' => (string) config('content.organization.description'),
            'logo' => $logoUrl !== null ? array_filter([
                '@type' => 'ImageObject',
                'url' => $logoUrl,
                'width' => $logoWidth > 0 ? $logoWidth : null,
                'height' => $logoHeight > 0 ? $logoHeight : null,
            ], fn (mixed $value): bool => $value !== null && $value !== '') : null,
            'email' => $email !== '' ? $email : null,
            'contactPoint' => $includeContactPoint && $email !== '' ? [[
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $email,
                'availableLanguage' => ['pl'],
            ]] : null,
            'sameAs' => $sameAs === [] ? null : $sameAs,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>|null  $potentialAction
     * @return array<string, mixed>
     */
    public function website(?array $potentialAction = null): array
    {
        return array_filter([
            '@id' => $this->schemaIds->website(),
            '@type' => 'WebSite',
            'url' => $this->publicUrlResolver->currentRoot(),
            'name' => $this->name(),
            'alternateName' => $this->alternateName(),
            'inLanguage' => 'pl-PL',
            'publisher' => [
                '@id' => $this->schemaIds->organization(),
            ],
            'potentialAction' => $potentialAction,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public function organizationDocument(bool $includeContactPoint = false): array
    {
        return [
            '@context' => 'https://schema.org',
            ...$this->organization($includeContactPoint),
        ];
    }
}
