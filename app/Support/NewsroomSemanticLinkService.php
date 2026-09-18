<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use Illuminate\Database\Eloquent\Builder;

final class NewsroomSemanticLinkService
{
    public const RELATED_LIMIT = 4;

    public const REVERSE_LIMIT = 3;

    public function __construct(
        private readonly NewsroomPublicGate $publicGate,
    ) {}

    /**
     * @return list<array{id:int,title:string,slug:string,url:string}>
     */
    public function topics(ContentArticle $article): array
    {
        if ($this->publicGate->disabled()) {
            return [];
        }

        $topics = $article->relationLoaded('topics')
            ? $article->topics
            : $article->topics()
                ->published()
                ->get(['content_topics.id', 'title', 'slug', 'status', 'published_at']);

        return $topics
            ->filter(fn (ContentTopic $topic): bool => $topic->isPubliclyVisible())
            ->sortBy([
                ['title', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn (ContentTopic $topic): array => [
                'id' => (int) $topic->getKey(),
                'title' => (string) $topic->title,
                'slug' => (string) $topic->slug,
                'url' => route('public.news.topics.show', ['topicSlug' => $topic->slug], false),
            ])
            ->values()
            ->all();
    }

    /**
     * Deterministic resolver:
     * 1. shared published topics,
     * 2. same primary category,
     * 3. editorial priority,
     * 4. first publication date,
     * 5. id tie-breaker.
     *
     * @return list<array<string,mixed>>
     */
    public function relatedArticles(ContentArticle $article, int $limit = self::RELATED_LIMIT): array
    {
        if ($this->publicGate->disabled() || $limit < 1) {
            return [];
        }

        $topicIds = collect($this->topics($article))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $query = $this->eligibleArticleQuery()
            ->whereKeyNot($article->getKey())
            ->where(function (Builder $builder) use ($article, $topicIds): void {
                if ($article->category_id !== null) {
                    $builder->where('category_id', $article->category_id);
                }

                if ($topicIds !== []) {
                    $method = $article->category_id !== null ? 'orWhereHas' : 'whereHas';
                    $builder->{$method}('topics', fn (Builder $topicQuery): Builder => $topicQuery
                        ->published()
                        ->whereKey($topicIds));
                }
            });

        if ($topicIds !== []) {
            $query->withCount([
                'topics as shared_public_topic_count' => fn (Builder $topicQuery): Builder => $topicQuery
                    ->published()
                    ->whereKey($topicIds),
            ])->orderByDesc('shared_public_topic_count');
        }

        if ($article->category_id !== null) {
            $query->orderByRaw(
                'CASE WHEN content_articles.category_id = ? THEN 0 ELSE 1 END',
                [(int) $article->category_id],
            );
        }

        return $query
            ->orderByDesc('editorial_priority')
            ->orderByDesc('first_published_at')
            ->orderByDesc('id')
            ->limit(min(max($limit, 1), self::RELATED_LIMIT))
            ->get()
            ->map(fn (ContentArticle $related): array => $this->articleCard($related))
            ->values()
            ->all();
    }

    /**
     * @param  iterable<int,mixed>  $questionIds
     * @return list<array<string,mixed>>
     */
    public function forQuestions(iterable $questionIds, int $limit = self::REVERSE_LIMIT): array
    {
        $ids = $this->ids($questionIds);

        if ($ids === []) {
            return [];
        }

        return $this->reverseArticles(
            fn (Builder $query): Builder => $query->whereHas(
                'questions',
                fn (Builder $relation): Builder => $relation->whereKey($ids),
            ),
            $limit,
        );
    }

    /**
     * @param  iterable<int,mixed>  $legalUnitIds
     * @return list<array<string,mixed>>
     */
    public function forLegalUnits(iterable $legalUnitIds, int $limit = self::REVERSE_LIMIT): array
    {
        $ids = $this->ids($legalUnitIds);

        if ($ids === []) {
            return [];
        }

        return $this->reverseArticles(
            fn (Builder $query): Builder => $query->whereHas(
                'legalUnits',
                fn (Builder $relation): Builder => $relation->whereKey($ids),
            ),
            $limit,
        );
    }

    /** @return list<array<string,mixed>> */
    public function forTrafficSign(int $trafficSignId, int $limit = self::REVERSE_LIMIT): array
    {
        if ($trafficSignId < 1) {
            return [];
        }

        return $this->reverseArticles(
            fn (Builder $query): Builder => $query->whereHas(
                'trafficSigns',
                fn (Builder $relation): Builder => $relation
                    ->whereKey($trafficSignId)
                    ->whereIn('content_article_traffic_sign.relation_type', ['direct', 'example']),
            ),
            $limit,
        );
    }

    /**
     * @return array{
     *     has_crawlable_inbound:bool,
     *     hub:?array{label:string,url:string},
     *     category:?array{label:string,url:string},
     *     topics:list<array{id:int,title:string,slug:string,url:string}>,
     *     author:?array{label:string,url:string},
     *     inbound_sources:list<array{kind:string,label:string,url:string}>,
     *     explicit_reverse_edge_count:int,
     *     estimated_hub_depth:?int
     * }
     */
    public function audit(ContentArticle $article): array
    {
        $gateEnabled = $this->publicGate->enabled();
        $isIndexable = $gateEnabled && $article->isIndexable();
        $isActive = $isIndexable && $article->isActivelyDistributed();
        $topics = $isIndexable ? $this->topics($article) : [];
        $category = null;
        $author = null;
        $hub = null;
        $inboundSources = [];

        if ($article->category instanceof ContentCategory && $article->category->is_active) {
            $category = [
                'label' => (string) $article->category->name,
                'url' => route('public.news.categories.show', ['categorySlug' => $article->category->slug], false),
            ];
        }

        if ($article->author instanceof ContentAuthor && $article->author->isPubliclyVisible()) {
            $author = [
                'label' => (string) $article->author->name,
                'url' => route('content-authors.show', ['authorSlug' => $article->author->slug], false),
            ];
        }

        if ($isActive) {
            $type = $article->type instanceof ContentArticleType
                ? $article->type->value
                : (string) $article->type;
            $family = NewsroomRouteContract::familyForType($type);
            $hub = $family === NewsroomRouteContract::FAMILY_GUIDES
                ? ['label' => 'Poradniki', 'url' => route('public.guides', absolute: false)]
                : ['label' => 'Aktualności', 'url' => route('public.news', absolute: false)];

            $inboundSources[] = [
                'kind' => 'hub',
                ...$hub,
            ];

            if ($family === NewsroomRouteContract::FAMILY_NEWSROOM && $category !== null) {
                $inboundSources[] = [
                    'kind' => 'category',
                    ...$category,
                ];
            }

            foreach ($topics as $topic) {
                $inboundSources[] = [
                    'kind' => 'topic',
                    'label' => $topic['title'],
                    'url' => $topic['url'],
                ];
            }
        }

        if ($isIndexable && $author !== null) {
            $inboundSources[] = [
                'kind' => 'author',
                ...$author,
            ];
        }

        $explicitReverseEdges = $article->questions()->count()
            + $article->legalUnits()->count()
            + $article->trafficSigns()->count();

        $estimatedHubDepth = match (true) {
            $isIndexable === false => null,
            $isActive && $hub !== null && $hub['url'] === route('public.guides', absolute: false) => 1,
            $isActive && $category !== null => 2,
            $author !== null => 3,
            default => null,
        };

        return [
            'has_crawlable_inbound' => $inboundSources !== [],
            'hub' => $hub,
            'category' => $category,
            'topics' => $topics,
            'author' => $author,
            'inbound_sources' => $inboundSources,
            'explicit_reverse_edge_count' => $explicitReverseEdges,
            'estimated_hub_depth' => $estimatedHubDepth,
        ];
    }

    /**
     * @param  callable(Builder):Builder  $constraint
     * @return list<array<string,mixed>>
     */
    private function reverseArticles(callable $constraint, int $limit): array
    {
        if ($this->publicGate->disabled() || $limit < 1) {
            return [];
        }

        $query = $constraint($this->eligibleArticleQuery());

        return $query
            ->orderByDesc('editorial_priority')
            ->orderByDesc('first_published_at')
            ->orderByDesc('id')
            ->limit(min(max($limit, 1), self::REVERSE_LIMIT))
            ->get()
            ->map(fn (ContentArticle $article): array => $this->articleCard($article))
            ->values()
            ->all();
    }

    private function eligibleArticleQuery(): Builder
    {
        return ContentArticle::query()
            ->activelyDistributed()
            ->indexable()
            ->whereIn('type', [
                ...NewsroomRouteContract::NEWSROOM_TYPES,
                ...NewsroomRouteContract::GUIDE_TYPES,
            ])
            ->whereHas('category', fn (Builder $query): Builder => $query->active())
            ->whereHas('author', fn (Builder $query): Builder => $query->published())
            ->with([
                'category:id,name,slug,is_active',
                'author:id,name,slug,is_published,published_at',
            ]);
    }

    /** @return array<string,mixed> */
    private function articleCard(ContentArticle $article): array
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        $url = NewsroomRouteContract::canonicalPath($type, (string) $article->slug);

        return [
            'id' => (int) $article->getKey(),
            'type' => $type,
            'title' => (string) $article->title,
            'lead' => filled($article->lead) ? (string) $article->lead : null,
            'url' => $url,
            'category' => $article->category instanceof ContentCategory
                ? (string) $article->category->name
                : null,
            'first_published_at' => $article->first_published_at?->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<int,mixed>  $values
     * @return list<int>
     */
    private function ids(iterable $values): array
    {
        return collect($values)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
