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

        foreach ($categories as $category) {
            $categoryLead = $this->resolveEditorialSlot(
                ContentHomePlacement::SLOT_CATEGORY_LEAD,
                (string) $category->slug,
                1,
                $at,
                $used,
                $category,
                includeManualPlacements: $includeManualPlacements,
            )[0] ?? null;

            $items = $this->resolveChronological(
                $categoryItemsLimit,
                $at,
                $used,
                $category,
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
    ): array {
        if ($limit === 0) {
            return [];
        }

        $resolved = [];

        if ($includeManualPlacements) {
            $placements = ContentHomePlacement::query()
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

        $query = $this->candidateQuery($at);

        $this->applyContextFilters($query, $category, $type);

        $query
            ->orderByDesc('is_featured')
            ->orderByDesc('editorial_priority')
            ->orderByRaw('COALESCE(first_published_at, scheduled_for) DESC')
            ->orderByDesc('id');

        foreach ($query->limit(self::CANDIDATE_WINDOW)->get() as $article) {
            if (
                isset($used[(int) $article->getKey()])
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
    ): array {
        if ($limit === 0) {
            return [];
        }

        $query = $this->candidateQuery($at);
        $this->applyContextFilters($query, $category, $type);

        $query
            ->orderByRaw('COALESCE(first_published_at, scheduled_for) DESC')
            ->orderByDesc('id');

        $resolved = [];

        foreach ($query->limit(self::CANDIDATE_WINDOW)->get() as $article) {
            if (
                isset($used[(int) $article->getKey()])
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

    private function candidateQuery(Carbon $at): Builder
    {
        $now = now();
        $includeScheduledPreview = $at->gt($now);

        return ContentArticle::query()
            ->with(['category', 'author'])
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
