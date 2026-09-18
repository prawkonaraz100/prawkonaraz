<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentCategory;
use App\Models\ContentHomePlacement;
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NewsroomHomeCompositionService
{
    private const CANDIDATE_WINDOW = 100;

    public const DEFAULT_SECONDARY_LIMIT = 4;

    public const DEFAULT_LATEST_LIMIT = 8;

    public const DEFAULT_CATEGORY_ITEMS_LIMIT = 3;

    public const DEFAULT_GUIDES_ITEMS_LIMIT = 3;

    public const DEFAULT_IMPORTANT_NOW_LIMIT = 6;

    public function __construct(
        private readonly ContentArticlePublishingService $publishingService,
    ) {}

    /**
     * @return array{
     *     lead: ?ContentArticle,
     *     secondary: list<ContentArticle>,
     *     latest: list<ContentArticle>,
     *     categories: list<array{
     *         category: ContentCategory,
     *         lead: ?ContentArticle,
     *         items: list<ContentArticle>
     *     }>,
     *     guides: array{
     *         lead: ?ContentArticle,
     *         items: list<ContentArticle>
     *     },
     *     important_now: list<ContentArticle>,
     *     breaking: ?ContentArticle
     * }
     */
    public function compose(
        ?DateTimeInterface $at = null,
        int $secondaryLimit = self::DEFAULT_SECONDARY_LIMIT,
        int $latestLimit = self::DEFAULT_LATEST_LIMIT,
        int $categoryItemsLimit = self::DEFAULT_CATEGORY_ITEMS_LIMIT,
        int $guidesItemsLimit = self::DEFAULT_GUIDES_ITEMS_LIMIT,
        int $importantNowLimit = self::DEFAULT_IMPORTANT_NOW_LIMIT,
        bool $includeManualPlacements = true,
    ): array {
        $at = $this->normalizeAt($at);

        $this->assertLimit($secondaryLimit, 'secondaryLimit', 4);
        $this->assertLimit($latestLimit, 'latestLimit', 50);
        $this->assertLimit($categoryItemsLimit, 'categoryItemsLimit', 20);
        $this->assertLimit($guidesItemsLimit, 'guidesItemsLimit', 20);
        $this->assertLimit($importantNowLimit, 'importantNowLimit', 6);

        /** @var array<int, true> $used */
        $used = [];

        $lead = $this->resolveEditorialSlot(
            ContentHomePlacement::SLOT_LEAD,
            null,
            1,
            $at,
            $used,
            includeManualPlacements: $includeManualPlacements,
        )[0] ?? null;

        $secondary = $this->resolveEditorialSlot(
            ContentHomePlacement::SLOT_SECONDARY,
            null,
            $secondaryLimit,
            $at,
            $used,
            includeManualPlacements: $includeManualPlacements,
        );

        $latest = $this->resolveChronological(
            $latestLimit,
            $at,
            $used,
        );

        $categoryBlocks = [];

        $categories = ContentCategory::query()
            ->active()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $categoryPlacements = $includeManualPlacements
            ? $this->categoryPlacementCandidates($at)
            : collect();

        $categoryCandidatePools = $this->categoryCandidatePools($at, $categories);

        foreach ($categories as $category) {
            $categoryId = (int) $category->getKey();
            $categorySlug = (string) $category->slug;

            $categoryLead = $this->resolveEditorialSlot(
                ContentHomePlacement::SLOT_CATEGORY_LEAD,
                $categorySlug,
                1,
                $at,
                $used,
                $category,
                includeManualPlacements: $includeManualPlacements,
                placementCandidates: $categoryPlacements->get($categorySlug, []),
                fallbackCandidates: $categoryCandidatePools['editorial']->get($categoryId, []),
            )[0] ?? null;

            $items = $this->resolveChronological(
                $categoryItemsLimit,
                $at,
                $used,
                $category,
                fallbackCandidates: $categoryCandidatePools['chronological']->get($categoryId, []),
            );

            if ($categoryLead === null && $items === []) {
                continue;
            }

            $categoryBlocks[] = [
                'category' => $category,
                'lead' => $categoryLead,
                'items' => $items,
            ];
        }

        $guidesLead = $this->resolveEditorialSlot(
            ContentHomePlacement::SLOT_GUIDES_LEAD,
            null,
            1,
            $at,
            $used,
            null,
            ContentArticleType::Guide,
            includeManualPlacements: $includeManualPlacements,
        )[0] ?? null;

        $guideItems = $this->resolveChronological(
            $guidesItemsLimit,
            $at,
            $used,
            null,
            ContentArticleType::Guide,
        );

        $importantNow = $this->resolveEditorialSlot(
            ContentHomePlacement::SLOT_IMPORTANT_NOW,
            null,
            $importantNowLimit,
            $at,
            $used,
            includeManualPlacements: $includeManualPlacements,
        );

        return [
            'lead' => $lead,
            'secondary' => $secondary,
            'latest' => $latest,
            'categories' => $categoryBlocks,
            'guides' => [
                'lead' => $guidesLead,
                'items' => $guideItems,
            ],
            'important_now' => $importantNow,
            'breaking' => $this->resolveBreaking($at),
        ];
    }

    /**
     * @param  array<int, true>  $used
     * @return list<ContentArticle>
     */
    private function resolveEditorialSlot(
        string $slotKey,
        ?string $contextKey,
        int $limit,
        Carbon $at,
        array &$used,
        ?ContentCategory $category = null,
        ?ContentArticleType $type = null,
        bool $includeManualPlacements = true,
        ?iterable $placementCandidates = null,
        ?iterable $fallbackCandidates = null,
    ): array {
        if ($limit === 0) {
            return [];
        }

        $resolved = [];

        if ($includeManualPlacements) {
            $placements = $placementCandidates ?? ContentHomePlacement::query()
                ->activeAt($at)
                ->where('surface_key', ContentHomePlacement::SURFACE_NEWSROOM_HOME)
                ->where('slot_key', $slotKey)
                ->where('context_key', $contextKey)
                ->with(['article.category', 'article.author'])
                ->orderBy('position')
                ->orderBy('id')
                ->limit(self::CANDIDATE_WINDOW)
                ->get();

            foreach ($placements as $placement) {
                $article = $placement->article;

                if (
                    $article === null
                    || isset($used[(int) $article->getKey()])
                    || ! $this->matchesSlotContext($article, $slotKey, $category, $type)
                    || ! $this->isArticleEligibleAt($article, $at)
                ) {
                    continue;
                }

                $this->appendCandidate($resolved, $used, $article);

                if (count($resolved) >= $limit) {
                    return $resolved;
                }
            }
        }

        if ($fallbackCandidates === null) {
            $query = $this->candidateQuery($at);

            $this->applyContextFilters($query, $category, $type);

            $query
                ->orderByDesc('is_featured')
                ->orderByDesc('editorial_priority')
                ->orderByRaw('COALESCE(first_published_at, scheduled_for) DESC')
                ->orderByDesc('id');

            $fallbackCandidates = $query->limit(self::CANDIDATE_WINDOW)->get();
        }

        foreach ($fallbackCandidates as $article) {
            if (
                isset($used[(int) $article->getKey()])
                || ! $this->matchesSlotContext($article, $slotKey, $category, $type)
                || ! $this->isArticleEligibleAt($article, $at)
            ) {
                continue;
            }

            $this->appendCandidate($resolved, $used, $article);

            if (count($resolved) >= $limit) {
                break;
            }
        }

        return $resolved;
    }

    /**
     * @param  array<int, true>  $used
     * @return list<ContentArticle>
     */
    private function resolveChronological(
        int $limit,
        Carbon $at,
        array &$used,
        ?ContentCategory $category = null,
        ?ContentArticleType $type = null,
        ?iterable $fallbackCandidates = null,
    ): array {
        if ($limit === 0) {
            return [];
        }

        if ($fallbackCandidates === null) {
            $query = $this->candidateQuery($at);
            $this->applyContextFilters($query, $category, $type);

            $query
                ->orderByRaw('COALESCE(first_published_at, scheduled_for) DESC')
                ->orderByDesc('id');

            $fallbackCandidates = $query->limit(self::CANDIDATE_WINDOW)->get();
        }

        $resolved = [];

        foreach ($fallbackCandidates as $article) {
            if (
                isset($used[(int) $article->getKey()])
                || ($category !== null && (int) $article->category_id !== (int) $category->getKey())
                || ($type !== null && $article->type !== $type)
                || ! $this->isArticleEligibleAt($article, $at)
            ) {
                continue;
            }

            $this->appendCandidate($resolved, $used, $article);

            if (count($resolved) >= $limit) {
                break;
            }
        }

        return $resolved;
    }

    private function resolveBreaking(Carbon $at): ?ContentArticle
    {
        $query = $this->candidateQuery($at)
            ->where('type', ContentArticleType::News->value)
            ->where('is_breaking', true)
            ->whereNotNull('breaking_expires_at')
            ->where('breaking_expires_at', '>', $at)
            ->orderByDesc('editorial_priority')
            ->orderByRaw('COALESCE(first_published_at, scheduled_for) DESC')
            ->orderByDesc('id');

        foreach ($query->limit(self::CANDIDATE_WINDOW)->get() as $article) {
            if ($this->isArticleEligibleAt($article, $at)) {
                return $article;
            }
        }

        return null;
    }

    private function candidateQuery(Carbon $at, bool $withRelations = true): Builder
    {
        $now = now();
        $includeScheduledPreview = $at->gt($now);

        $query = ContentArticle::query()
            ->where(function (Builder $query) use ($at, $includeScheduledPreview): void {
                $query->where(function (Builder $published) use ($at): void {
                    $published
                        ->activelyDistributed()
                        ->where('published_at', '<=', $at);
                });

                if ($includeScheduledPreview) {
                    $query->orWhere(function (Builder $scheduled) use ($at): void {
                        $scheduled
                            ->where('workflow_status', ContentArticleWorkflowStatus::Scheduled->value)
                            ->whereNull('first_published_at')
                            ->whereNotNull('scheduled_for')
                            ->where('scheduled_for', '<=', $at);
                    });
                }
            });

        if ($withRelations) {
            $query->with(['category', 'author']);
        }

        return $query;
    }

    /**
     * @return Collection<string, Collection<int, ContentHomePlacement>>
     */
    private function categoryPlacementCandidates(Carbon $at): Collection
    {
        return ContentHomePlacement::query()
            ->activeAt($at)
            ->where('surface_key', ContentHomePlacement::SURFACE_NEWSROOM_HOME)
            ->where('slot_key', ContentHomePlacement::SLOT_CATEGORY_LEAD)
            ->whereNotNull('context_key')
            ->with(['article.category', 'article.author'])
            ->orderBy('context_key')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ContentHomePlacement $placement): string => (string) $placement->context_key);
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return array{
     *     editorial: Collection<int, list<ContentArticle>>,
     *     chronological: Collection<int, list<ContentArticle>>
     * }
     */
    private function categoryCandidatePools(Carbon $at, Collection $categories): array
    {
        $categoryIds = $categories
            ->map(fn (ContentCategory $category): int => (int) $category->getKey())
            ->values()
            ->all();

        if ($categoryIds === []) {
            return [
                'editorial' => collect(),
                'chronological' => collect(),
            ];
        }

        $editorialRows = $this->rankedCategoryCandidateRows($at, $categoryIds, editorial: true);
        $chronologicalRows = $this->rankedCategoryCandidateRows($at, $categoryIds, editorial: false);

        $articleIds = collect($editorialRows)
            ->concat($chronologicalRows)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($articleIds === []) {
            return [
                'editorial' => collect(),
                'chronological' => collect(),
            ];
        }

        $articles = ContentArticle::query()
            ->with(['category', 'author'])
            ->whereIn('id', $articleIds)
            ->get()
            ->keyBy(fn (ContentArticle $article): int => (int) $article->getKey());

        return [
            'editorial' => $this->hydrateRankedCategoryRows($editorialRows, $articles),
            'chronological' => $this->hydrateRankedCategoryRows($chronologicalRows, $articles),
        ];
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<object{id:int,category_id:int,candidate_rank:int}>
     */
    private function rankedCategoryCandidateRows(
        Carbon $at,
        array $categoryIds,
        bool $editorial,
    ): array {
        $order = $editorial
            ? 'is_featured DESC, editorial_priority DESC, COALESCE(first_published_at, scheduled_for) DESC, id DESC'
            : 'COALESCE(first_published_at, scheduled_for) DESC, id DESC';

        $ranked = $this->candidateQuery($at, withRelations: false)
            ->whereIn('category_id', $categoryIds)
            ->select(['id', 'category_id'])
            ->selectRaw(
                "ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY {$order}) AS candidate_rank",
            );

        return DB::query()
            ->fromSub($ranked->toBase(), 'ranked_newsroom_candidates')
            ->where('candidate_rank', '<=', self::CANDIDATE_WINDOW)
            ->orderBy('category_id')
            ->orderBy('candidate_rank')
            ->get(['id', 'category_id', 'candidate_rank'])
            ->all();
    }

    /**
     * @param  list<object{id:int,category_id:int,candidate_rank:int}>  $rows
     * @param  Collection<int, ContentArticle>  $articles
     * @return Collection<int, list<ContentArticle>>
     */
    private function hydrateRankedCategoryRows(array $rows, Collection $articles): Collection
    {
        return collect($rows)
            ->groupBy(fn (object $row): int => (int) $row->category_id)
            ->map(fn (Collection $categoryRows): array => $categoryRows
                ->map(fn (object $row): ?ContentArticle => $articles->get((int) $row->id))
                ->filter(fn (?ContentArticle $article): bool => $article instanceof ContentArticle)
                ->values()
                ->all());
    }

    private function applyContextFilters(
        Builder $query,
        ?ContentCategory $category,
        ?ContentArticleType $type,
    ): void {
        if ($category !== null) {
            $query->where('category_id', $category->getKey());
        }

        if ($type !== null) {
            $query->where('type', $type->value);
        }
    }

    private function matchesSlotContext(
        ContentArticle $article,
        string $slotKey,
        ?ContentCategory $category,
        ?ContentArticleType $type,
    ): bool {
        if (
            $slotKey === ContentHomePlacement::SLOT_CATEGORY_LEAD
            && ($category === null || (int) $article->category_id !== (int) $category->getKey())
        ) {
            return false;
        }

        if ($type !== null && $article->type !== $type) {
            return false;
        }

        return true;
    }

    public function isArticleEligibleAt(ContentArticle $article, DateTimeInterface $at): bool
    {
        $at = $this->normalizeAt($at);
        if ($article->workflow_status === ContentArticleWorkflowStatus::Published) {
            return $article->isActivelyDistributed()
                && $article->published_at !== null
                && $article->published_at->lte($at);
        }

        if (
            $article->workflow_status !== ContentArticleWorkflowStatus::Scheduled
            || ! $at->gt(now())
        ) {
            return false;
        }

        try {
            $this->publishingService->assertScheduledPreviewReady($article, $at);

            return true;
        } catch (DomainException) {
            return false;
        }
    }

    /**
     * @param  list<ContentArticle>  $resolved
     * @param  array<int, true>  $used
     */
    private function appendCandidate(
        array &$resolved,
        array &$used,
        ContentArticle $article,
    ): void {
        $articleId = (int) $article->getKey();
        $used[$articleId] = true;
        $resolved[] = $article;
    }

    private function normalizeAt(?DateTimeInterface $at): Carbon
    {
        return $at === null
            ? now()
            : Carbon::parse($at->format(DATE_ATOM));
    }

    private function assertLimit(int $value, string $name, int $max): void
    {
        if ($value < 0 || $value > $max) {
            throw new InvalidArgumentException("{$name} must be between 0 and {$max}.");
        }
    }
}
