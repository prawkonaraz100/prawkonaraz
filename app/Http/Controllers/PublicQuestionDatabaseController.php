<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\ContentAuthor;
use App\Models\LicenseCategory;
use App\Support\LegalContentCatalogService;
use App\Support\NewsroomSemanticLinkService;
use App\Support\PublicQuestionAnswerStatsService;
use App\Support\PublicQuestionBreadcrumbs;
use App\Support\PublicQuestionCatalogService;
use App\Support\PublicQuestionExplanationService;
use App\Support\PublicQuestionRelationsService;
use App\Support\PublicQuestionSchemaService;
use App\Support\PublicQuestionSearchService;
use App\Support\PublicQuestionSeoService;
use App\Support\PublicQuestionSignReferenceService;
use App\Support\QuestionAudioPayloadBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PublicQuestionDatabaseController extends Controller
{
    public function __construct(
        private readonly NewsroomSemanticLinkService $newsroomSemanticLinks,
    ) {}

    public function index(
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        PublicQuestionSearchService $publicQuestionSearchService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
    ): View|RedirectResponse {
        $searchQuery = $this->hubSearchQuery($request);
        $searchResult = null;

        if ($searchQuery !== '') {
            $canonicalUrl = $publicQuestionSearchService->canonicalRedirectFor($searchQuery);

            if ($canonicalUrl !== null) {
                return redirect()->to($canonicalUrl);
            }

            $searchResult = $publicQuestionSearchService->search($searchQuery);

            if ($searchResult->redirectUrl !== null) {
                return redirect()->to($searchResult->redirectUrl);
            }
        }

        $categories = $publicQuestionCatalogService->categoryCards();
        $canonicalQuestionsCount = $publicQuestionCatalogService->canonicalQuestionsCount();
        $breadcrumbs = $publicQuestionBreadcrumbs->hub();
        $featuredCategory = $publicQuestionCatalogService->visibleCategories()->first();
        $featuredQuestionItems = $featuredCategory
            ? $publicQuestionCatalogService->representativeQuestionsForCategory($featuredCategory)
            : collect();
        $featuredQuestions = $featuredQuestionItems
            ->take(15)
            ->map(fn ($question): array => $publicQuestionCatalogService->buildQuestionListItem($question, $featuredCategory))
            ->values();

        return view('questions-database.index', [
            'categories' => $categories,
            'canonicalQuestionsCount' => $canonicalQuestionsCount,
            'searchQuery' => $searchQuery,
            'hasActiveSearch' => $searchQuery !== '',
            'searchResults' => $searchResult !== null
                ? $this->paginateItems($searchResult->items, 10, $request)
                : null,
            'featuredCategory' => $featuredCategory,
            'featuredCategoryCard' => $featuredCategory
                ? $categories->firstWhere('id', $featuredCategory->getKey())
                : null,
            'featuredQuestions' => $featuredQuestions,
            'featuredQuestionTotal' => $featuredQuestionItems->count(),
            'meta' => $publicQuestionSeoService->hub(
                $categories->count(),
                $canonicalQuestionsCount,
            ),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $publicQuestionSchemaService->hub($breadcrumbs, $categories),
        ]);
    }

    public function search(
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        PublicQuestionSearchService $publicQuestionSearchService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
    ): View|RedirectResponse {
        return $this->index(
            $request,
            $publicQuestionCatalogService,
            $publicQuestionSearchService,
            $publicQuestionSeoService,
            $publicQuestionSchemaService,
            $publicQuestionBreadcrumbs,
        );
    }

    public function category(
        string $categorySlug,
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        PublicQuestionSearchService $publicQuestionSearchService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
    ): View {
        $category = $publicQuestionCatalogService->findVisibleCategoryBySlug($categorySlug);

        abort_unless($category !== null, 404);

        $representativeQuestions = $publicQuestionCatalogService
            ->representativeQuestionsForCategory($category);
        $filters = $this->categoryFilters($request);
        $searchResult = $publicQuestionSearchService->search($filters['q'], $category, $filters['gov_id']);
        $questionItems = $searchResult->items;
        $questions = $this->paginateItems($questionItems, 10, $request);
        $breadcrumbs = $publicQuestionBreadcrumbs->category($category);
        $categoryMarketingCopy = $publicQuestionCatalogService->categoryMarketingCopy($category);
        $latestQuestionDate = $representativeQuestions
            ->pluck('updated_at')
            ->filter()
            ->sortDesc()
            ->first();

        return view('questions-database.category', [
            'category' => $category,
            'summary' => $categoryMarketingCopy['summary'],
            'vehicleLabel' => $categoryMarketingCopy['vehicle_label'],
            'questions' => $questions,
            'questionTypeSummary' => 'Test jednokrotnego wyboru',
            'latestQuestionDate' => $latestQuestionDate,
            'filters' => $filters,
            'meta' => $publicQuestionSeoService->category($category, $questions),
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $publicQuestionSchemaService->category($category, $breadcrumbs, $questions->items()),
        ]);
    }

    public function show(
        string $externalId,
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        LegalContentCatalogService $legalContentCatalogService,
        PublicQuestionExplanationService $publicQuestionExplanationService,
        PublicQuestionRelationsService $publicQuestionRelationsService,
        PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
        QuestionAudioPayloadBuilder $questionAudioPayloadBuilder,
        PublicQuestionAnswerStatsService $publicQuestionAnswerStatsService,
        ?string $slug = null,
    ): View|RedirectResponse {
        return $this->renderQuestion(
            null,
            $externalId,
            $slug,
            $request,
            $publicQuestionCatalogService,
            $legalContentCatalogService,
            $publicQuestionExplanationService,
            $publicQuestionRelationsService,
            $publicQuestionSignReferenceService,
            $publicQuestionSeoService,
            $publicQuestionSchemaService,
            $publicQuestionBreadcrumbs,
            $questionAudioPayloadBuilder,
            $publicQuestionAnswerStatsService,
        );
    }

    public function showInCategory(
        string $categorySlug,
        string $externalId,
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        LegalContentCatalogService $legalContentCatalogService,
        PublicQuestionExplanationService $publicQuestionExplanationService,
        PublicQuestionRelationsService $publicQuestionRelationsService,
        PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
        QuestionAudioPayloadBuilder $questionAudioPayloadBuilder,
        PublicQuestionAnswerStatsService $publicQuestionAnswerStatsService,
        ?string $slug = null,
    ): View|RedirectResponse {
        return $this->renderQuestion(
            $categorySlug,
            $externalId,
            $slug,
            $request,
            $publicQuestionCatalogService,
            $legalContentCatalogService,
            $publicQuestionExplanationService,
            $publicQuestionRelationsService,
            $publicQuestionSignReferenceService,
            $publicQuestionSeoService,
            $publicQuestionSchemaService,
            $publicQuestionBreadcrumbs,
            $questionAudioPayloadBuilder,
            $publicQuestionAnswerStatsService,
        );
    }

    protected function renderQuestion(
        ?string $categorySlug,
        string $externalId,
        ?string $slug,
        Request $request,
        PublicQuestionCatalogService $publicQuestionCatalogService,
        LegalContentCatalogService $legalContentCatalogService,
        PublicQuestionExplanationService $publicQuestionExplanationService,
        PublicQuestionRelationsService $publicQuestionRelationsService,
        PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
        PublicQuestionSeoService $publicQuestionSeoService,
        PublicQuestionSchemaService $publicQuestionSchemaService,
        PublicQuestionBreadcrumbs $publicQuestionBreadcrumbs,
        QuestionAudioPayloadBuilder $questionAudioPayloadBuilder,
        PublicQuestionAnswerStatsService $publicQuestionAnswerStatsService,
    ): View|RedirectResponse {
        $relationPreviewRequested = $request->query('relation_preview') !== null;

        if ($relationPreviewRequested) {
            abort_unless(
                $request->query('relation_preview') === 'v2' && $request->hasValidSignature(),
                403,
            );
        }

        $questions = $publicQuestionCatalogService->questionGroupByExternalOrDisplayId($externalId);

        if ($questions->isEmpty()) {
            abort(404);
        }

        $canonicalQuestion = $publicQuestionCatalogService->findCanonicalQuestionByExternalId($externalId);

        abort_unless($canonicalQuestion !== null, 404);

        $canonicalUrl = $publicQuestionCatalogService->questionUrl($canonicalQuestion);
        $categories = $publicQuestionCatalogService->categoriesForQuestions($questions);
        $contextCategory = $this->contextCategoryForQuestionGroup(
            $categorySlug,
            $categories,
            $publicQuestionCatalogService,
        );
        $primaryCategory = $contextCategory
            ?? $publicQuestionCatalogService->primaryCategoryForQuestions($questions);
        $displayQuestion = $contextCategory instanceof LicenseCategory
            ? $questions->first(fn ($question): bool => (int) $question->license_category_id === (int) $contextCategory->getKey())
            : null;
        $mainQuestion = $displayQuestion ?? $canonicalQuestion;
        $expectedSlug = $publicQuestionCatalogService->slugForQuestion($mainQuestion);
        $expectedExternalId = $publicQuestionCatalogService->publicExternalIdForQuestion($mainQuestion);
        $expectedUrl = $contextCategory instanceof LicenseCategory
            ? $publicQuestionCatalogService->questionUrl($mainQuestion, $contextCategory)
            : $canonicalUrl;

        if ($externalId !== $expectedExternalId || $slug !== $expectedSlug) {
            return redirect()->to($expectedUrl, 301);
        }

        $mediaPayload = $publicQuestionCatalogService->primaryMediaPayload($mainQuestion);
        $question = $publicQuestionCatalogService->buildQuestionShowModel($mainQuestion);
        $questionAudio = $questionAudioPayloadBuilder->forPublicQuestion($mainQuestion);
        $answerExplanation = $publicQuestionExplanationService->publicOrSystemFallbackForQuestionGroup($questions, $mainQuestion);
        $answerExplanation = $this->decorateAnswerExplanationSignReferences(
            $answerExplanation,
            $publicQuestionSignReferenceService,
        );
        $displayExternalId = $publicQuestionCatalogService->displayExternalId($mainQuestion->external_id);
        $questionMeta = [
            'external_id' => (string) $mainQuestion->external_id,
            'display_external_id' => $displayExternalId,
            'type_label' => $publicQuestionCatalogService->questionTypeLabel($mainQuestion),
            'source_label' => $publicQuestionCatalogService->questionSourceLabel($mainQuestion),
            'points' => $mainQuestion->points,
            'published_at' => $mainQuestion->published_at,
            'updated_at' => $mainQuestion->updated_at,
            'correct_option_label' => $publicQuestionCatalogService->correctOptionLabel($question['options']),
            'correct_option_summary' => $publicQuestionCatalogService->correctOptionSummary($mainQuestion, $question['options']),
        ];
        $questionAnswerStats = $publicQuestionAnswerStatsService->forQuestion(
            $mainQuestion,
            $question['options'],
            $questions,
        );
        $referenceSign = $publicQuestionCatalogService->referenceSignCard($mainQuestion);
        $legalReferences = $legalContentCatalogService->verifiedReferencesForQuestionGroup($questions);
        $legalReferenceDefaultVerifier = $legalReferences->isNotEmpty()
            ? ContentAuthor::defaultLegalReferenceVerifier()
            : null;
        $canEditPublicExplanation = (bool) $request->user()?->isAdministrator();
        $relatedQuestionCategories = $contextCategory instanceof LicenseCategory
            ? collect([$contextCategory])
            : $categories;
        $editorialRelatedQuestions = $publicQuestionCatalogService
            ->editorialRelatedQuestions($answerExplanation['related_questions'] ?? [], $relatedQuestionCategories);
        $relatedQuestionData = $publicQuestionRelationsService->forQuestion(
            $mainQuestion,
            $relatedQuestionCategories,
            $editorialRelatedQuestions,
        );
        $relatedQuestionPreview = false;

        if ($relationPreviewRequested) {
            $v2Preview = $publicQuestionRelationsService->v2SignedPreviewForQuestion($mainQuestion);

            abort_unless($v2Preview !== null && (int) $v2Preview['total'] > 0, 404);

            $relatedQuestionData = [
                'groups' => $v2Preview['groups'],
                'total' => $v2Preview['total'],
                'topic' => $v2Preview['topic'],
            ];
            $relatedQuestionPreview = true;
        }
        $relatedQuestionGroups = $relatedQuestionData['groups']
            ->map(function (array $group) use ($publicQuestionSignReferenceService): array {
                $group['items'] = $this->decorateRelatedQuestionSignReferences(
                    $group['items'],
                    $publicQuestionSignReferenceService,
                );

                return $group;
            });
        $questionNavigation = $publicQuestionCatalogService->questionNavigation($mainQuestion, $primaryCategory);
        $breadcrumbs = $publicQuestionBreadcrumbs->question(
            $primaryCategory,
            'Pytanie '.$displayExternalId,
            $canonicalUrl,
        );
        $meta = $publicQuestionSeoService->question($mainQuestion, $primaryCategory, $mediaPayload, $canonicalUrl);

        if ($relatedQuestionPreview) {
            $meta['robots'] = 'noindex,nofollow,noarchive';
        }

        return view('questions-database.show', [
            'question' => $question,
            'questionMeta' => $questionMeta,
            'categories' => $categories,
            'primaryCategory' => $primaryCategory,
            'media' => $mediaPayload,
            'questionAudio' => $questionAudio,
            'questionAnswerStats' => $questionAnswerStats,
            'answerExplanation' => $answerExplanation,
            'referenceSign' => $referenceSign,
            'legalReferences' => $legalReferences,
            'legalReferenceDefaultVerifier' => $legalReferenceDefaultVerifier,
            'relatedQuestionGroups' => $relatedQuestionGroups,
            'relatedQuestionTotal' => $relatedQuestionData['total'],
            'relatedQuestionTopic' => $relatedQuestionData['topic'],
            'relatedQuestionPreview' => $relatedQuestionPreview,
            'questionNavigation' => $questionNavigation,
            'canEditPublicExplanation' => $canEditPublicExplanation,
            'questionEditUrl' => $canEditPublicExplanation
                ? QuestionResource::getUrl('edit', ['record' => $mainQuestion], panel: 'admin')
                : null,
            'publicExplanationUpdateUrl' => route('api.v1.admin.questions.public-explanation.update', $mainQuestion),
            'canEditLegalReferences' => $canEditPublicExplanation,
            'legalReferenceUpdateUrl' => route('api.v1.admin.questions.legal-reference.update', $mainQuestion),
            'legalUnitSearchUrl' => route('api.v1.admin.legal-units.search'),
            'newsroomReverseArticles' => $this->newsroomSemanticLinks->forQuestions($questions->pluck('id')),
            'legalReferenceEditorOptions' => $canEditPublicExplanation
                ? $legalContentCatalogService->questionLegalReferenceEditorOptions($legalReferences->first()?->legalUnit)
                : [],
            'meta' => $meta,
            'breadcrumbs' => $breadcrumbs,
            'structuredData' => $relatedQuestionPreview
                ? []
                : $publicQuestionSchemaService->question(
                    $mainQuestion,
                    $breadcrumbs,
                    $canonicalUrl,
                    $question['options'],
                    $publicQuestionCatalogService->acceptedAnswerText($mainQuestion, $answerExplanation['body_plain'] ?? null),
                    $mediaPayload['poster_url'] ?? $mediaPayload['url'] ?? null,
                    $mediaPayload,
                    $questionAudio,
                    $primaryCategory,
                    $legalReferences,
                    $questionAnswerStats,
                ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $answerExplanation
     * @return array<string, mixed>
     */
    protected function decorateAnswerExplanationSignReferences(
        array $answerExplanation,
        PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
    ): array {
        $answerExplanation['body_html'] = $publicQuestionSignReferenceService
            ->decorateHtml((string) ($answerExplanation['body_html'] ?? ''));
        $answerExplanation['dont_confuse_with_html'] = $publicQuestionSignReferenceService
            ->decorateHtml((string) ($answerExplanation['dont_confuse_with_html'] ?? ''));
        $answerExplanation['exam_trap_html'] = $publicQuestionSignReferenceService
            ->decorateHtml((string) ($answerExplanation['exam_trap_html'] ?? ''));
        $answerExplanation['dont_confuse_with_signs'] = $publicQuestionSignReferenceService
            ->cardsForText((string) ($answerExplanation['dont_confuse_with_plain'] ?? $answerExplanation['dont_confuse_with_raw'] ?? ''));
        $answerExplanation['common_mistakes'] = collect($answerExplanation['common_mistakes'] ?? [])
            ->map(function (mixed $mistake) use ($publicQuestionSignReferenceService): mixed {
                if (! is_array($mistake)) {
                    return $mistake;
                }

                $mistake['title_html'] = $publicQuestionSignReferenceService
                    ->decoratePlainText((string) ($mistake['title'] ?? ''));
                $mistake['explanation_html'] = $publicQuestionSignReferenceService
                    ->decoratePlainText((string) ($mistake['explanation'] ?? ''));

                return $mistake;
            })
            ->values()
            ->all();

        return $answerExplanation;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $relatedQuestions
     * @return Collection<int, array<string, mixed>>
     */
    protected function decorateRelatedQuestionSignReferences(
        Collection $relatedQuestions,
        PublicQuestionSignReferenceService $publicQuestionSignReferenceService,
    ): Collection {
        return $relatedQuestions
            ->map(function (array $relatedQuestion) use ($publicQuestionSignReferenceService): array {
                $relatedQuestion['relation_description_html'] = $publicQuestionSignReferenceService
                    ->decorateHtml((string) ($relatedQuestion['relation_description_html'] ?? ''));

                if (empty($relatedQuestion['reference_sign'])) {
                    $relatedQuestion['reference_sign'] = $publicQuestionSignReferenceService->firstCardForText(
                        (string) ($relatedQuestion['relation_description_plain'] ?? ''),
                        (string) ($relatedQuestion['prompt_plain'] ?? ''),
                    );
                }

                return $relatedQuestion;
            })
            ->values();
    }

    /**
     * @param  Collection<int, LicenseCategory>  $categories
     */
    protected function contextCategoryForQuestionGroup(
        ?string $categorySlug,
        Collection $categories,
        PublicQuestionCatalogService $publicQuestionCatalogService,
    ): ?LicenseCategory {
        if ($categorySlug === null) {
            return null;
        }

        $contextCategory = $publicQuestionCatalogService->findVisibleCategoryBySlug($categorySlug);

        abort_unless($contextCategory instanceof LicenseCategory, 404);
        abort_unless(
            $categories->contains(fn (LicenseCategory $category): bool => (int) $category->getKey() === (int) $contextCategory->getKey()),
            404,
        );

        return $contextCategory;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    protected function paginateItems(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $currentPage = max((int) $request->integer('page', 1), 1);
        $slice = $items
            ->forPage($currentPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ],
        );
    }

    protected function hubSearchQuery(Request $request): string
    {
        $query = trim((string) $request->string('q')->value());

        return $query !== ''
            ? $query
            : trim((string) $request->string('question')->value());
    }

    /**
     * @return array{q: string, gov_id: string}
     */
    protected function categoryFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->string('q')->value()),
            'gov_id' => trim((string) $request->string('gov_id')->value()),
        ];
    }
}
