<?php

namespace App\Support;

use App\Models\LegalContentPage;
use App\Models\LegalUnit;
use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionAudioAsset;
use App\Models\QuestionLegalReference;
use App\Models\QuestionTopic;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicQuestionSchemaService
{
    public function __construct(
        protected PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
        protected QuestionTextFormatter $questionTextFormatter,
        protected QuestionVideoSeoDescriptionService $questionVideoSeoDescriptionService,
        protected PublicUrlResolver $publicUrlResolver,
        protected SchemaIds $schemaIds,
        protected SchemaRenderer $schemaRenderer,
    ) {}

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  Collection<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    public function hub(array $breadcrumbs, Collection $categories): array
    {
        $canonicalUrl = route('public.questions.hub');
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $datasetId = $this->schemaIds->publicQuestionDataset();
        $categoryListId = $this->schemaIds->publicQuestionHubCategoryList();
        $categoryReferences = $categories
            ->map(fn (array $category): array => ['@id' => $this->categoryEntityIdFromCard($category)])
            ->values()
            ->all();

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => 'Oficjalna baza pytań na prawo jazdy',
                'url' => $canonicalUrl,
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
                    '@id' => $datasetId,
                ],
            ],
            $this->datasetSchema($datasetId, $organizationId, $categoryReferences),
            $this->categoryListSchema($categoryListId, $categories),
            ...$categories
                ->map(fn (array $category): array => $this->categoryDefinedTermSchemaFromCard($category, $datasetId))
                ->all(),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    public function category(LicenseCategory $category, array $breadcrumbs, array $questions): array
    {
        $canonicalUrl = route('public.questions.category', $category->slug);
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $datasetId = $this->schemaIds->publicQuestionDataset();
        $categoryId = $this->schemaIds->publicQuestionCategoryEntity($category);
        $questionListId = $this->schemaIds->publicQuestionCategoryQuestionList($category);
        $questionReferences = $this->questionReferencesFromListItems($questions);

        return $this->schemaRenderer->graph([
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            [
                '@id' => $webPageId,
                '@type' => 'CollectionPage',
                'name' => 'Oficjalna baza pytań kategorii '.$category->code,
                'url' => $canonicalUrl,
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
                    '@id' => $questionListId,
                ],
            ],
            $this->datasetSchema($datasetId, $organizationId, [['@id' => $categoryId]]),
            $this->categoryDefinedTermSchema($category, $datasetId, $questionReferences),
            $this->questionListSchema($questionListId, $questions),
        ]);
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @param  list<array<string, mixed>>  $options
     * @param  iterable<QuestionLegalReference>  $legalReferences
     * @param  array<string, mixed>  $questionAnswerStats
     * @return array<string, mixed>
     */
    public function question(
        Question $question,
        array $breadcrumbs,
        string $canonicalUrl,
        array $options,
        string $acceptedAnswerText,
        ?string $imageUrl = null,
        array $mediaPayload = [],
        array $audioPayload = [],
        ?LicenseCategory $primaryCategory = null,
        iterable $legalReferences = [],
        array $questionAnswerStats = [],
    ): array {
        $plainPrompt = $this->questionTextFormatter->plainText($question->prompt);
        $displayExternalId = $this->displayExternalId($question->external_id);
        $organizationId = $this->schemaIds->organization();
        $websiteId = $this->schemaIds->website();
        $webPageId = $this->schemaIds->fragment($canonicalUrl, 'webpage');
        $questionId = $this->schemaIds->fragment($canonicalUrl, 'question');
        $breadcrumbId = $this->schemaIds->fragment($canonicalUrl, 'breadcrumb');
        $datasetId = $this->schemaIds->publicQuestionDataset();
        $learningResourceId = $this->schemaIds->publicQuestionLearningResource($canonicalUrl);
        $category = $this->resolveQuestionCategory($question, $primaryCategory);
        $categoryId = $category instanceof LicenseCategory
            ? $this->schemaIds->publicQuestionCategoryEntity($category)
            : null;
        $topic = $this->resolveQuestionTopic($question);
        $topicId = $topic instanceof QuestionTopic
            ? $this->schemaIds->publicQuestionTopicEntity($topic)
            : null;
        $imageObject = $this->imageObject($canonicalUrl, $webPageId, $imageUrl, $mediaPayload);
        $imageObjectId = is_array($imageObject) && is_string($imageObject['@id'] ?? null)
            ? $imageObject['@id']
            : null;
        $legalUnitNodes = $this->legalUnitSchemas($legalReferences);
        $about = array_values(array_filter([
            $categoryId !== null ? ['@id' => $categoryId] : null,
            $topicId !== null ? ['@id' => $topicId] : null,
            ...collect($legalUnitNodes)
                ->pluck('@id')
                ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
                ->map(fn (string $id): array => ['@id' => $id])
                ->all(),
        ]));

        $questionSchema = array_filter([
            '@id' => $questionId,
            '@type' => 'Question',
            'name' => $plainPrompt !== '' ? $plainPrompt : 'Pytanie '.$displayExternalId,
            'text' => $plainPrompt,
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'isPartOf' => [
                '@id' => $categoryId ?? $datasetId,
            ],
            'about' => $about !== [] ? $about : null,
            'image' => $imageObjectId !== null ? [
                '@id' => $imageObjectId,
            ] : null,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $acceptedAnswerText,
                'url' => $canonicalUrl.'#answer',
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $answerStatsSchema = $this->questionAnswerStatsSchema(
            $canonicalUrl,
            $questionId,
            $webPageId,
            $websiteId,
            $organizationId,
            $displayExternalId,
            $plainPrompt,
            $questionAnswerStats,
        );
        $answerStatsDataset = $answerStatsSchema['dataset'];
        $answerStatsDatasetId = is_array($answerStatsDataset) && is_string($answerStatsDataset['@id'] ?? null)
            ? $answerStatsDataset['@id']
            : null;

        if ($answerStatsDatasetId !== null) {
            $questionSchema['interactionStatistic'] = [
                '@type' => 'InteractionCounter',
                'name' => 'Liczba odpowiedzi kursantów uwzględniona w publicznych statystykach pytania',
                'interactionType' => [
                    '@type' => 'ChooseAction',
                ],
                'interactionService' => [
                    '@id' => $websiteId,
                ],
                'userInteractionCount' => (int) ($questionAnswerStats['sample_count'] ?? 0),
            ];
        }

        $webPageSchema = array_filter([
            '@id' => $webPageId,
            '@type' => 'WebPage',
            'name' => 'Pytanie egzaminacyjne '.$displayExternalId,
            'url' => $canonicalUrl,
            'description' => $plainPrompt,
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
                '@id' => $questionId,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        if ($imageObjectId !== null) {
            $webPageSchema['primaryImageOfPage'] = [
                '@id' => $imageObjectId,
            ];
        }

        $videoObject = $this->videoObject($question, $canonicalUrl, $webPageId, $mediaPayload);
        $audioObject = $this->audioObject($question, $canonicalUrl, $webPageId, $audioPayload);
        $hasPart = [];
        $learningResourceParts = [
            ['@id' => $questionId],
        ];

        if ($videoObject !== null && is_string($videoObject['@id'] ?? null)) {
            $hasPart[] = ['@id' => $videoObject['@id']];
            $learningResourceParts[] = ['@id' => $videoObject['@id']];
        }

        if ($audioObject !== null && is_string($audioObject['@id'] ?? null)) {
            $hasPart[] = ['@id' => $audioObject['@id']];
            $learningResourceParts[] = ['@id' => $audioObject['@id']];
        }

        if ($answerStatsDatasetId !== null) {
            $hasPart[] = ['@id' => $answerStatsDatasetId];
            $learningResourceParts[] = ['@id' => $answerStatsDatasetId];
        }

        if (count($hasPart) === 1) {
            $webPageSchema['hasPart'] = $hasPart[0];
        } elseif (count($hasPart) > 1) {
            $webPageSchema['hasPart'] = $hasPart;
        }

        $graph = [
            $this->organizationSchema($organizationId),
            $this->websiteSchema($websiteId, $organizationId),
            $this->breadcrumbSchema($breadcrumbs, $breadcrumbId),
            $webPageSchema,
            $this->datasetSchema(
                $datasetId,
                $organizationId,
                $categoryId !== null ? [['@id' => $categoryId]] : [],
            ),
            $category instanceof LicenseCategory
                ? $this->categoryDefinedTermSchema($category, $datasetId, [['@id' => $questionId]])
                : null,
            $topic instanceof QuestionTopic
                ? $this->topicDefinedTermSchema($topic)
                : null,
            $questionSchema,
            $this->learningResourceSchema(
                $learningResourceId,
                $questionId,
                $datasetId,
                $canonicalUrl,
                $displayExternalId,
                $category,
                $learningResourceParts,
            ),
            $imageObject,
        ];

        if ($answerStatsDataset !== null) {
            $graph[] = $answerStatsDataset;
            array_push($graph, ...$answerStatsSchema['observations']);
        }

        if ($videoObject !== null) {
            $graph[] = $videoObject;
        }

        if ($audioObject !== null) {
            $graph[] = $audioObject;
        }

        foreach ($legalUnitNodes as $legalUnitNode) {
            $graph[] = $legalUnitNode;
        }

        return $this->schemaRenderer->graph($graph);
    }

    /**
     * @param  list<array<string, string>>  $hasPart
     * @return array<string, mixed>
     */
    protected function datasetSchema(string $datasetId, string $organizationId, array $hasPart = []): array
    {
        return [
            '@id' => $datasetId,
            '@type' => 'Dataset',
            'name' => 'Oficjalna baza pytań na prawo jazdy',
            'description' => 'Publiczna baza pytań egzaminacyjnych na prawo jazdy z odpowiedziami, wyjaśnieniami, kategoriami oraz podstawami prawnymi tam, gdzie zostały zweryfikowane.',
            'url' => route('public.questions.hub'),
            'inLanguage' => 'pl-PL',
            'creator' => [
                '@id' => $organizationId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'hasPart' => $hasPart !== [] ? $hasPart : null,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    protected function categoryListSchema(string $categoryListId, Collection $categories): array
    {
        return [
            '@id' => $categoryListId,
            '@type' => 'ItemList',
            'numberOfItems' => $categories->count(),
            'itemListElement' => $categories
                ->values()
                ->map(fn (array $category, int $index): array => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $category['url'] ?? null,
                    'name' => $category['name'] ?? null,
                    'description' => $category['description'] ?? null,
                    'item' => [
                        '@id' => $this->categoryEntityIdFromCard($category),
                    ],
                ], fn (mixed $value): bool => $value !== null && $value !== ''))
                ->all(),
        ];
    }

    /**
     * @param  list<array<string, string>>  $hasPart
     * @return array<string, mixed>
     */
    protected function categoryDefinedTermSchema(LicenseCategory $category, string $datasetId, array $hasPart = []): array
    {
        return array_filter([
            '@id' => $this->schemaIds->publicQuestionCategoryEntity($category),
            '@type' => 'DefinedTerm',
            'name' => $category->name ?: 'Kategoria '.$category->code,
            'termCode' => (string) $category->code,
            'description' => $category->description,
            'url' => route('public.questions.category', $category->slug),
            'isPartOf' => [
                '@id' => $datasetId,
            ],
            'hasPart' => $hasPart !== [] ? $hasPart : null,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function categoryDefinedTermSchemaFromCard(array $category, string $datasetId): array
    {
        return array_filter([
            '@id' => $this->categoryEntityIdFromCard($category),
            '@type' => 'DefinedTerm',
            'name' => $category['name'] ?? null,
            'termCode' => $category['code'] ?? null,
            'description' => $category['description'] ?? null,
            'url' => $category['url'] ?? null,
            'isPartOf' => [
                '@id' => $datasetId,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    protected function questionListSchema(string $questionListId, array $questions): array
    {
        return [
            '@id' => $questionListId,
            '@type' => 'ItemList',
            'numberOfItems' => count($questions),
            'itemListElement' => array_map(
                function (array $question, int $index): array {
                    $url = (string) ($question['url'] ?? '');
                    $canonicalUrl = (string) ($question['canonical_url'] ?? $url);
                    $name = 'Pytanie '.($question['display_external_id'] ?? $question['external_id']);

                    return [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'url' => $url,
                        'name' => $name,
                        'item' => [
                            '@id' => $this->schemaIds->fragment($canonicalUrl, 'question'),
                            '@type' => 'Question',
                            'name' => $name,
                            'url' => $canonicalUrl,
                        ],
                    ];
                },
                $questions,
                array_keys($questions),
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return list<array<string, string>>
     */
    protected function questionReferencesFromListItems(array $questions): array
    {
        return collect($questions)
            ->map(fn (array $question): mixed => $question['canonical_url'] ?? $question['url'] ?? null)
            ->filter(fn (mixed $url): bool => is_string($url) && $url !== '')
            ->map(fn (string $url): array => ['@id' => $this->schemaIds->fragment($url, 'question')])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, string>>  $hasPart
     * @return array<string, mixed>
     */
    protected function learningResourceSchema(
        string $learningResourceId,
        string $questionId,
        string $datasetId,
        string $canonicalUrl,
        string $displayExternalId,
        ?LicenseCategory $category,
        array $hasPart,
    ): array {
        $categoryCode = $category instanceof LicenseCategory ? trim((string) $category->code) : '';

        return array_filter([
            '@id' => $learningResourceId,
            '@type' => 'LearningResource',
            'name' => 'Pytanie '.$displayExternalId,
            'url' => $canonicalUrl,
            'learningResourceType' => 'Practice Problem',
            'educationalLevel' => 'Egzamin teoretyczny na prawo jazdy',
            'educationalUse' => 'practice',
            'teaches' => $categoryCode !== '' ? 'Prawo jazdy kategorii '.$categoryCode : 'Prawo jazdy',
            'inLanguage' => 'pl-PL',
            'isAccessibleForFree' => true,
            'about' => [
                '@id' => $questionId,
            ],
            'hasPart' => $hasPart,
            'isPartOf' => [
                '@id' => $datasetId,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $mediaPayload
     * @return array<string, mixed>|null
     */
    protected function imageObject(string $canonicalUrl, string $webPageId, ?string $imageUrl, array $mediaPayload): ?array
    {
        $imageUrl = $this->nonBlankString($imageUrl);

        if ($imageUrl === null) {
            return null;
        }

        $mediaKind = (string) ($mediaPayload['kind'] ?? '');

        return array_filter([
            '@id' => $this->schemaIds->publicQuestionImage($canonicalUrl),
            '@type' => 'ImageObject',
            'url' => $imageUrl,
            'contentUrl' => $imageUrl,
            'caption' => $this->nonBlankString($mediaPayload['alt_text'] ?? null),
            'encodingFormat' => $mediaKind === 'image'
                ? $this->nonBlankString($mediaPayload['mime_type'] ?? null)
                : null,
            'width' => $this->positiveInt($mediaPayload['width'] ?? null),
            'height' => $this->positiveInt($mediaPayload['height'] ?? null),
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  iterable<QuestionLegalReference>  $legalReferences
     * @return list<array<string, mixed>>
     */
    protected function legalUnitSchemas(iterable $legalReferences): array
    {
        return collect($legalReferences)
            ->map(fn (mixed $reference): ?array => $reference instanceof QuestionLegalReference
                ? $this->legalUnitSchema($reference)
                : null)
            ->filter()
            ->unique('@id')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function legalUnitSchema(QuestionLegalReference $reference): ?array
    {
        if (
            $reference->status !== QuestionLegalReference::STATUS_VERIFIED
            || $reference->verified_at === null
            || ! $reference->legalUnit instanceof LegalUnit
            || $reference->legalUnit->status !== LegalUnit::STATUS_VERIFIED
        ) {
            return null;
        }

        $legalUnit = $reference->legalUnit;

        return array_filter([
            '@id' => $this->schemaIds->publicQuestionLegalUnit($legalUnit),
            '@type' => 'Legislation',
            'name' => $this->legalUnitName($legalUnit),
            'legislationIdentifier' => $this->nonBlankString($legalUnit->canonical_path)
                ?? $this->nonBlankString($legalUnit->label),
            'text' => $this->nonBlankString($legalUnit->official_excerpt),
            'url' => $this->legalUnitUrl($reference),
            'legislationJurisdiction' => 'PL',
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function topicDefinedTermSchema(?QuestionTopic $topic): ?array
    {
        if (! $topic instanceof QuestionTopic || ! $topic->is_active) {
            return null;
        }

        return array_filter([
            '@id' => $this->schemaIds->publicQuestionTopicEntity($topic),
            '@type' => 'DefinedTerm',
            'name' => $topic->name,
            'termCode' => $topic->key,
            'description' => $topic->description,
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $questionAnswerStats
     * @return array{dataset: array<string, mixed>|null, observations: list<array<string, mixed>>}
     */
    protected function questionAnswerStatsSchema(
        string $canonicalUrl,
        string $questionId,
        string $webPageId,
        string $websiteId,
        string $organizationId,
        string $displayExternalId,
        string $plainPrompt,
        array $questionAnswerStats,
    ): array {
        if (($questionAnswerStats['available'] ?? false) !== true) {
            return [
                'dataset' => null,
                'observations' => [],
            ];
        }

        $sampleCount = $this->positiveInt($questionAnswerStats['sample_count'] ?? null);
        $items = collect($questionAnswerStats['items'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values();

        if ($sampleCount === null || $items->isEmpty()) {
            return [
                'dataset' => null,
                'observations' => [],
            ];
        }

        $statsDatasetId = $this->schemaIds->fragment($canonicalUrl, 'answer-statistics');
        $updatedAt = $this->schemaDate($questionAnswerStats['updated_at'] ?? null);
        $windowDays = $this->positiveInt($questionAnswerStats['window_days'] ?? null);
        $difficultyLabel = $this->nonBlankString($questionAnswerStats['difficulty_label'] ?? null);
        $windowLabel = $this->nonBlankString($questionAnswerStats['window_label'] ?? null)
            ?? ($windowDays !== null ? 'Ostatnie '.$windowDays.' dni' : null);
        $datasetName = $this->questionAnswerStatsDatasetName($displayExternalId, $plainPrompt);
        $distributionSummary = $items
            ->map(function (array $item): string {
                $label = $this->nonBlankString($item['label'] ?? null) ?? '';
                $percent = $this->nonNegativeInt($item['percent'] ?? null) ?? 0;

                return trim($label.' '.$percent.'%');
            })
            ->filter(fn (string $summary): bool => $summary !== '')
            ->implode(', ');

        $observations = $items
            ->map(function (array $item) use ($statsDatasetId, $questionId, $updatedAt, $displayExternalId): ?array {
                $key = $this->answerStatsOptionKey($item['key'] ?? null);
                $label = $this->nonBlankString($item['label'] ?? null);
                $count = $this->nonNegativeInt($item['count'] ?? null);
                $percent = $this->nonNegativeInt($item['percent'] ?? null);

                if ($key === null || $label === null || $count === null || $percent === null) {
                    return null;
                }

                $isCorrect = (bool) ($item['is_correct'] ?? false);

                return array_filter([
                    '@id' => $statsDatasetId.'-'.$key,
                    '@type' => 'Observation',
                    'name' => 'Odsetek odpowiedzi '.$label.' dla pytania '.$displayExternalId,
                    'description' => $label.' wybrało '.$percent.'% kursantów w publicznych statystykach tego pytania. '
                        .$this->answerStatsCorrectnessLabel($isCorrect),
                    'observationAbout' => [
                        '@id' => $questionId,
                    ],
                    'isPartOf' => [
                        '@id' => $statsDatasetId,
                    ],
                    'variableMeasured' => [
                        '@type' => 'StatisticalVariable',
                        'name' => 'Odsetek wyboru odpowiedzi '.$label,
                    ],
                    'value' => $percent,
                    'unitText' => '%',
                    'observationDate' => $updatedAt,
                    'measurementMethod' => 'Zagregowane odpowiedzi kursantów w serwisie PrawkoNaRaz.pl.',
                ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
            })
            ->filter()
            ->values()
            ->all();

        if ($observations === []) {
            return [
                'dataset' => null,
                'observations' => [],
            ];
        }

        $variableMeasured = collect([
            [
                '@type' => 'PropertyValue',
                'name' => 'Łączna liczba odpowiedzi w próbce',
                'value' => $sampleCount,
            ],
            [
                '@type' => 'PropertyValue',
                'name' => 'Okno analizy',
                'value' => $windowLabel,
            ],
            [
                '@type' => 'PropertyValue',
                'name' => 'Poziom trudności',
                'value' => $difficultyLabel,
            ],
        ])
            ->filter(fn (array $property): bool => $property['value'] !== null && $property['value'] !== '')
            ->values()
            ->all();

        $dataset = array_filter([
            '@id' => $statsDatasetId,
            '@type' => 'Dataset',
            'name' => $datasetName,
            'description' => trim('Publiczny, zagregowany rozkład odpowiedzi kursantów dla tego pytania egzaminacyjnego. '
                .($distributionSummary !== '' ? 'Rozkład: '.$distributionSummary.'. ' : '')
                .'Próbka: '.$sampleCount.' odpowiedzi.'
                .($difficultyLabel !== null ? ' Poziom trudności: '.$difficultyLabel.'.' : '')),
            'url' => $statsDatasetId,
            'inLanguage' => 'pl-PL',
            'creator' => [
                '@id' => $organizationId,
            ],
            'publisher' => [
                '@id' => $organizationId,
            ],
            'isPartOf' => [
                '@id' => $websiteId,
            ],
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'about' => [
                '@id' => $questionId,
            ],
            'dateModified' => $updatedAt,
            'temporalCoverage' => $windowLabel,
            'measurementTechnique' => 'Dzienne agregaty wyborów odpowiedzi kursantów, publikowane po osiągnięciu minimalnej próbki.',
            'variableMeasured' => $variableMeasured,
            'hasPart' => collect($observations)
                ->pluck('@id')
                ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
                ->map(fn (string $id): array => ['@id' => $id])
                ->values()
                ->all(),
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);

        return [
            'dataset' => $dataset,
            'observations' => $observations,
        ];
    }

    protected function answerStatsOptionKey(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['a', 'b'], true) ? $value : null;
    }

    protected function questionAnswerStatsDatasetName(string $displayExternalId, string $plainPrompt): string
    {
        $prompt = Str::squish($plainPrompt);
        $prefix = 'Statystyki odpowiedzi kursantów dla pytania '.$displayExternalId;

        return $prompt !== ''
            ? $prefix.': '.$prompt
            : $prefix;
    }

    protected function answerStatsCorrectnessLabel(bool $isCorrect): string
    {
        return $isCorrect ? 'To poprawna odpowiedź.' : 'To nie jest poprawna odpowiedź.';
    }

    protected function resolveQuestionCategory(Question $question, ?LicenseCategory $primaryCategory): ?LicenseCategory
    {
        if ($primaryCategory instanceof LicenseCategory) {
            return $primaryCategory;
        }

        $question->loadMissing('licenseCategory:id,code,slug,name,description,sort_order');

        return $question->licenseCategory instanceof LicenseCategory
            ? $question->licenseCategory
            : null;
    }

    protected function resolveQuestionTopic(Question $question): ?QuestionTopic
    {
        $question->loadMissing('questionTopic:id,key,name,description,is_active');

        return $question->questionTopic instanceof QuestionTopic && $question->questionTopic->is_active
            ? $question->questionTopic
            : null;
    }

    protected function categoryEntityIdFromCard(array $category): string
    {
        $slug = trim((string) ($category['slug'] ?? ''));

        if ($slug === '') {
            $path = parse_url((string) ($category['url'] ?? ''), PHP_URL_PATH);
            $slug = is_string($path) ? trim((string) Str::afterLast($path, '/')) : '';
        }

        return $this->schemaIds->publicQuestionCategoryEntityFromSlug($slug);
    }

    protected function legalUnitName(LegalUnit $legalUnit): string
    {
        $actName = trim((string) ($legalUnit->legalAct?->short_title ?: $legalUnit->legalAct?->title));
        $label = trim((string) $legalUnit->label);
        $title = trim((string) $legalUnit->title);
        $name = trim(implode(' ', array_filter([$actName, $label])));

        if ($name === '') {
            $name = $title;
        }

        return $name !== '' ? $name : 'Przepis prawa';
    }

    protected function legalUnitUrl(QuestionLegalReference $reference): ?string
    {
        if (
            $reference->contentPage instanceof LegalContentPage
            && $reference->contentPage->isPubliclyVisible()
        ) {
            return route('public.regulations.show', $reference->contentPage->slug);
        }

        $legalUnit = $reference->legalUnit;

        return $legalUnit instanceof LegalUnit
            ? $this->nonBlankString($legalUnit->source_url)
                ?? $this->nonBlankString($legalUnit->legalAct?->source_url)
            : null;
    }

    /**
     * @param  array<string, mixed>  $mediaPayload
     * @return array<string, mixed>|null
     */
    protected function videoObject(Question $question, string $canonicalUrl, string $webPageId, array $mediaPayload): ?array
    {
        if (($mediaPayload['kind'] ?? null) !== 'video') {
            return null;
        }

        $contentUrl = $this->nonBlankString($mediaPayload['url'] ?? null);
        $thumbnailUrl = $this->nonBlankString($mediaPayload['poster_url'] ?? null);
        $uploadedAt = $question->published_at ?? $question->created_at ?? $question->updated_at;

        if ($contentUrl === null || $thumbnailUrl === null || $uploadedAt === null) {
            return null;
        }

        $plainPrompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));
        $displayExternalId = $this->displayExternalId($question->external_id);
        $name = trim('Film do pytania '.$displayExternalId.($plainPrompt !== '' ? ': '.$plainPrompt : ''));
        $description = $this->questionVideoSeoDescriptionService->forQuestion($question);
        $duration = $this->iso8601Duration($mediaPayload['duration_seconds'] ?? null);
        $videoObjectId = $this->videoObjectId($canonicalUrl, $displayExternalId);

        return array_filter([
            '@id' => $videoObjectId,
            '@type' => 'VideoObject',
            'name' => $name,
            'description' => $description,
            'thumbnailUrl' => [$thumbnailUrl],
            'uploadDate' => $uploadedAt->toIso8601String(),
            'contentUrl' => $contentUrl,
            'url' => $canonicalUrl,
            'duration' => $duration,
            'encodingFormat' => $this->nonBlankString($mediaPayload['mime_type'] ?? null),
            'width' => $this->positiveInt($mediaPayload['width'] ?? null),
            'height' => $this->positiveInt($mediaPayload['height'] ?? null),
            'isFamilyFriendly' => true,
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'potentialAction' => [
                '@type' => 'WatchAction',
                'target' => $canonicalUrl,
            ],
        ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param  array<string, mixed>  $audioPayload
     * @return array<string, mixed>|null
     */
    protected function audioObject(Question $question, string $canonicalUrl, string $webPageId, array $audioPayload): ?array
    {
        $questionAudio = $audioPayload[QuestionAudioAsset::TYPE_QUESTION] ?? null;

        if (! is_array($questionAudio)) {
            return null;
        }

        $contentUrl = $this->nonBlankString($questionAudio['url'] ?? null);
        $transcript = $this->nonBlankString($questionAudio['transcript'] ?? null);
        $uploadedAt = $question->published_at ?? $question->created_at ?? $question->updated_at;

        if ($contentUrl === null || $transcript === null || $uploadedAt === null) {
            return null;
        }

        $displayExternalId = $this->displayExternalId($question->external_id);
        $plainPrompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));
        $name = trim('Audio do pytania '.$displayExternalId.($plainPrompt !== '' ? ': '.$plainPrompt : ''));

        return array_filter([
            '@id' => $this->schemaIds->publicQuestionAudio($canonicalUrl),
            '@type' => 'AudioObject',
            'name' => $name !== '' ? $name : 'Audio do pytania egzaminacyjnego',
            'description' => 'Nagranie audio odczytujące treść pytania egzaminacyjnego '.$displayExternalId.'.',
            'contentUrl' => $contentUrl,
            'url' => $canonicalUrl,
            'uploadDate' => $uploadedAt->toIso8601String(),
            'duration' => $this->iso8601Duration($questionAudio['duration_seconds'] ?? null),
            'encodingFormat' => $this->nonBlankString($questionAudio['encoding_format'] ?? null),
            'transcript' => $transcript,
            'inLanguage' => 'pl-PL',
            'isFamilyFriendly' => true,
            'mainEntityOfPage' => [
                '@id' => $webPageId,
            ],
            'potentialAction' => [
                '@type' => 'ListenAction',
                'target' => $canonicalUrl,
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
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('public.questions.hub').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  list<array{label: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    protected function breadcrumbSchema(array $breadcrumbs, string $breadcrumbId): array
    {
        $schema = $this->publicQuestionBreadcrumbs->toSchema($breadcrumbs);
        unset($schema['@context']);
        $schema['@id'] = $breadcrumbId;

        return $schema;
    }

    protected function videoObjectId(string $canonicalUrl, string $displayExternalId): string
    {
        $suffix = Str::slug($displayExternalId);

        return $canonicalUrl.'#video'.($suffix !== '' ? '-'.$suffix : '');
    }

    protected function iso8601Duration(mixed $seconds): ?string
    {
        if (! is_numeric($seconds)) {
            return null;
        }

        $remaining = max(0, (int) $seconds);

        if ($remaining <= 0) {
            return null;
        }

        $hours = intdiv($remaining, 3600);
        $remaining %= 3600;
        $minutes = intdiv($remaining, 60);
        $seconds = $remaining % 60;
        $duration = 'PT';

        if ($hours > 0) {
            $duration .= $hours.'H';
        }

        if ($minutes > 0) {
            $duration .= $minutes.'M';
        }

        if ($seconds > 0 || $duration === 'PT') {
            $duration .= $seconds.'S';
        }

        return $duration;
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

    protected function nonNegativeInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value >= 0 ? $value : null;
    }

    protected function schemaDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $this->nonBlankString($value);
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
}
