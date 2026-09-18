<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomCategoryReadModelService
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly ContentArticlePublicCatalogService $catalogService,
        private readonly NewsroomPublicGate $publicGate,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{
     *     category: array{
     *         id:int,
     *         name:string,
     *         slug:string,
     *         description:?string,
     *         seo_title:?string,
     *         seo_description:?string
     *     },
     *     articles: LengthAwarePaginator<int, array<string,mixed>>,
     *     related_categories: list<array{id:int,name:string,slug:string,url:string}>
     * }|null
     */
    public function build(string $categorySlug, int $page = 1): ?array
    {
        if ($this->publicGate->disabled() || $page < 1) {
            return null;
        }

        $category = ContentCategory::query()
            ->active()
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'position',
                'seo_title',
                'seo_description',
            ])
            ->where('slug', trim($categorySlug))
            ->first();

        if (! $category instanceof ContentCategory) {
            return null;
        }

        $articles = $this->catalogService
            ->activelyDistributedQuery(NewsroomRouteContract::FAMILY_NEWSROOM)
            ->where('category_id', $category->getKey())
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        if ($page > 1 && $articles->isEmpty()) {
            return null;
        }

        $articles->setCollection(
            $articles->getCollection()
                ->map(fn (ContentArticle $article): array => $this->article($article))
                ->values(),
        );

        $relatedCategories = ContentCategory::query()
            ->active()
            ->select(['id', 'name', 'slug', 'position'])
            ->whereKeyNot($category->getKey())
            ->whereHas('articles', function (Builder $query): void {
                $query
                    ->activelyDistributed()
                    ->whereIn('type', NewsroomRouteContract::NEWSROOM_TYPES);
            })
            ->orderBy('position')
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(fn (ContentCategory $related): array => [
                'id' => (int) $related->getKey(),
                'name' => (string) $related->name,
                'slug' => (string) $related->slug,
                'url' => route('public.news.categories.show', ['categorySlug' => $related->slug], false),
            ])
            ->values()
            ->all();

        return [
            'category' => [
                'id' => (int) $category->getKey(),
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
                'description' => filled($category->description) ? (string) $category->description : null,
                'seo_title' => filled($category->seo_title) ? (string) $category->seo_title : null,
                'seo_description' => filled($category->seo_description) ? (string) $category->seo_description : null,
            ],
            'articles' => $articles,
            'related_categories' => $relatedCategories,
        ];
    }

    /** @return array<string,mixed> */
    private function article(ContentArticle $article): array
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::from((string) $article->type);
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
