<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use Illuminate\Pagination\LengthAwarePaginator;

final class NewsroomGuideHubReadModelService
{
    public const PER_PAGE = 20;

    public function __construct(
        private readonly ContentArticlePublicCatalogService $catalogService,
        private readonly NewsroomPublicGate $publicGate,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * @return array{
     *     guides: LengthAwarePaginator<int, array<string,mixed>>
     * }|null
     */
    public function build(int $page = 1): ?array
    {
        if ($this->publicGate->disabled() || $page < 1) {
            return null;
        }

        $guides = $this->catalogService
            ->activelyDistributedQuery(NewsroomRouteContract::FAMILY_GUIDES)
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        if ($page > 1 && $guides->isEmpty()) {
            return null;
        }

        $guides->setCollection(
            $guides->getCollection()
                ->map(fn (ContentArticle $article): array => $this->guide($article))
                ->values(),
        );

        return ['guides' => $guides];
    }

    /** @return array<string,mixed> */
    private function guide(ContentArticle $article): array
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
            'category' => $category instanceof \App\Models\ContentCategory
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
