<?php

namespace App\Support;

use App\Models\ContentAuthor;
use App\Models\LegalContentPage;
use App\Models\LegalTopic;
use App\Models\LegalUnit;
use App\Models\Question;
use App\Models\QuestionLegalReference;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LegalContentSchemaService
{
    public function __construct(
        protected LegalContentBreadcrumbs $breadcrumbs,
        protected PublicUrlResolver $publicUrlResolver,
        protected PublicQuestionCatalogService $publicQuestionCatalogService,
        protected QuestionTextFormatter $questionTextFormatter,
        protected SchemaIds $schemaIds,
        protected SchemaRenderer $schemaRenderer,
        protected SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, LegalContentPage>  $pages
     * @return array<string, mixed>
     */
    public function hub(array $breadcrumbs, Collection $pages): array
    {
        $canonicalUrl = route('public.regulations');
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $itemListId = $this->schemaIds->legalContentItemList();

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => 'Przepisy drogowe do egzaminu',
                'description' => 'Indeks edukacyjnych opracowań przepisów drogowych powiązanych z pytaniami egzaminacyjnymi.',
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
                    '@id' => $itemListId,
                ],
            ],
            $this->legalContentItemListSchema($itemListId, $pages),
            ...$pages
                ->map(fn (LegalContentPage $page): ?array => $this->legalTopicSchema($page->topic))
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, QuestionLegalReference>  $questionReferences
     * @return array<string, mixed>
     */
    public function page(LegalContentPage $page, array $breadcrumbs, Collection $questionReferences): array
    {
        $page->loadMissing([
            'topic',
            'author',
            'reviewer',
            'legalUnits.legalAct',
        ]);

        $canonicalUrl = route('public.regulations.show', $page->slug);
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $articleId = $this->schemaIds->legalContentPageEntity($page);
        $legalUnitListId = $this->schemaIds->legalContentLegalUnitList($page);
        $questionListId = $this->schemaIds->legalContentQuestionList($page);
        $legalUnitReferences = $page->legalUnits
            ->map(fn (LegalUnit $unit): array => ['@id' => $this->schemaIds->legalUnit($unit)])
            ->values()
            ->all();
        $questionReferencesForSchema = $questionReferences
            ->filter(fn (QuestionLegalReference $reference): bool => $reference->question instanceof Question)
            ->values();
        $questionNodeReferences = $questionReferencesForSchema
            ->map(fn (QuestionLegalReference $reference): array => ['@id' => $this->schemaIds->publicQuestionEntity($reference->question)])
            ->values()
            ->all();
        $topic = $page->topic instanceof LegalTopic ? $page->topic : null;
        $topicReference = $topic instanceof LegalTopic
            ? ['@id' => $this->schemaIds->legalTopicEntity($topic)]
            : null;
        $authorNode = $this->contentAuthorSchema($page->author);
        $reviewerNode = $this->contentAuthorSchema($page->reviewer);
        $authorId = is_array($authorNode) && is_string($authorNode['@id'] ?? null)
            ? $authorNode['@id']
            : $organizationId;
        $reviewerId = is_array($reviewerNode) && is_string($reviewerNode['@id'] ?? null)
            ? $reviewerNode['@id']
            : null;
        $articleHasPart = array_values(array_filter([
            $legalUnitReferences !== [] ? ['@id' => $legalUnitListId] : null,
            $questionNodeReferences !== [] ? ['@id' => $questionListId] : null,
        ]));

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'WebPage',
                'name' => $page->title,
                'description' => $this->legalContentDescription($page),
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
                    '@id' => $articleId,
                ],
                'about' => array_values(array_filter([$topicReference, ...$legalUnitReferences])),
            ],
            $this->legalTopicSchema($topic),
            ...$page->legalUnits
                ->map(fn (LegalUnit $unit): ?array => $this->legalUnitSchema($unit))
                ->filter()
                ->values()
                ->all(),
            $authorNode,
            $reviewerNode,
            $this->legalContentArticleSchema(
                $page,
                $articleId,
                $webPageId,
                $organizationId,
                $authorId,
                $reviewerId,
                array_values(array_filter([$topicReference, ...$legalUnitReferences])),
                $legalUnitReferences,
                $questionNodeReferences,
                $articleHasPart,
            ),
            $legalUnitReferences !== [] ? $this->legalUnitListSchema($legalUnitListId, $page->legalUnits) : null,
            $questionNodeReferences !== [] ? $this->questionListSchema($questionListId, $questionReferencesForSchema) : null,
            ...$questionReferencesForSchema
                ->map(fn (QuestionLegalReference $reference): ?array => $this->questionSchema($reference))
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function methodology(array $breadcrumbs): array
    {
        $canonicalUrl = route('public.regulations.methodology');
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'WebPage',
                'name' => 'Metodologia przepisów i podstaw prawnych',
                'url' => $canonicalUrl,
                'description' => 'Opis procesu weryfikacji przepisów, źródeł i podstaw prawnych w prawkonaraz.pl.',
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
                'about' => [
                    '@id' => $organizationId,
                ],
            ],
        ]);
    }

    /**
     * @param  Collection<int, LegalContentPage>  $pages
     * @return array<string, mixed>
     */
    protected function legalContentItemListSchema(string $itemListId, Collection $pages): array
    {
        return [
            '@id' => $itemListId,
            '@type' => 'ItemList',
            'name' => 'Najważniejsze tematy przepisów drogowych',
            'numberOfItems' => $pages->count(),
            'itemListElement' => $pages
                ->values()
                ->map(fn (LegalContentPage $page, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('public.regulations.show', $page->slug),
                    'name' => $page->title,
                    'description' => $this->legalContentDescription($page),
                    'item' => [
                        '@id' => $this->schemaIds->legalContentPageEntity($page),
                        '@type' => 'Article',
                        'name' => $page->title,
                        'url' => route('public.regulations.show', $page->slug),
                    ],
                ], fn (mixed $value): bool => $value !== null && $value !== ''))
                ->all(),
        ];
    }

    /**
     * @param  list<array{@id : string}>  $about
     * @param  list<array{@id : string}>  $citations
     * @param  list<array{@id : string}>  $mentions
     * @param  list<array{@id : string}>  $hasPart
     * @return array<string, mixed>
     */
    protected function legalContentArticleSchema(
        LegalContentPage $page,
        string $articleId,
        string $webPageId,
        string $organizationId,
        string $authorId,
        ?string $reviewerId,
        array $about,
        array $citations,
        array $mentions,
        array $hasPart,
    ): array {
        $sections = collect([
            'Krótko' => $page->summary,
            'W praktyce na egzaminie' => $page->exam_context,
            'Najważniejsze punkty' => $page->key_points,
            'Podstawa prawna' => $page->legalUnits->isNotEmpty() ? 'visible' : null,
            'Powiązane pytania egzaminacyjne' => $mentions !== [] ? 'visible' : null,
        ])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])
            ->keys()
            ->values()
            ->all();

        return array_filter([
            '@id' => $articleId,
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $this->legalContentDescription($page),
            'url' => explode('#', $webPageId, 2)[0],
            'inLanguage' => 'pl-PL',
            'datePublished' => $page->published_at?->toIso8601String(),
            'dateModified' => ($page->last_reviewed_at ?: $page->updated_at)?->toIso8601String(),
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'author' => [
                '@id' => $authorId,
            ],
            'reviewedBy' => $reviewerId !== null ? [
                '@id' => $reviewerId,
            ] : null,
            'publisher' => [
                '@id' => $organizationId,
            ],
            'about' => $about !== [] ? $about : null,
            'citation' => $citations !== [] ? $citations : null,
            'mentions' => $mentions !== [] ? $mentions : null,
            'hasPart' => $hasPart !== [] ? $hasPart : null,
            'articleSection' => $sections !== [] ? $sections : null,
            'isAccessibleForFree' => true,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function legalTopicSchema(?LegalTopic $topic): ?array
    {
        if (! $topic instanceof LegalTopic) {
            return null;
        }

        return array_filter([
            '@id' => $this->schemaIds->legalTopicEntity($topic),
            '@type' => 'DefinedTerm',
            'name' => $topic->title,
            'termCode' => $topic->slug,
            'description' => $this->nonBlankString($topic->description),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function legalUnitSchema(LegalUnit $unit): ?array
    {
        $name = $this->legalUnitName($unit);

        if ($name === null) {
            return null;
        }

        return array_filter([
            '@id' => $this->schemaIds->legalUnit($unit),
            '@type' => 'Legislation',
            'name' => $name,
            'legislationIdentifier' => $this->nonBlankString($unit->canonical_path)
                ?? $this->nonBlankString($unit->label),
            'description' => $this->nonBlankString($unit->summary),
            'url' => $this->nonBlankString($unit->source_url)
                ?? $this->nonBlankString($unit->legalAct?->source_url),
            'legislationJurisdiction' => 'PL',
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  Collection<int, LegalUnit>  $legalUnits
     * @return array<string, mixed>
     */
    protected function legalUnitListSchema(string $listId, Collection $legalUnits): array
    {
        return [
            '@id' => $listId,
            '@type' => 'ItemList',
            'name' => 'Podstawa prawna',
            'numberOfItems' => $legalUnits->count(),
            'itemListElement' => $legalUnits
                ->values()
                ->map(fn (LegalUnit $unit, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $this->nonBlankString($unit->source_url),
                    'name' => $this->legalUnitName($unit),
                    'item' => [
                        '@id' => $this->schemaIds->legalUnit($unit),
                    ],
                ])
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, QuestionLegalReference>  $questionReferences
     * @return array<string, mixed>
     */
    protected function questionListSchema(string $listId, Collection $questionReferences): array
    {
        return [
            '@id' => $listId,
            '@type' => 'ItemList',
            'name' => 'Powiązane pytania egzaminacyjne',
            'numberOfItems' => $questionReferences->count(),
            'itemListElement' => $questionReferences
                ->values()
                ->map(function (QuestionLegalReference $reference, int $index): ?array {
                    if (! $reference->question instanceof Question) {
                        return null;
                    }

                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => $this->questionUrl($reference->question),
                        'name' => $this->questionName($reference->question),
                        'item' => [
                            '@id' => $this->schemaIds->publicQuestionEntity($reference->question),
                        ],
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function questionSchema(QuestionLegalReference $reference): ?array
    {
        if (! $reference->question instanceof Question) {
            return null;
        }

        $question = $reference->question;

        return array_filter([
            '@id' => $this->schemaIds->publicQuestionEntity($question),
            '@type' => 'Question',
            'name' => $this->questionName($question),
            'url' => $this->questionUrl($question),
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
        return $this->siteIdentitySchema->organization();
    }

    /**
     * @return array<string, mixed>
     */
    protected function websiteSchema(string $websiteId, string $organizationId): array
    {
        return $this->siteIdentitySchema->website();
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    protected function breadcrumbSchema(array $breadcrumbs, string $breadcrumbId): array
    {
        $schema = $this->breadcrumbs->toSchema($breadcrumbs);
        unset($schema['@context']);
        $schema['@id'] = $breadcrumbId;

        return $schema;
    }

    protected function legalContentDescription(LegalContentPage $page): string
    {
        foreach ([$page->meta_description, $page->intro, $page->summary, $page->title] as $value) {
            $text = $this->nonBlankString($value);

            if ($text !== null) {
                return Str::squish(strip_tags($text));
            }
        }

        return 'Opracowanie przepisu drogowego.';
    }

    protected function legalUnitName(LegalUnit $unit): ?string
    {
        $actName = $this->nonBlankString($unit->legalAct?->short_title)
            ?? $this->nonBlankString($unit->legalAct?->title);
        $label = $this->nonBlankString($unit->label);
        $title = $this->nonBlankString($unit->title);
        $name = trim(implode(' ', array_filter([$actName, $label])));

        if ($name === '') {
            $name = $title ?? '';
        }

        return $name !== '' ? $name : null;
    }

    protected function questionName(Question $question): string
    {
        $prompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));

        return $prompt !== '' ? $prompt : 'Pytanie '.$this->displayExternalId($question->external_id);
    }

    protected function questionUrl(Question $question): string
    {
        return route('public.questions.show', [
            'externalId' => $this->publicQuestionCatalogService->publicExternalIdForQuestion($question),
            'slug' => $this->publicQuestionCatalogService->slugForQuestion($question),
        ]);
    }

    protected function displayExternalId(mixed $externalId): string
    {
        $value = trim((string) $externalId);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, ':')) {
            $suffix = trim(Str::afterLast($value, ':'));

            return $suffix !== '' ? $suffix : $value;
        }

        return $value;
    }

    protected function nonBlankString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
