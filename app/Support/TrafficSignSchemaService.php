<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TrafficSignSchemaService
{
    public function __construct(
        protected MediaUrlResolver $mediaUrlResolver,
        protected TrafficSignBreadcrumbs $trafficSignBreadcrumbs,
        protected PublicUrlResolver $publicUrlResolver,
        protected SchemaIds $schemaIds,
        protected SchemaRenderer $schemaRenderer,
    ) {}

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, TrafficSignCategory>  $categories
     * @param  Collection<int, TrafficSign>  $featuredSigns
     * @return array<string, mixed>
     */
    public function hub(array $breadcrumbs, Collection $categories, Collection $featuredSigns): array
    {
        $canonicalUrl = route('traffic-signs.index');
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $termSetId = $this->schemaIds->trafficSignTermSet();
        $categoryListId = $this->schemaIds->trafficSignHubCategoryList();
        $featuredListId = $this->schemaIds->trafficSignHubFeaturedList();
        $categoryReferences = $categories
            ->map(fn (TrafficSignCategory $category): array => ['@id' => $this->schemaIds->trafficSignCategoryEntity($category)])
            ->values()
            ->all();
        $hasPart = [['@id' => $categoryListId]];

        if ($featuredSigns->isNotEmpty()) {
            $hasPart[] = ['@id' => $featuredListId];
        }

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => 'Znaki drogowe',
                'description' => 'Kompleksowa baza znaków drogowych z kategoriami, opisami znaczenia, zachowania kierowcy i kontekstu prawnego.',
                'url' => $canonicalUrl,
                'inLanguage' => 'pl-PL',
                'isPartOf' => [
                    '@id' => $websiteId,
                ],
                'breadcrumb' => [
                    '@id' => $breadcrumbId,
                ],
                'author' => [
                    '@id' => $organizationId,
                ],
                'publisher' => [
                    '@id' => $organizationId,
                ],
                'mainEntity' => [
                    '@id' => $termSetId,
                ],
                'hasPart' => $hasPart,
            ],
            $this->trafficSignTermSetSchema($termSetId, $organizationId, $categoryReferences),
            $this->trafficSignCategoryListSchema($categoryListId, $categories),
            ...$categories
                ->map(fn (TrafficSignCategory $category): array => $this->trafficSignCategoryDefinedTermSchema($category, $termSetId))
                ->all(),
            $featuredSigns->isNotEmpty()
                ? $this->trafficSignListSchema($featuredListId, $featuredSigns, 'Ostatnio aktualizowane znaki drogowe')
                : null,
            ...$featuredSigns
                ->map(fn (TrafficSign $sign): array => $this->trafficSignDefinedTermSchema($sign, $termSetId, $sign->category instanceof TrafficSignCategory ? $sign->category : null))
                ->all(),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, TrafficSign>  $signs
     * @return array<string, mixed>
     */
    public function category(TrafficSignCategory $category, array $breadcrumbs, Collection $signs): array
    {
        $canonicalUrl = route('traffic-signs.categories.show', $category->slug);
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $termSetId = $this->schemaIds->trafficSignTermSet();
        $categoryId = $this->schemaIds->trafficSignCategoryEntity($category);
        $signListId = $this->schemaIds->trafficSignCategorySignList($category);

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => $category->name,
                'description' => $category->intro_body ?: $category->description,
                'url' => $canonicalUrl,
                'inLanguage' => 'pl-PL',
                'isPartOf' => [
                    '@id' => $websiteId,
                ],
                'breadcrumb' => [
                    '@id' => $breadcrumbId,
                ],
                'author' => [
                    '@id' => $organizationId,
                ],
                'publisher' => [
                    '@id' => $organizationId,
                ],
                'mainEntity' => [
                    '@id' => $categoryId,
                ],
                'hasPart' => [
                    '@id' => $signListId,
                ],
            ],
            $this->trafficSignTermSetSchema($termSetId, $organizationId, [['@id' => $categoryId]]),
            $this->trafficSignCategoryDefinedTermSchema($category, $termSetId),
            $this->trafficSignListSchema($signListId, $signs, 'Znaki w kategorii '.$category->name),
            ...$signs
                ->map(fn (TrafficSign $sign): array => $this->trafficSignDefinedTermSchema($sign, $termSetId, $category))
                ->all(),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function sign(TrafficSign $sign, array $breadcrumbs): array
    {
        $sign->loadMissing(['author', 'category']);

        $category = $sign->category instanceof TrafficSignCategory ? $sign->category : null;
        $author = $sign->author instanceof ContentAuthor ? $sign->author : null;
        $canonicalUrl = route('traffic-signs.show', $sign->slug);
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $articleId = $this->schemaIds->fragment($canonicalUrl, 'article');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $termSetId = $this->schemaIds->trafficSignTermSet();
        $signId = $this->schemaIds->trafficSignEntity($sign);
        $categoryId = $category instanceof TrafficSignCategory
            ? $this->schemaIds->trafficSignCategoryEntity($category)
            : null;
        $authorNode = $this->contentAuthorSchema($author);
        $authorId = is_array($authorNode) && is_string($authorNode['@id'] ?? null)
            ? $authorNode['@id']
            : $organizationId;
        $images = $this->uniqueGraphImageObjects([
            $this->graphImageObject(
                $this->schemaIds->trafficSignImage($canonicalUrl),
                $webPageId,
                $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path),
                $sign->publicImageAlt(),
                $sign->image_width,
                $sign->image_height,
            ),
            $this->graphImageObject(
                $this->schemaIds->trafficSignOgImage($canonicalUrl),
                $webPageId,
                $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->og_image_path),
                $sign->publicOgImageAlt(),
                $sign->og_image_width ?: $sign->image_width,
                $sign->og_image_height ?: $sign->image_height,
            ),
        ]);
        $imageReferences = collect($images)
            ->pluck('@id')
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->map(fn (string $id): array => ['@id' => $id])
            ->values()
            ->all();
        $primaryImageId = $imageReferences[0]['@id'] ?? null;
        $faqSchema = $this->trafficSignFaqPageSchema($sign, $canonicalUrl, $webPageId);
        $faqId = is_array($faqSchema) && is_string($faqSchema['@id'] ?? null)
            ? $faqSchema['@id']
            : null;
        $webPageHasPart = array_values(array_filter([
            ['@id' => $articleId],
            $faqId !== null ? ['@id' => $faqId] : null,
        ]));

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'WebPage',
                'name' => $this->trafficSignTitle($sign),
                'description' => $this->trafficSignDescription($sign),
                'url' => $canonicalUrl,
                'inLanguage' => 'pl-PL',
                'isPartOf' => [
                    '@id' => $websiteId,
                ],
                'breadcrumb' => [
                    '@id' => $breadcrumbId,
                ],
                'author' => [
                    '@id' => $authorId,
                ],
                'publisher' => [
                    '@id' => $organizationId,
                ],
                'mainEntity' => [
                    '@id' => $signId,
                ],
                'about' => $categoryId !== null ? [
                    '@id' => $categoryId,
                ] : null,
                'primaryImageOfPage' => $primaryImageId !== null ? [
                    '@id' => $primaryImageId,
                ] : null,
                'hasPart' => $webPageHasPart,
            ],
            $this->trafficSignTermSetSchema(
                $termSetId,
                $organizationId,
                $categoryId !== null ? [['@id' => $categoryId]] : [],
            ),
            $category instanceof TrafficSignCategory
                ? $this->trafficSignCategoryDefinedTermSchema($category, $termSetId)
                : null,
            $this->trafficSignDefinedTermSchema($sign, $termSetId, $category),
            $authorNode,
            $this->trafficSignArticleSchema($sign, $articleId, $webPageId, $signId, $authorId, $organizationId, $imageReferences),
            ...$images,
            $faqSchema,
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function author(ContentAuthor $author, array $breadcrumbs, Collection $signs): array
    {
        $sameAs = array_values(array_filter([
            $author->linkedin_url,
            $author->external_profile_url,
        ]));

        return [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'name' => $author->name,
                'url' => route('content-authors.show', $author->slug),
                'dateModified' => $author->updated_at->toIso8601String(),
                'mainEntity' => [
                    '@type' => 'Person',
                    'name' => $author->name,
                    'jobTitle' => $author->job_title,
                    'description' => $author->bio,
                    'sameAs' => $sameAs === [] ? null : $sameAs,
                    'image' => $this->mediaUrlResolver->resolve($author->photo_path, 'public'),
                    'knowsAbout' => $signs->take(5)->map(fn (TrafficSign $sign): string => $sign->publicTitle())->all(),
                ],
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function organizationPage(array $breadcrumbs): array
    {
        return [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            $this->organization(),
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function howItWorksPage(array $breadcrumbs): array
    {
        return [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            $this->organization(),
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => 'Jak działa PrawkoNaRaz',
                'url' => route('about.how-it-works'),
                'description' => 'Opis działania trybów nauki, powtórek, bazy pytań, promocji i zaproszeń w PrawkoNaRaz.',
                'about' => [
                    '@type' => 'SoftwareApplication',
                    'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
                    'applicationCategory' => 'EducationalApplication',
                ],
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function contactPage(array $breadcrumbs): array
    {
        return [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            $this->organization(),
            [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'name' => 'Kontakt',
                'url' => route('about.contact'),
                'description' => 'Kontakt z zespołem serwisu w sprawie treści, korekt i współpracy.',
                'mainEntity' => [
                    '@type' => 'Organization',
                    'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
                    'email' => (string) config('content.organization.email'),
                    'url' => $this->publicUrlResolver->currentRoot(),
                ],
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function methodologyPage(array $breadcrumbs): array
    {
        return [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            $this->organization(),
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => 'Pytania na prawo jazdy - jak uczymy teorii',
                'url' => route('about.methodology'),
                'description' => 'Opis sposobu nauki teorii i pytań na prawo jazdy z PrawkoNaRaz: wyjaśnienia, powtórki i materiały aktualizowane po zmianach.',
                'about' => [
                    '@type' => 'Organization',
                    'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return list<array<string, mixed>>
     */
    public function supportingPage(array $page, array $breadcrumbs, ?ContentAuthor $author, Collection $relatedSigns): array
    {
        $primarySign = $relatedSigns->first();
        $images = [];

        if ($primarySign instanceof TrafficSign) {
            $images = $this->uniqueImageObjects([
                $this->imageObject(
                    $this->mediaUrlResolver->resolveIfPublicAssetExists($primarySign->image_path),
                    $primarySign->publicImageAlt(),
                    $primarySign->image_width,
                    $primarySign->image_height,
                ),
                $this->imageObject(
                    $this->mediaUrlResolver->resolveIfPublicAssetExists($primarySign->og_image_path),
                    $primarySign->publicOgImageAlt(),
                    $primarySign->og_image_width ?: $primarySign->image_width,
                    $primarySign->og_image_height ?: $primarySign->image_height,
                ),
            ]);
        }

        $schemas = [
            $this->trafficSignBreadcrumbs->toSchema($breadcrumbs),
            [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $page['title'],
                'description' => $page['description'],
                'datePublished' => $page['published_at'] ?? null,
                'dateModified' => $page['updated_at'] ?? null,
                'author' => $author instanceof ContentAuthor
                    ? [
                        '@type' => 'Person',
                        'name' => $author->name,
                        'url' => route('content-authors.show', $author->slug),
                    ]
                    : [
                        '@type' => 'Organization',
                        'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
                    ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
                    'url' => $this->publicUrlResolver->currentRoot(),
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
                    ],
                ],
                'mainEntityOfPage' => route('traffic-signs.supporting.show', $page['slug']),
                'about' => $relatedSigns
                    ->map(fn (TrafficSign $sign): array => [
                        '@type' => 'DefinedTerm',
                        'name' => $sign->publicTitle(),
                        'url' => route('traffic-signs.show', $sign->slug),
                    ])
                    ->all(),
                'image' => $images,
            ],
        ];

        $faqItems = collect($page['faq_items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->values();

        if ($faqItems->isNotEmpty()) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqItems
                    ->map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $item['answer'],
                        ],
                    ])
                    ->all(),
            ];
        }

        return $schemas;
    }

    /**
     * @param  list<array{@id : string}>  $hasDefinedTerm
     * @return array<string, mixed>
     */
    protected function trafficSignTermSetSchema(string $termSetId, string $organizationId, array $hasDefinedTerm = []): array
    {
        return array_filter([
            '@id' => $termSetId,
            '@type' => 'DefinedTermSet',
            'name' => 'Baza znaków drogowych',
            'description' => 'Publiczny zbiór opisów znaków drogowych z podziałem na kategorie, znaczenie, zachowanie kierowcy i kontekst prawny.',
            'url' => route('traffic-signs.index'),
            'inLanguage' => 'pl-PL',
            'creator' => [
                '@id' => $organizationId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'hasDefinedTerm' => $hasDefinedTerm !== [] ? $hasDefinedTerm : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  Collection<int, TrafficSignCategory>  $categories
     * @return array<string, mixed>
     */
    protected function trafficSignCategoryListSchema(string $categoryListId, Collection $categories): array
    {
        return [
            '@id' => $categoryListId,
            '@type' => 'ItemList',
            'name' => 'Kategorie znaków drogowych',
            'numberOfItems' => $categories->count(),
            'itemListElement' => $categories
                ->values()
                ->map(fn (TrafficSignCategory $category, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('traffic-signs.categories.show', $category->slug),
                    'name' => $category->name,
                    'description' => $category->intro_body ?: $category->description,
                    'item' => [
                        '@id' => $this->schemaIds->trafficSignCategoryEntity($category),
                    ],
                ], fn (mixed $value): bool => $value !== null && $value !== ''))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function trafficSignCategoryDefinedTermSchema(TrafficSignCategory $category, string $termSetId): array
    {
        return array_filter([
            '@id' => $this->schemaIds->trafficSignCategoryEntity($category),
            '@type' => 'DefinedTerm',
            'name' => $category->name,
            'termCode' => $category->slug,
            'description' => $category->intro_body ?: $category->description,
            'url' => route('traffic-signs.categories.show', $category->slug),
            'inDefinedTermSet' => [
                '@id' => $termSetId,
            ],
            'subjectOf' => [
                '@id' => $this->schemaIds->fragment(route('traffic-signs.categories.show', $category->slug), 'webpage'),
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  Collection<int, TrafficSign>  $signs
     * @return array<string, mixed>
     */
    protected function trafficSignListSchema(string $listId, Collection $signs, string $name): array
    {
        return [
            '@id' => $listId,
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => $signs->count(),
            'itemListElement' => $signs
                ->values()
                ->map(fn (TrafficSign $sign, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('traffic-signs.show', $sign->slug),
                    'name' => $this->trafficSignTitle($sign),
                    'description' => $this->trafficSignDescription($sign),
                    'item' => [
                        '@id' => $this->schemaIds->trafficSignEntity($sign),
                        '@type' => 'DefinedTerm',
                        'name' => $this->trafficSignTitle($sign),
                        'url' => route('traffic-signs.show', $sign->slug),
                    ],
                ], fn (mixed $value): bool => $value !== null && $value !== ''))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function trafficSignDefinedTermSchema(TrafficSign $sign, string $termSetId, ?TrafficSignCategory $category): array
    {
        return array_filter([
            '@id' => $this->schemaIds->trafficSignEntity($sign),
            '@type' => 'DefinedTerm',
            'name' => $this->trafficSignTitle($sign),
            'termCode' => $this->nonBlankString($sign->code),
            'description' => $this->trafficSignDescription($sign),
            'url' => route('traffic-signs.show', $sign->slug),
            'inDefinedTermSet' => [
                '@id' => $termSetId,
            ],
            'subjectOf' => [
                '@id' => $this->schemaIds->fragment(route('traffic-signs.show', $sign->slug), 'webpage'),
            ],
            'about' => $category instanceof TrafficSignCategory ? [
                '@id' => $this->schemaIds->trafficSignCategoryEntity($category),
            ] : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  list<array{@id : string}>  $imageReferences
     * @return array<string, mixed>
     */
    protected function trafficSignArticleSchema(
        TrafficSign $sign,
        string $articleId,
        string $webPageId,
        string $signId,
        string $authorId,
        string $organizationId,
        array $imageReferences,
    ): array {
        $sections = collect([
            'Znaczenie znaku' => $sign->meaning,
            'Gdzie występuje' => $sign->placement,
            'Jak powinien zachować się kierowca' => $sign->driver_behavior,
            'Najczęstsze błędy' => $sign->common_mistakes,
            'Podstawa prawna' => $sign->legal_summary,
            'Mandat i konsekwencje' => $sign->fine_summary,
        ])
            ->filter(fn (mixed $value): bool => $this->nonBlankString($value) !== null)
            ->keys()
            ->values()
            ->all();

        return array_filter([
            '@id' => $articleId,
            '@type' => 'Article',
            'headline' => $this->trafficSignTitle($sign),
            'description' => $this->trafficSignDescription($sign),
            'url' => explode('#', $webPageId, 2)[0],
            'inLanguage' => 'pl-PL',
            'datePublished' => $sign->published_at?->toIso8601String(),
            'dateModified' => $sign->updated_at?->toIso8601String(),
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'about' => [
                '@id' => $signId,
            ],
            'author' => [
                '@id' => $authorId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'articleSection' => $sections !== [] ? $sections : null,
            'image' => $imageReferences !== [] ? $imageReferences : null,
            'citation' => $this->trafficSignLegalCitationSchema($sign),
            'keywords' => array_values(array_filter([
                $this->nonBlankString($sign->publicCode()),
                $this->nonBlankString($sign->name),
                $sign->category instanceof TrafficSignCategory ? $this->nonBlankString($sign->category->name) : null,
                'znaki drogowe',
            ])),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function trafficSignFaqPageSchema(TrafficSign $sign, string $canonicalUrl, string $webPageId): ?array
    {
        $faqItems = collect($sign->faq_items ?? [])
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->values();

        if ($faqItems->isEmpty()) {
            return null;
        }

        return [
            '@id' => $this->schemaIds->fragment($canonicalUrl, 'faq'),
            '@type' => 'FAQPage',
            'url' => $canonicalUrl.'#faq',
            'inLanguage' => 'pl-PL',
            'isPartOf' => [
                '@id' => $webPageId,
            ],
            'mainEntity' => $faqItems
                ->map(fn (array $item, int $index): array => [
                    '@id' => $this->schemaIds->fragment($canonicalUrl, 'faq-question-'.($index + 1)),
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function trafficSignLegalCitationSchema(TrafficSign $sign): ?array
    {
        $label = $this->nonBlankString($sign->legal_reference_label);
        $url = $this->nonBlankString($sign->legal_reference_url);

        if ($label === null && $url === null) {
            return null;
        }

        return array_filter([
            '@type' => 'CreativeWork',
            'name' => $label ?? 'Podstawa prawna znaku '.$this->trafficSignTitle($sign),
            'url' => $url,
            'description' => $this->nonBlankString($sign->legal_summary),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function contentAuthorSchema(?ContentAuthor $author): ?array
    {
        if (! $author instanceof ContentAuthor) {
            return null;
        }

        $sameAs = array_values(array_filter([
            $this->nonBlankString($author->linkedin_url),
            $this->nonBlankString($author->external_profile_url),
        ]));

        return array_filter([
            '@id' => $this->schemaIds->fragment(route('content-authors.show', $author->slug), 'person'),
            '@type' => 'Person',
            'name' => $author->name,
            'url' => route('content-authors.show', $author->slug),
            'jobTitle' => $this->nonBlankString($author->job_title),
            'description' => $this->nonBlankString($author->bio),
            'image' => $this->mediaUrlResolver->resolve($author->photo_path, 'public'),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
            'worksFor' => [
                '@id' => $this->schemaIds->organization(),
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function organizationSchema(string $organizationId): array
    {
        $sameAs = (array) config('content.organization.same_as', []);
        $email = (string) config('content.organization.email', '');
        $logoUrl = $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png'));
        $legalName = trim((string) config('content.organization.legal_name', ''));

        return array_filter([
            '@id' => $organizationId,
            '@type' => 'Organization',
            'name' => (string) config('content.organization.name', 'PrawkoNaRaz'),
            'legalName' => $legalName !== '' ? $legalName : null,
            'url' => $this->publicUrlResolver->currentRoot(),
            'description' => (string) config('content.organization.description'),
            'logo' => $logoUrl !== null ? [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ] : null,
            'email' => $email !== '' ? $email : null,
            'sameAs' => $sameAs === [] ? null : $sameAs,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function websiteSchema(string $websiteId, string $organizationId): array
    {
        return [
            '@id' => $websiteId,
            '@type' => 'WebSite',
            'url' => $this->publicUrlResolver->currentRoot(),
            'name' => (string) config('content.organization.name', 'PrawkoNaRaz'),
            'publisher' => [
                '@id' => $organizationId,
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    protected function breadcrumbSchema(array $breadcrumbs, string $breadcrumbId): array
    {
        $schema = $this->trafficSignBreadcrumbs->toSchema($breadcrumbs);
        unset($schema['@context']);
        $schema['@id'] = $breadcrumbId;

        return $schema;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function graphImageObject(
        string $imageId,
        string $webPageId,
        ?string $url,
        ?string $alt,
        ?int $width,
        ?int $height,
    ): ?array {
        $url = $this->nonBlankString($url);

        if ($url === null) {
            return null;
        }

        return array_filter([
            '@id' => $imageId,
            '@type' => 'ImageObject',
            'url' => $url,
            'contentUrl' => $url,
            'caption' => $this->nonBlankString($alt),
            'width' => $this->positiveInt($width),
            'height' => $this->positiveInt($height),
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $images
     * @return list<array<string, mixed>>
     */
    protected function uniqueGraphImageObjects(array $images): array
    {
        $uniqueImages = [];

        foreach ($images as $image) {
            if (! is_array($image) || ! filled($image['url'] ?? null)) {
                continue;
            }

            $uniqueImages[$image['url']] = $image;
        }

        return array_values($uniqueImages);
    }

    protected function trafficSignTitle(TrafficSign $sign): string
    {
        $title = $this->nonBlankString($sign->publicTitle());

        return $title !== '' ? $title : 'Znak drogowy';
    }

    protected function trafficSignDescription(TrafficSign $sign): string
    {
        foreach ([$sign->meta_description, $sign->intro_definition, $sign->meaning, $sign->name] as $value) {
            $text = $this->nonBlankString($value);

            if ($text !== null) {
                return Str::squish(strip_tags($text));
            }
        }

        return $this->trafficSignTitle($sign);
    }

    protected function nonBlankString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function organization(): array
    {
        $sameAs = (array) config('content.organization.same_as', []);
        $email = (string) config('content.organization.email', '');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) config('content.organization.name', config('app.name', 'prawkonaraz.pl')),
            'legalName' => (string) config('content.organization.legal_name', config('app.name', 'prawkonaraz.pl')),
            'url' => $this->publicUrlResolver->currentRoot(),
            'description' => (string) config('content.organization.description'),
            'logo' => $this->publicUrlResolver->normalize((string) config('content.organization.logo_url', '/favicon.png')),
            'email' => $email,
            'contactPoint' => $email !== '' ? [[
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $email,
                'availableLanguage' => ['pl'],
            ]] : null,
            'sameAs' => $sameAs === [] ? null : $sameAs,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function imageObject(?string $url, ?string $alt, ?int $width, ?int $height): ?array
    {
        if (! filled($url)) {
            return null;
        }

        return array_filter([
            '@type' => 'ImageObject',
            'url' => $url,
            'caption' => $alt,
            'width' => $width,
            'height' => $height,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $images
     * @return list<array<string, mixed>>
     */
    protected function uniqueImageObjects(array $images): array
    {
        $uniqueImages = [];

        foreach ($images as $image) {
            if (! is_array($image) || ! filled($image['url'] ?? null)) {
                continue;
            }

            $uniqueImages[$image['url']] = $image;
        }

        return array_values($uniqueImages);
    }
}
