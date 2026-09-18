<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Models\ContentTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomTopicReadModelService
{
    public const PER_PAGE = 20;

    public const DISPOSITION_VISIBLE = 'visible';

    public const DISPOSITION_GONE = 'gone';

    public const DISPOSITION_NOT_FOUND = 'not_found';

    public function __construct(
        private readonly NewsroomPublicGate $publicGate,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{
     *     disposition:string,
     *     topic?:array{id:int,title:string,slug:string,description:string,seo_title:?string,seo_description:?string,published_at:?string},
     *     featured?:?array<string,mixed>,
     *     articles?:LengthAwarePaginator<int,array<string,mixed>>
     * }
     */
    public function resolve(string $topicSlug, int $page = 1): array
    {
        if ($this->publicGate->disabled() || $page < 1) {
            return ['disposition' => self::DISPOSITION_NOT_FOUND];
        }

        $topicSlug = trim($topicSlug);

        if ($topicSlug === '') {
            return ['disposition' => self::DISPOSITION_NOT_FOUND];
        }

        $topic = ContentTopic::query()
            ->select([
                'id',
                'title',
                'slug',
                'description',
                'status',
                'featured_article_id',
                'seo_title',
                'seo_description',
                'published_at',
            ])
            ->where('slug', $topicSlug)
            ->first();

        if (! $topic instanceof ContentTopic) {
            return ['disposition' => self::DISPOSITION_NOT_FOUND];
        }

        if (
            $topic->status === ContentTopic::STATUS_ARCHIVED
            && $topic->published_at !== null
            && $topic->published_at->lte(now())
        ) {
            return ['disposition' => self::DISPOSITION_GONE];
        }

        if (! $topic->isPubliclyVisible()) {
            return ['disposition' => self::DISPOSITION_NOT_FOUND];
        }

        $featured = null;

        if ($page === 1 && $topic->featured_article_id !== null) {
            $featuredArticle = $this->eligibleArticlesQuery($topic)
                ->whereKey($topic->featured_article_id)
                ->first();

            if ($featuredArticle instanceof ContentArticle) {
                $featured = $this->article($featuredArticle);
            }
        }

        $articlesQuery = $this->eligibleArticlesQuery($topic);

        if ($topic->featured_article_id !== null) {
            $articlesQuery->whereKeyNot($topic->featured_article_id);
        }

        $articles = $articlesQuery->paginate(self::PER_PAGE, ['*'], 'page', $page);

        if ($page > 1 && $articles->isEmpty()) {
            return ['disposition' => self::DISPOSITION_NOT_FOUND];
        }

        $articles->setCollection(
            $articles->getCollection()
                ->map(fn (ContentArticle $article): array => $this->article($article))
                ->values(),
        );

        return [
            'disposition' => self::DISPOSITION_VISIBLE,
            'topic' => [
                'id' => (int) $topic->getKey(),
                'title' => (string) $topic->title,
                'slug' => (string) $topic->slug,
                'description' => (string) $topic->description,
                'seo_title' => filled($topic->seo_title) ? (string) $topic->seo_title : null,
                'seo_description' => filled($topic->seo_description) ? (string) $topic->seo_description : null,
                'published_at' => $topic->published_at?->toIso8601String(),
            ],
            'featured' => $featured,
            'articles' => $articles,
        ];
    }

    private function eligibleArticlesQuery(ContentTopic $topic): Builder
    {
        return ContentArticle::query()
            ->activelyDistributed()
            ->indexable()
            ->whereIn('type', [
                ...NewsroomRouteContract::NEWSROOM_TYPES,
                ...NewsroomRouteContract::GUIDE_TYPES,
            ])
            ->whereHas('topics', fn (Builder $query): Builder => $query->whereKey($topic->getKey()))
            ->with([
                'category:id,name,slug,description,is_active',
                'author:id,name,slug,job_title,photo_path,is_published,published_at',
            ])
            ->orderByDesc('first_published_at')
            ->orderByDesc('id');
    }

    /** @return array<string,mixed> */
    private function article(ContentArticle $article): array
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::from((string) $article->type);
        $category = $article->relationLoaded('category') ? $article->category : null;
        $author = $article->relationLoaded('author') ? $article->author : null;
        $heroUrl = $this->mediaUrlResolver->resolve(
            $article->hero_image_path,
            (string) config('media.newsroom_disk', config('media.public_disk', 'public')),
        );

        return [
            'id' => (int) $article->getKey(),
            'type' => $type->value,
            'title' => (string) $article->title,
            'slug' => (string) $article->slug,
            'url' => NewsroomRouteContract::canonicalPath($type->value, (string) $article->slug),
            'lead' => filled($article->lead) ? (string) $article->lead : null,
            'first_published_at' => $article->first_published_at?->toIso8601String(),
            'category' => $category instanceof ContentCategory
                ? [
                    'id' => (int) $category->getKey(),
                    'name' => (string) $category->name,
                    'slug' => (string) $category->slug,
                ]
                : null,
            'author' => $author instanceof ContentAuthor
                ? [
                    'id' => (int) $author->getKey(),
                    'name' => (string) $author->name,
                    'slug' => (string) $author->slug,
                    'url' => route('content-authors.show', $author->slug, false),
                ]
                : null,
            'hero' => $heroUrl === null
                ? null
                : [
                    'url' => $heroUrl,
                    'alt' => filled($article->hero_image_alt) ? (string) $article->hero_image_alt : '',
                    'width' => $article->hero_image_width !== null ? (int) $article->hero_image_width : null,
                    'height' => $article->hero_image_height !== null ? (int) $article->hero_image_height : null,
                    'object_position' => $this->objectPosition($article->hero_focal_x, $article->hero_focal_y),
                ],
        ];
    }

    private function objectPosition(mixed $x, mixed $y): ?string
    {
        if (! is_numeric($x) || ! is_numeric($y)) {
            return null;
        }

        $x = min(max((float) $x, 0.0), 1.0) * 100;
        $y = min(max((float) $y, 0.0), 1.0) * 100;

        return number_format($x, 2, '.', '').'% '.number_format($y, 2, '.', '').'%';
    }
}
