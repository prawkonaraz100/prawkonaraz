<?php

namespace App\Support;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\QuestionMedia;
use App\Models\TrafficSign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicQuestionCatalogService
{
    protected const AUXILIARY_PUBLIC_EXTERNAL_ID_PREFIX = 'pytanie-pomocnicze';

    protected ?Collection $visibleCategoriesCache = null;

    protected ?Collection $canonicalQuestionSitemapRowsCache = null;

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $rawExternalIdsByDisplayExternalIdCache = null;

    /**
     * @var array<string, string>|null
     */
    protected ?array $rawExternalIdsByAuxiliaryPublicExternalIdCache = null;

    public function __construct(
        protected StudyContextService $studyContextService,
        protected QuestionMediaPayloadBuilder $questionMediaPayloadBuilder,
        protected QuestionTextFormatter $questionTextFormatter,
        protected QuestionExplanationAssetPayloadBuilder $questionExplanationAssetPayloadBuilder,
        protected SharedQuestionExplanationAssetResolver $sharedQuestionExplanationAssetResolver,
        protected MediaUrlResolver $mediaUrlResolver,
        protected QuestionVideoSeoDescriptionService $questionVideoSeoDescriptionService,
        protected PublicQuestionExplanationService $publicQuestionExplanationService,
    ) {}

    /**
     * @return Collection<int, LicenseCategory>
     */
    public function visibleCategories(): Collection
    {
        if ($this->visibleCategoriesCache instanceof Collection) {
            return $this->visibleCategoriesCache;
        }

        return $this->visibleCategoriesCache = $this->studyContextService->visibleCategoriesQuery()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['id', 'code', 'slug', 'name', 'description', 'sort_order']);
    }

    public function findVisibleCategoryBySlug(string $categorySlug): ?LicenseCategory
    {
        return $this->visibleCategories()->firstWhere('slug', $categorySlug);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function categoryCards(): Collection
    {
        $questionCounts = $this->baseQuery()
            ->get(['license_category_id', 'external_id'])
            ->groupBy('license_category_id')
            ->map(fn (Collection $questions): int => $questions
                ->pluck('external_id')
                ->filter(fn (mixed $externalId): bool => filled($externalId))
                ->unique()
                ->count());

        return $this->visibleCategories()
            ->map(function (LicenseCategory $category) use ($questionCounts): array {
                $marketingCopy = $this->categoryMarketingCopy($category);

                return [
                    'id' => $category->getKey(),
                    'code' => $category->code,
                    'slug' => $category->slug,
                    'name' => $category->name ?: 'Kategoria '.$category->code,
                    'description' => $category->description,
                    'title' => 'Kategoria '.$category->code,
                    'vehicle_label' => $marketingCopy['vehicle_label'],
                    'summary' => $marketingCopy['summary'],
                    'cta_label' => 'Zobacz pytania kat. '.$category->code,
                    'questions_count' => (int) ($questionCounts[$category->getKey()] ?? 0),
                    'url' => route('public.questions.category', $category->slug),
                ];
            })
            ->values();
    }

    public function canonicalQuestionsCount(): int
    {
        return $this->baseQuery()
            ->get(['external_id'])
            ->pluck('external_id')
            ->filter(fn (mixed $externalId): bool => filled($externalId))
            ->unique()
            ->count();
    }

    public function publicQuestionsQuery(): Builder
    {
        return Question::query()
            ->where('is_active', true)
            ->readyForDelivery()
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->whereIn('license_category_id', $this->visibleCategoryIds());
    }

    /**
     * @return Collection<int, Question>
     */
    public function representativeQuestionsForCategory(LicenseCategory $category): Collection
    {
        $questions = $this->baseQuery()
            ->where('license_category_id', $category->getKey())
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->withCount('media')
            ->get();

        return $this->groupRepresentatives($questions);
    }

    /**
     * @return Collection<int, Question>
     */
    public function questionGroup(string $externalId): Collection
    {
        return $this->baseQuery()
            ->where('external_id', trim($externalId))
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
                'referenceExplanationAsset.trafficSign',
            ])
            ->withCount('media')
            ->get()
            ->sortBy('id')
            ->values();
    }

    /**
     * @return Collection<int, Question>
     */
    public function questionGroupByExternalOrDisplayId(string $externalId): Collection
    {
        $questions = $this->questionGroup($externalId);

        if ($questions->isNotEmpty()) {
            return $questions;
        }

        $questions = $this->questionGroupByAuxiliaryPublicExternalId($externalId);

        return $questions->isNotEmpty()
            ? $questions
            : $this->questionGroupByDisplayExternalId($externalId);
    }

    public function findCanonicalUrlByExternalId(string $externalId): ?string
    {
        $question = $this->findCanonicalQuestionByExternalId($externalId);

        return $question instanceof Question ? $this->questionUrl($question) : null;
    }

    public function findCanonicalQuestionByExternalId(string $externalId): ?Question
    {
        $questions = $this->questionGroupByExternalOrDisplayId($externalId);

        if ($questions->isEmpty()) {
            return null;
        }

        return $this->representativeQuestion($questions);
    }

    /**
     * Resolve canonical public questions in one query for the common exact-ID
     * path. Unusual display or auxiliary IDs retain the existing resolver as a
     * compatibility fallback.
     *
     * @param  iterable<mixed>  $externalIds
     * @return Collection<string, Question>
     */
    public function canonicalQuestionsByExternalIds(iterable $externalIds): Collection
    {
        $requestedIds = collect($externalIds)
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->filter()
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return collect();
        }

        $questionGroups = $this->baseQuery()
            ->whereIn('external_id', $requestedIds->all())
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
                'referenceExplanationAsset.trafficSign',
            ])
            ->withCount('media')
            ->get()
            ->groupBy(fn (Question $question): string => (string) $question->external_id);
        $resolved = collect();

        foreach ($requestedIds as $externalId) {
            $group = $questionGroups->get($externalId, collect());
            $question = $group->isNotEmpty()
                ? $this->representativeQuestion($group)
                : $this->findCanonicalQuestionByExternalId($externalId);

            if ($question instanceof Question) {
                $resolved->put($externalId, $question);
            }
        }

        $this->prepareQuestionListQuestions($resolved->values());

        return $resolved;
    }

    /**
     * Batch-load every relation used by question list cards and prime the
     * shared reference-asset cache to prevent per-card database lookups.
     *
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    public function prepareQuestionListQuestions(Collection $questions): Collection
    {
        $models = new EloquentCollection($questions
            ->filter(fn (mixed $question): bool => $question instanceof Question)
            ->unique(fn (Question $question): int => (int) $question->getKey())
            ->values()
            ->all());

        if ($models->isEmpty()) {
            return collect();
        }

        $models->loadMissing([
            'licenseCategory:id,code,slug,name,sort_order',
            'media' => fn ($query) => $query->orderBy('sort_order'),
            'referenceExplanationAsset.trafficSign',
        ]);
        $this->sharedQuestionExplanationAssetResolver->preloadForQuestions($models);

        return collect($models->all())->values();
    }

    public function displayExternalId(mixed $externalId): string
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

    /**
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, LicenseCategory>
     */
    public function categoriesForQuestions(Collection $questions): Collection
    {
        $officialOrder = array_flip($this->visibleCategories()->pluck('code')->all());

        return $questions
            ->map(fn (Question $question) => $question->licenseCategory)
            ->filter(fn (mixed $category): bool => $category instanceof LicenseCategory)
            ->unique('id')
            ->sort(function (LicenseCategory $left, LicenseCategory $right) use ($officialOrder): int {
                $comparisons = [
                    ($officialOrder[$left->code] ?? PHP_INT_MAX) <=> ($officialOrder[$right->code] ?? PHP_INT_MAX),
                    strcmp((string) $left->code, (string) $right->code),
                    $left->getKey() <=> $right->getKey(),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->values();
    }

    public function primaryCategoryForQuestions(Collection $questions): ?LicenseCategory
    {
        return $this->categoriesForQuestions($questions)->first();
    }

    public function slugForQuestion(Question $question): string
    {
        $slug = Str::slug($this->questionTextFormatter->plainText($question->prompt));

        return $slug !== ''
            ? $slug
            : 'pytanie-'.Str::slug((string) $question->external_id);
    }

    public function questionUrl(Question $question, ?LicenseCategory $category = null): string
    {
        $parameters = [
            'externalId' => $this->publicExternalIdForQuestion($question),
            'slug' => $this->slugForQuestion($question),
        ];

        if ($category instanceof LicenseCategory) {
            return route('public.questions.category.show', [
                'categorySlug' => $category->slug,
                ...$parameters,
            ]);
        }

        return route('public.questions.show', [
            ...$parameters,
        ]);
    }

    public function publicExternalIdForQuestion(Question $question): string
    {
        $rawExternalId = trim((string) $question->external_id);

        if ($rawExternalId === '') {
            return '';
        }

        if (! str_contains($rawExternalId, ':')) {
            return $rawExternalId;
        }

        $displayExternalId = $this->displayExternalId($rawExternalId);

        if ($displayExternalId === '' || $displayExternalId === $rawExternalId) {
            return $this->auxiliaryPublicExternalId($rawExternalId);
        }

        return $this->displayExternalIdIsAmbiguous($displayExternalId, $rawExternalId)
            ? $this->auxiliaryPublicExternalId($rawExternalId)
            : $displayExternalId;
    }

    /**
     * @return array<string, mixed>
     */
    public function primaryMediaPayload(Question $question): array
    {
        $payloads = $this->questionMediaPayloadBuilder->forQuestion($question->media ?? []);

        $payload = $payloads[0] ?? [
            'kind' => 'none',
            'url' => null,
            'full_url' => null,
            'thumb_url' => null,
            'poster_url' => null,
            'mime_type' => null,
            'width' => null,
            'height' => null,
            'variant' => null,
        ];

        $payload['alt_text'] = $this->questionMediaAltText($question, $payload);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $mediaPayload
     */
    public function questionMediaAltText(Question $question, array $mediaPayload = []): string
    {
        $displayExternalId = $this->displayExternalId($question->external_id);
        $plainPrompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));
        $mediaKind = (string) ($mediaPayload['kind'] ?? 'none');
        $prefix = match ($mediaKind) {
            'video' => 'Kadr z filmu do pytania '.$displayExternalId,
            'image' => 'Ilustracja do pytania '.$displayExternalId,
            default => 'Pytanie egzaminacyjne '.$displayExternalId,
        };

        if ($plainPrompt === '') {
            return $prefix;
        }

        return Str::limit($prefix.': '.$plainPrompt, 180, '...');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function referenceSignCard(Question $question): ?array
    {
        $question->loadMissing('referenceExplanationAsset.trafficSign');

        $asset = $this->sharedQuestionExplanationAssetResolver->resolveReferenceAsset($question);

        if (! $asset || ! $asset->is_active) {
            return null;
        }

        if ($asset->trafficSign instanceof TrafficSign) {
            $sign = $asset->trafficSign;
            $title = $sign->publicTitle();
            $description = Str::squish(strip_tags((string) (
                $sign->intro_definition
                    ?: $sign->meaning
                    ?: $sign->driver_behavior
                    ?: ''
            )));
            $imageUrl = $this->trafficSignImageUrl($sign);
            $altText = $sign->publicImageAlt();
            $url = $sign->isPubliclyVisible()
                ? route('traffic-signs.show', $sign)
                : null;

            return [
                'code' => $sign->publicCode(),
                'name' => (string) $sign->name,
                'title' => $title !== '' ? $title : 'Powiązany znak drogowy',
                'eyebrow' => 'Powiązany znak drogowy',
                'intro' => $sign->intro_definition,
                'description' => $description !== '' ? Str::limit($description, 360, '...') : null,
                'image_url' => $imageUrl,
                'image_alt' => $altText,
                'alt_text' => $altText,
                'url' => $url,
                'link_label' => $url !== null
                    ? 'Zobacz opis znaku'
                    : null,
            ];
        }

        $payload = $this->questionExplanationAssetPayloadBuilder->forAsset($asset);

        if ($payload === null) {
            return null;
        }

        return [
            'title' => filled($payload['title'] ?? null)
                ? (string) $payload['title']
                : 'Powiązany znak drogowy',
            'eyebrow' => 'Powiązany znak drogowy',
            'intro' => $payload['body'] ?? $payload['caption'] ?? null,
            'image_url' => $payload['image_url'] ?? null,
            'alt_text' => $payload['alt_text'] ?? 'Znak drogowy',
            'url' => null,
            'link_label' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildQuestionListItem(
        Question $question,
        ?LicenseCategory $contextCategory = null,
        bool $includeReferenceSign = false,
    ): array {
        $mediaPayload = $this->primaryMediaPayload($question);
        $thumbnailUrl = $mediaPayload['thumb_url']
            ?? $mediaPayload['poster_url']
            ?? $mediaPayload['url']
            ?? null;
        $canonicalUrl = $this->questionUrl($question);
        $url = $contextCategory instanceof LicenseCategory
            ? $this->questionUrl($question, $contextCategory)
            : $canonicalUrl;

        $item = [
            'external_id' => (string) $question->external_id,
            'display_external_id' => $this->displayExternalId($question->external_id),
            'prompt_html' => $this->questionTextFormatter->inlineHtml($question->prompt),
            'prompt_plain' => $this->questionTextFormatter->plainText($question->prompt),
            'url' => $url,
            'canonical_url' => $canonicalUrl,
            'category_code' => $contextCategory?->code ?? $question->licenseCategory?->code,
            'type_label' => $this->questionTypeLabel($question),
            'thumbnail_url' => $thumbnailUrl,
            'thumbnail_alt' => 'Miniatura pytania '.$this->displayExternalId($question->external_id),
            'updated_at' => $question->updated_at,
            'date_label' => $question->updated_at?->format('d.m.Y')
                ?? $question->published_at?->format('d.m.Y')
                ?? '-',
        ];

        if ($includeReferenceSign) {
            $item['reference_sign'] = $this->referenceSignCard($question);
        }

        return $item;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function answerOptions(Question $question): array
    {
        $correctAnswer = Str::lower((string) $question->correct_answer);

        if ($question->question_type === 'boolean') {
            return [
                [
                    'key' => 'a',
                    'label' => 'TAK',
                    'text_html' => $this->questionTextFormatter->inlineHtml($question->option_a ?: 'Tak'),
                    'text_plain' => $this->questionTextFormatter->plainText($question->option_a ?: 'Tak'),
                    'is_correct' => $correctAnswer === 'a',
                ],
                [
                    'key' => 'b',
                    'label' => 'NIE',
                    'text_html' => $this->questionTextFormatter->inlineHtml($question->option_b ?: 'Nie'),
                    'text_plain' => $this->questionTextFormatter->plainText($question->option_b ?: 'Nie'),
                    'is_correct' => $correctAnswer === 'b',
                ],
            ];
        }

        return collect([
            ['key' => 'a', 'label' => 'A', 'value' => $question->option_a],
            ['key' => 'b', 'label' => 'B', 'value' => $question->option_b],
            ['key' => 'c', 'label' => 'C', 'value' => $question->option_c],
        ])
            ->filter(fn (array $option): bool => filled($option['value']))
            ->map(fn (array $option): array => [
                'key' => $option['key'],
                'label' => $option['label'],
                'text_html' => $this->questionTextFormatter->inlineHtml((string) $option['value']),
                'text_plain' => $this->questionTextFormatter->plainText((string) $option['value']),
                'is_correct' => $correctAnswer === $option['key'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildQuestionShowModel(Question $question): array
    {
        return [
            'external_id' => (string) $question->external_id,
            'display_external_id' => $this->displayExternalId($question->external_id),
            'prompt_html' => $this->questionTextFormatter->inlineHtml($question->prompt),
            'prompt_plain' => $this->questionTextFormatter->plainText($question->prompt),
            'question_type' => (string) $question->question_type,
            'options' => $this->answerOptions($question),
            'explanation_html' => $this->questionTextFormatter->richHtml($question->explanation),
            'explanation_plain' => $this->questionTextFormatter->plainText($question->explanation),
        ];
    }

    /**
     * @return array{position: int|null, total: int, previous: array<string, mixed>|null, next: array<string, mixed>|null}
     */
    public function questionNavigation(Question $question, ?LicenseCategory $category): array
    {
        if (! $category instanceof LicenseCategory) {
            return [
                'position' => null,
                'total' => 0,
                'previous' => null,
                'next' => null,
            ];
        }

        $questions = $this->representativeQuestionsForCategory($category);
        $currentExternalId = (string) $question->external_id;
        $currentIndex = $questions->search(
            fn (Question $candidate): bool => (string) $candidate->external_id === $currentExternalId
        );

        if ($currentIndex === false) {
            $currentDisplayExternalId = $this->displayExternalId($currentExternalId);
            $currentIndex = $questions->search(
                fn (Question $candidate): bool => $this->displayExternalId($candidate->external_id) === $currentDisplayExternalId
            );
        }

        if ($currentIndex === false) {
            return [
                'position' => null,
                'total' => $questions->count(),
                'previous' => null,
                'next' => null,
            ];
        }

        $previousQuestion = $currentIndex > 0 ? $questions->get($currentIndex - 1) : null;
        $nextQuestion = $questions->get($currentIndex + 1);

        return [
            'position' => ((int) $currentIndex) + 1,
            'total' => $questions->count(),
            'previous' => $previousQuestion instanceof Question ? $this->buildQuestionListItem($previousQuestion, $category) : null,
            'next' => $nextQuestion instanceof Question ? $this->buildQuestionListItem($nextQuestion, $category) : null,
        ];
    }

    public function acceptedAnswerText(Question $question, ?string $explanationOverride = null): string
    {
        $correctOption = collect($this->answerOptions($question))
            ->firstWhere('is_correct', true);
        $answerText = (string) ($correctOption['text_plain'] ?? Str::upper((string) $question->correct_answer));
        $explanation = $explanationOverride !== null
            ? $this->questionTextFormatter->plainText($explanationOverride)
            : $this->questionTextFormatter->plainText($question->explanation);

        if ($explanation !== '') {
            $normalizedExplanation = Str::lower(Str::squish($explanation));
            $normalizedAnswer = Str::lower(Str::squish($answerText));

            if (
                $normalizedAnswer !== ''
                && preg_match('/^'.preg_quote($normalizedAnswer, '/').'[\\.,:;!?\\s]/u', $normalizedExplanation) === 1
            ) {
                return "Poprawna odpowiedź: {$explanation}";
            }
        }

        return trim($explanation !== ''
            ? "Poprawna odpowiedź: {$answerText}. {$explanation}"
            : "Poprawna odpowiedź: {$answerText}.");
    }

    public function questionTypeLabel(Question $question): string
    {
        return match ($question->question_type) {
            'boolean' => 'Tak / Nie',
            'single_choice' => 'Jednokrotny wybór',
            default => 'Pytanie egzaminacyjne',
        };
    }

    public function questionSourceLabel(Question $question): string
    {
        $source = trim((string) $question->source);

        if ($source === '') {
            return 'gov.pl';
        }

        $normalizedSource = Str::lower($source);

        if (
            str_contains($normalizedSource, 'gov')
            || str_contains($normalizedSource, 'government')
            || str_contains($normalizedSource, 'pj360')
        ) {
            return 'gov.pl';
        }

        return Str::headline(str_replace(['_', '-'], ' ', $source));
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    public function correctOptionLabel(array $options): ?string
    {
        $correctOption = collect($options)
            ->firstWhere('is_correct', true);

        if (! is_array($correctOption)) {
            return null;
        }

        $label = trim((string) ($correctOption['label'] ?? ''));

        if ($label !== '') {
            return $label;
        }

        $text = trim((string) ($correctOption['text_plain'] ?? ''));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    public function correctOptionSummary(Question $question, array $options): string
    {
        $correctOption = collect($options)
            ->firstWhere('is_correct', true);

        if (! is_array($correctOption)) {
            return 'Poprawna odpowiedź została zaznaczona poniżej.';
        }

        $label = trim((string) ($correctOption['label'] ?? ''));
        $text = trim((string) ($correctOption['text_plain'] ?? ''));

        if ($question->question_type === 'boolean') {
            return $label !== ''
                ? "Poprawna odpowiedź: {$label}."
                : 'Poprawna odpowiedź została zaznaczona poniżej.';
        }

        if ($label !== '' && $text !== '') {
            return "Poprawna odpowiedź: {$label} - {$text}.";
        }

        if ($text !== '') {
            return "Poprawna odpowiedź: {$text}.";
        }

        return 'Poprawna odpowiedź została zaznaczona poniżej.';
    }

    /**
     * @param  Collection<int, LicenseCategory>  $categories
     * @return Collection<int, Question>
     */
    public function relatedQuestions(Question $question, Collection $categories, int $limit = 4): Collection
    {
        $currentExternalId = (string) $question->external_id;
        $preferredCategoryOrder = array_flip($categories
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
        $topicCandidates = collect();

        if ($question->question_topic_id) {
            $topicCandidates = $this->sortRelatedCandidates(
                $this->groupRepresentatives(
                    $this->baseQuery()
                        ->where('question_topic_id', $question->question_topic_id)
                        ->where('external_id', '!=', $currentExternalId)
                        ->with([
                            'licenseCategory:id,code,slug,name,sort_order',
                            'referenceExplanationAsset.trafficSign',
                        ])
                        ->withCount('media')
                        ->get()
                ),
                $preferredCategoryOrder,
            )->values();
        }

        $remaining = max($limit - $topicCandidates->count(), 0);

        if ($remaining === 0) {
            return $this->prepareQuestionListQuestions($topicCandidates->take($limit)->values());
        }

        $categoryIds = $categories
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $excludeExternalIds = $topicCandidates
            ->pluck('external_id')
            ->all();
        $categoryCandidates = collect();

        if ($categoryIds !== []) {
            $categoryQuery = $this->baseQuery()
                ->whereIn('license_category_id', $categoryIds)
                ->where('external_id', '!=', $currentExternalId);

            if ($excludeExternalIds !== []) {
                $categoryQuery->whereNotIn('external_id', $excludeExternalIds);
            }

            $categoryCandidates = $this->sortRelatedCandidates(
                $this->groupRepresentatives(
                    $categoryQuery
                        ->with([
                            'licenseCategory:id,code,slug,name,sort_order',
                            'referenceExplanationAsset.trafficSign',
                        ])
                        ->withCount('media')
                        ->get()
                ),
                $preferredCategoryOrder,
            )->values();
        }

        $related = $topicCandidates
            ->concat($categoryCandidates)
            ->unique('external_id')
            ->take($limit)
            ->values();

        return $this->prepareQuestionListQuestions($related);
    }

    /**
     * @param  list<array{external_id: string, description: string}>  $relatedQuestions
     * @param  Collection<int, LicenseCategory>  $categories
     * @return Collection<int, array<string, mixed>>
     */
    public function editorialRelatedQuestions(array $relatedQuestions, Collection $categories): Collection
    {
        $relations = collect($relatedQuestions)
            ->filter(fn (mixed $relation): bool => is_array($relation))
            ->map(fn (array $relation): array => [
                'external_id' => trim((string) ($relation['external_id'] ?? '')),
                'description' => trim((string) ($relation['description'] ?? '')),
            ])
            ->filter(fn (array $relation): bool => $relation['external_id'] !== '' && $relation['description'] !== '')
            ->unique('external_id')
            ->values();

        if ($relations->isEmpty()) {
            return collect();
        }

        $preferredCategoryOrder = array_flip($categories
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
        $questions = $this->baseQuery()
            ->whereIn('external_id', $relations->pluck('external_id')->all())
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
                'referenceExplanationAsset.trafficSign',
            ])
            ->withCount('media')
            ->get();
        $this->sharedQuestionExplanationAssetResolver->preloadForQuestions($questions);
        $questionsByExternalId = $questions
            ->groupBy(fn (Question $question): string => (string) $question->external_id);

        return $relations
            ->map(function (array $relation) use ($preferredCategoryOrder, $questionsByExternalId): ?array {
                $questionGroup = $questionsByExternalId->get($relation['external_id'], collect());

                if ($questionGroup->isEmpty()) {
                    return null;
                }

                $question = $this->sortRelatedCandidates($questionGroup, $preferredCategoryOrder)->first();

                if (! $question instanceof Question) {
                    return null;
                }

                return [
                    ...$this->buildQuestionListItem($question, $question->licenseCategory, true),
                    'relation_description' => $relation['description'],
                    'relation_description_html' => $this->questionTextFormatter->inlineHtml($relation['description']),
                    'relation_description_plain' => $this->questionTextFormatter->plainText($relation['description']),
                    'is_editorial_relation' => true,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function hubSitemapUrls(): array
    {
        return [[
            'loc' => route('public.questions.hub'),
            'lastmod' => $this->latestQuestionLastModified(),
            'images' => [],
        ]];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function categorySitemapUrls(): array
    {
        $latestQuestionByCategoryId = $this->baseQuery()
            ->selectRaw('license_category_id, max(updated_at) as latest_question_updated_at')
            ->groupBy('license_category_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->license_category_id => filled($row->latest_question_updated_at)
                    ? Carbon::parse((string) $row->latest_question_updated_at)->toIso8601String()
                    : null,
            ]);

        return $this->visibleCategories()
            ->map(fn (LicenseCategory $category): array => [
                'loc' => route('public.questions.category', $category->slug),
                'lastmod' => $latestQuestionByCategoryId->get((int) $category->getKey()),
                'images' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function sitemapUrls(): array
    {
        return $this->loadQuestionMediaForSitemapRows($this->canonicalQuestionSitemapRows())
            ->map(fn (array $row): array => $this->buildQuestionSitemapItem($row))
            ->sortBy('loc')
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, videos: list<array{thumbnail_loc: string, title: string, description: string, content_loc: string, duration: int|null, publication_date: string|null, family_friendly: string}>}>
     */
    public function videoSitemapUrls(): array
    {
        return $this->loadQuestionMediaForSitemapRows($this->canonicalQuestionSitemapRows())
            ->map(fn (array $row): ?array => $this->buildQuestionVideoSitemapItem($row))
            ->filter()
            ->sortBy('loc')
            ->values()
            ->all();
    }

    /**
     * @return Collection<string, Collection<int, array{question: Question, lastmod: string|null}>>
     */
    public function canonicalQuestionSitemapRowsByCategory(): Collection
    {
        return $this->canonicalQuestionSitemapRows()
            ->groupBy(fn (array $row): string => (string) $row['question']->licenseCategory?->slug);
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, images: array<int, string>}>
     */
    public function sitemapUrlsForCanonicalCategory(LicenseCategory $category): array
    {
        $rows = $this->canonicalQuestionSitemapRowsByCategory()
            ->get((string) $category->slug, collect());

        return $this->loadQuestionMediaForSitemapRows($rows)
            ->map(fn (array $row): array => $this->buildQuestionSitemapItem($row))
            ->sortBy('loc')
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{question: Question, lastmod: string|null}>
     */
    protected function canonicalQuestionSitemapRows(): Collection
    {
        if ($this->canonicalQuestionSitemapRowsCache instanceof Collection) {
            return $this->canonicalQuestionSitemapRowsCache;
        }

        $questions = $this->baseQuery()
            ->with('licenseCategory:id,code,slug,name,sort_order')
            ->withCount('media')
            ->get([
                'id',
                'license_category_id',
                'external_id',
                'prompt',
                'updated_at',
            ]);

        $publicExplanationLastModifiedByExternalId = $this->publicQuestionExplanationService
            ->lastModifiedByExternalIds($questions->pluck('external_id')->all());

        return $this->canonicalQuestionSitemapRowsCache = $questions
            ->groupBy('external_id')
            ->map(function (Collection $group) use ($publicExplanationLastModifiedByExternalId): array {
                /** @var Question $representative */
                $representative = $this->representativeQuestion($group);
                $lastModified = $this->maxIsoDate([
                    ...$group
                        ->pluck('updated_at')
                        ->filter(fn (mixed $value): bool => $value instanceof Carbon)
                        ->all(),
                    $publicExplanationLastModifiedByExternalId->get((string) $representative->external_id),
                ]);

                return [
                    'question' => $representative,
                    'lastmod' => $lastModified,
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, array{question: Question, lastmod: string|null}>  $rows
     * @return Collection<int, array{question: Question, lastmod: string|null}>
     */
    protected function loadQuestionMediaForSitemapRows(Collection $rows): Collection
    {
        $questionIds = $rows
            ->pluck('question.id')
            ->filter()
            ->values()
            ->all();

        if ($questionIds === []) {
            return $rows;
        }

        $questions = Question::query()
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (array $row) use ($questions): array {
                $loaded = $questions->get($row['question']->getKey());

                if ($loaded instanceof Question) {
                    $row['question'] = $loaded;
                }

                return $row;
            });
    }

    /**
     * @param  array{question: Question, lastmod: string|null}  $row
     * @return array{loc: string, lastmod: string|null, images: array<int, string>}
     */
    protected function buildQuestionSitemapItem(array $row): array
    {
        $mediaPayload = $this->primaryMediaPayload($row['question']);
        $images = match ($mediaPayload['kind'] ?? null) {
            'image' => [
                $mediaPayload['poster_url'] ?? null,
                $mediaPayload['url'] ?? null,
            ],
            'video' => [
                $mediaPayload['poster_url'] ?? null,
            ],
            default => [],
        };

        return [
            'loc' => $this->questionUrl($row['question']),
            'lastmod' => $row['lastmod'],
            'images' => array_values(array_unique(array_filter($images))),
        ];
    }

    /**
     * @param  array{question: Question, lastmod: string|null}  $row
     * @return array{loc: string, lastmod: string|null, videos: list<array{thumbnail_loc: string, title: string, description: string, content_loc: string, duration: int|null, publication_date: string|null, family_friendly: string}>}|null
     */
    protected function buildQuestionVideoSitemapItem(array $row): ?array
    {
        $question = $row['question'];
        $mediaPayload = $this->primaryMediaPayload($question);

        if (($mediaPayload['kind'] ?? null) !== 'video') {
            return null;
        }

        $pageUrl = $this->questionUrl($question);
        $contentUrl = $this->nonBlankString($mediaPayload['url'] ?? null);
        $thumbnailUrl = $this->nonBlankString($mediaPayload['poster_url'] ?? null);

        if ($contentUrl === null || $thumbnailUrl === null || $contentUrl === $pageUrl) {
            return null;
        }

        return [
            'loc' => $pageUrl,
            'lastmod' => $this->questionVideoLastModified($question, $row['lastmod']),
            'videos' => [[
                'thumbnail_loc' => $thumbnailUrl,
                'title' => $this->sitemapVideoTitle($question),
                'description' => $this->sitemapVideoDescription($question),
                'content_loc' => $contentUrl,
                'duration' => $this->positiveInt($mediaPayload['duration_seconds'] ?? null),
                'publication_date' => $this->sitemapVideoPublicationDate($question),
                'family_friendly' => 'yes',
            ]],
        ];
    }

    public function latestQuestionLastModified(): ?string
    {
        $value = $this->baseQuery()->max('updated_at');

        return filled($value)
            ? Carbon::parse((string) $value)->toIso8601String()
            : null;
    }

    public function latestQuestionVideoLastModified(): ?string
    {
        $questionValue = $this->baseQuery()
            ->whereHas('media', fn (Builder $query) => $query
                ->where('kind', 'video')
                ->whereNotNull('path')
                ->whereNotNull('poster_path'))
            ->max('updated_at');

        $mediaValue = QuestionMedia::query()
            ->where('kind', 'video')
            ->whereNotNull('path')
            ->whereNotNull('poster_path')
            ->whereHas('question', function (Builder $query): void {
                $query
                    ->where('is_active', true)
                    ->readyForDelivery()
                    ->whereNotNull('external_id')
                    ->where('external_id', '!=', '')
                    ->whereIn('license_category_id', $this->visibleCategoryIds());
            })
            ->max('updated_at');

        return $this->maxIsoDate([$questionValue, $mediaValue]);
    }

    protected function sitemapVideoTitle(Question $question): string
    {
        $displayExternalId = $this->displayExternalId($question->external_id);
        $plainPrompt = Str::squish($this->questionTextFormatter->plainText($question->prompt));
        $title = trim('Film do pytania '.$displayExternalId.($plainPrompt !== '' ? ': '.$plainPrompt : ''));

        return $title !== '' ? $title : 'Film do pytania egzaminacyjnego';
    }

    protected function sitemapVideoDescription(Question $question): string
    {
        return $this->questionVideoSeoDescriptionService->forQuestion($question);
    }

    protected function questionVideoLastModified(Question $question, ?string $questionLastModified): ?string
    {
        $values = [$questionLastModified];

        if ($question->relationLoaded('media')) {
            $values = array_merge(
                $values,
                $question->media
                    ->filter(fn (QuestionMedia $media): bool => $media->kind === 'video')
                    ->pluck('updated_at')
                    ->all(),
            );
        }

        return $this->maxIsoDate($values);
    }

    protected function sitemapVideoPublicationDate(Question $question): ?string
    {
        $date = $question->published_at ?? $question->created_at ?? $question->updated_at;

        return $date?->toIso8601String();
    }

    protected function nonBlankString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function trafficSignImageUrl(TrafficSign $sign): ?string
    {
        $cutoutPath = "traffic-signs/sign-cutouts/{$sign->slug}.png";

        if (is_file(public_path($cutoutPath))) {
            return $this->mediaUrlResolver->resolve($cutoutPath);
        }

        return filled($sign->image_path)
            ? $this->mediaUrlResolver->resolveIfPublicAssetExists($sign->image_path)
            : null;
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
     * @param  iterable<mixed>  $values
     */
    protected function maxIsoDate(iterable $values): ?string
    {
        $max = Collection::make($values)
            ->filter()
            ->map(fn (mixed $value): Carbon => $value instanceof Carbon
                ? $value
                : Carbon::parse((string) $value))
            ->sortDesc()
            ->first();

        return $max?->toIso8601String();
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    protected function groupRepresentatives(Collection $questions): Collection
    {
        return $questions
            ->filter(fn (Question $question): bool => filled($question->external_id))
            ->groupBy('external_id')
            ->map(fn (Collection $group): Question => $this->representativeQuestion($group))
            ->sortBy(fn (Question $question): string => mb_strtolower((string) $question->external_id))
            ->values();
    }

    /**
     * @return Collection<int, Question>
     */
    protected function questionGroupByDisplayExternalId(string $externalId): Collection
    {
        $displayExternalId = $this->displayExternalId($externalId);

        if ($displayExternalId === '') {
            return collect();
        }

        $candidateIds = $this->baseQuery()
            ->where('external_id', 'like', '%:'.$displayExternalId.'%')
            ->pluck('external_id')
            ->filter(fn (mixed $candidate): bool => $this->displayExternalId($candidate) === $displayExternalId)
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return $this->baseQuery()
            ->whereIn('external_id', $candidateIds->all())
            ->with([
                'licenseCategory:id,code,slug,name,sort_order',
                'media' => fn ($query) => $query->orderBy('sort_order'),
                'referenceExplanationAsset.trafficSign',
            ])
            ->withCount('media')
            ->get()
            ->sortBy('id')
            ->values();
    }

    /**
     * @return Collection<int, Question>
     */
    protected function questionGroupByAuxiliaryPublicExternalId(string $externalId): Collection
    {
        $publicExternalId = Str::slug(trim($externalId));

        if ($publicExternalId === '') {
            return collect();
        }

        $rawExternalId = $this->rawExternalIdsByAuxiliaryPublicExternalId()[$publicExternalId] ?? null;

        return is_string($rawExternalId)
            ? $this->questionGroup($rawExternalId)
            : collect();
    }

    protected function displayExternalIdIsAmbiguous(string $displayExternalId, string $rawExternalId): bool
    {
        $rawExternalIds = $this->rawExternalIdsByDisplayExternalId()[$displayExternalId] ?? [];
        $normalizedExternalIds = array_unique(array_map(
            fn (mixed $value): string => trim((string) $value),
            [...$rawExternalIds, $rawExternalId],
        ));

        return count($normalizedExternalIds) > 1;
    }

    protected function auxiliaryPublicExternalId(string $rawExternalId): string
    {
        $displayExternalId = $this->displayExternalId($rawExternalId);
        $basePublicExternalId = Str::slug(self::AUXILIARY_PUBLIC_EXTERNAL_ID_PREFIX.'-'.$displayExternalId);

        if ($basePublicExternalId === '') {
            return self::AUXILIARY_PUBLIC_EXTERNAL_ID_PREFIX.'-'.substr(hash('crc32b', $rawExternalId), 0, 6);
        }

        $prefixedRawExternalIds = collect($this->rawExternalIdsByDisplayExternalId()[$displayExternalId] ?? [])
            ->filter(fn (mixed $candidate): bool => str_contains((string) $candidate, ':'))
            ->map(fn (mixed $candidate): string => trim((string) $candidate))
            ->unique()
            ->values();

        if ($prefixedRawExternalIds->count() <= 1) {
            return $basePublicExternalId;
        }

        return $basePublicExternalId.'-'.substr(hash('crc32b', $rawExternalId), 0, 6);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rawExternalIdsByDisplayExternalId(): array
    {
        if ($this->rawExternalIdsByDisplayExternalIdCache !== null) {
            return $this->rawExternalIdsByDisplayExternalIdCache;
        }

        $groups = [];

        $this->baseQuery()
            ->pluck('external_id')
            ->filter(fn (mixed $externalId): bool => filled($externalId))
            ->map(fn (mixed $externalId): string => trim((string) $externalId))
            ->unique()
            ->each(function (string $rawExternalId) use (&$groups): void {
                $displayExternalId = $this->displayExternalId($rawExternalId);

                if ($displayExternalId === '') {
                    return;
                }

                $groups[$displayExternalId] ??= [];
                $groups[$displayExternalId][] = $rawExternalId;
            });

        return $this->rawExternalIdsByDisplayExternalIdCache = $groups;
    }

    /**
     * @return array<string, string>
     */
    protected function rawExternalIdsByAuxiliaryPublicExternalId(): array
    {
        if ($this->rawExternalIdsByAuxiliaryPublicExternalIdCache !== null) {
            return $this->rawExternalIdsByAuxiliaryPublicExternalIdCache;
        }

        $map = [];

        foreach ($this->rawExternalIdsByDisplayExternalId() as $rawExternalIds) {
            foreach ($rawExternalIds as $rawExternalId) {
                if (! str_contains($rawExternalId, ':')) {
                    continue;
                }

                $map[$this->auxiliaryPublicExternalId($rawExternalId)] = $rawExternalId;
            }
        }

        return $this->rawExternalIdsByAuxiliaryPublicExternalIdCache = $map;
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    protected function representativeQuestion(Collection $questions): Question
    {
        /** @var Question $question */
        $question = $questions
            ->sort(function (Question $left, Question $right): int {
                $comparisons = [
                    ((int) ($right->media_count ?? 0)) <=> ((int) ($left->media_count ?? 0)),
                    ((int) ($left->licenseCategory?->sort_order ?? PHP_INT_MAX)) <=> ((int) ($right->licenseCategory?->sort_order ?? PHP_INT_MAX)),
                    strcmp((string) ($left->licenseCategory?->code ?? ''), (string) ($right->licenseCategory?->code ?? '')),
                    $left->getKey() <=> $right->getKey(),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->first();

        return $question;
    }

    /**
     * @param  array<int, int>  $preferredCategoryOrder
     * @param  Collection<int, Question>  $questions
     * @return Collection<int, Question>
     */
    protected function sortRelatedCandidates(Collection $questions, array $preferredCategoryOrder): Collection
    {
        return $questions
            ->sort(function (Question $left, Question $right) use ($preferredCategoryOrder): int {
                $leftCategoryRank = $preferredCategoryOrder[(int) ($left->licenseCategory?->getKey() ?? 0)] ?? PHP_INT_MAX;
                $rightCategoryRank = $preferredCategoryOrder[(int) ($right->licenseCategory?->getKey() ?? 0)] ?? PHP_INT_MAX;
                $leftExternalId = (string) $left->external_id;
                $rightExternalId = (string) $right->external_id;
                $externalIdComparison = ctype_digit($leftExternalId) && ctype_digit($rightExternalId)
                    ? ((int) $leftExternalId <=> (int) $rightExternalId)
                    : strnatcasecmp($leftExternalId, $rightExternalId);

                $comparisons = [
                    $leftCategoryRank <=> $rightCategoryRank,
                    ((int) ($right->media_count ?? 0)) <=> ((int) ($left->media_count ?? 0)),
                    $externalIdComparison,
                    $left->getKey() <=> $right->getKey(),
                ];

                foreach ($comparisons as $comparison) {
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->values();
    }

    protected function referenceAssetTitle(QuestionExplanationAsset|SharedQuestionExplanationAsset $asset): string
    {
        return trim((string) ($asset->title ?? ''));
    }

    /**
     * @return array{vehicle_label: string, summary: string}
     */
    public function categoryMarketingCopy(LicenseCategory $category): array
    {
        $code = Str::upper((string) $category->code);

        return match ($code) {
            'A' => [
                'vehicle_label' => 'Motocykle',
                'summary' => 'Oficjalne pytania na prawo jazdy kat. A dla osób przygotowujących się do egzaminu na motocykl bez ograniczenia mocy.',
            ],
            'A1' => [
                'vehicle_label' => 'Lżejsze motocykle',
                'summary' => 'Pytania kategorii A1 dla kandydatów na kierowców motocykli do 125 cm3, z poprawnymi odpowiedziami i wygodnym podziałem do nauki.',
            ],
            'A2' => [
                'vehicle_label' => 'Motocykle do 35 kW',
                'summary' => 'Oficjalne pytania egzaminacyjne kategorii A2 dla osób uczących się do teorii na motocykl o mocy do 35 kW.',
            ],
            'AM' => [
                'vehicle_label' => 'Motorowery i lekkie czterokołowce',
                'summary' => 'Baza pytań kategorii AM dla kandydatów przygotowujących się do egzaminu teoretycznego na motorower lub lekki czterokołowiec.',
            ],
            'B' => [
                'vehicle_label' => 'Samochody osobowe',
                'summary' => 'Najszersza baza pytań kategorii B dla przyszłych kierowców samochodów osobowych i lekkich dostawczych do 3,5 tony.',
            ],
            'B1' => [
                'vehicle_label' => 'Czterokołowce',
                'summary' => 'Oficjalne pytania na prawo jazdy kat. B1 dla osób przygotowujących się do prowadzenia czterokołowców.',
            ],
            'C' => [
                'vehicle_label' => 'Samochody ciężarowe',
                'summary' => 'Pytania egzaminacyjne kategorii C dla kandydatów na kierowców samochodów ciężarowych z poprawnymi odpowiedziami i wyjaśnieniami.',
            ],
            'C1' => [
                'vehicle_label' => 'Lżejsze ciężarówki',
                'summary' => 'Baza pytań kategorii C1 dla osób uczących się do egzaminu na samochody ciężarowe o DMC do 7,5 tony.',
            ],
            'D' => [
                'vehicle_label' => 'Autobusy',
                'summary' => 'Oficjalne pytania na prawo jazdy kat. D dla kandydatów na kierowców autobusów i przewozu osób.',
            ],
            'D1' => [
                'vehicle_label' => 'Mniejsze autobusy',
                'summary' => 'Pytania kategorii D1 dla osób przygotowujących się do egzaminu na mniejsze autobusy przeznaczone do przewozu pasażerów.',
            ],
            'T' => [
                'vehicle_label' => 'Ciągniki rolnicze',
                'summary' => 'Oficjalne pytania kategorii T dla kandydatów na kierowców ciągników rolniczych i pojazdów wolnobieżnych.',
            ],
            default => [
                'vehicle_label' => 'Pytania egzaminacyjne',
                'summary' => 'Oficjalne pytania egzaminacyjne z poprawnymi odpowiedziami i wyjaśnieniami dla tej kategorii prawa jazdy.',
            ],
        };
    }

    protected function baseQuery(): Builder
    {
        return $this->publicQuestionsQuery();
    }

    /**
     * @param  Collection<int, Question>  $questions
     */
    public function representativeQuestionForGroup(Collection $questions): Question
    {
        return $this->representativeQuestion($questions);
    }

    /**
     * @return array<int, int>
     */
    protected function visibleCategoryIds(): array
    {
        return $this->visibleCategories()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
