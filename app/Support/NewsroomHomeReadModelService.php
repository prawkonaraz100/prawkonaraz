<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;

final class NewsroomHomeReadModelService
{
    public function __construct(
        private readonly NewsroomHomeCompositionService $compositionService,
        private readonly NewsroomPublicGate $publicGate,
        private readonly NewsroomPublicReadCache $cache,
        private readonly MediaUrlResolver $mediaUrlResolver,
    ) {}

    /**
     * Public-only projection for the future /aktualnosci renderer.
     *
     * Returning null while the rollout gate is disabled makes the dark-deploy
     * boundary part of the read-model contract. Admin preview continues to use
     * NewsroomHomeCompositionService directly and is intentionally unaffected.
     *
     * @return array{
     *     lead: ?array<string,mixed>,
     *     secondary: list<array<string,mixed>>,
     *     latest: list<array<string,mixed>>,
     *     categories: list<array{
     *         category: array{id:int,name:string,slug:string,description:?string},
     *         lead: ?array<string,mixed>,
     *         items: list<array<string,mixed>>
     *     }>,
     *     guides: array{
     *         lead: ?array<string,mixed>,
     *         items: list<array<string,mixed>>
     *     },
     *     important_now: list<array<string,mixed>>,
     *     breaking: ?array<string,mixed>
     * }|null
     */
    public function build(): ?array
    {
        if ($this->publicGate->disabled()) {
            return null;
        }

        return $this->cache->rememberHome(fn (): array => $this->buildFresh());
    }

    /** @return array<string,mixed> */
    private function buildFresh(): array
    {
        $composition = $this->compositionService->compose();

        return [
            'lead' => $this->article($composition['lead']),
            'secondary' => $this->articles($composition['secondary']),
            'latest' => $this->articles($composition['latest']),
            'categories' => collect($composition['categories'])
                ->map(fn (array $block): array => [
                    'category' => $this->category($block['category']),
                    'lead' => $this->article($block['lead']),
                    'items' => $this->articles($block['items']),
                ])
                ->values()
                ->all(),
            'guides' => [
                'lead' => $this->article($composition['guides']['lead']),
                'items' => $this->articles($composition['guides']['items']),
            ],
            'important_now' => $this->articles($composition['important_now']),
            'breaking' => $this->article($composition['breaking']),
        ];
    }

    /**
     * @param  iterable<ContentArticle>  $articles
     * @return list<array<string,mixed>>
     */
    private function articles(iterable $articles): array
    {
        $presented = [];

        foreach ($articles as $article) {
            $presented[] = $this->article($article);
        }

        return $presented;
    }

    /** @return array<string,mixed>|null */
    private function article(?ContentArticle $article): ?array
    {
        if (! $article instanceof ContentArticle) {
            return null;
        }

        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::tryFrom((string) $article->type);

        if (! $type instanceof ContentArticleType) {
            return null;
        }

        $category = $article->relationLoaded('category')
            ? $article->category
            : null;
        $author = $article->relationLoaded('author')
            ? $article->author
            : null;
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
            'is_featured' => (bool) $article->is_featured,
            'is_breaking' => (bool) $article->is_breaking,
            'category' => $category instanceof ContentCategory
                ? $this->category($category)
                : null,
            'author' => $author instanceof ContentAuthor
                ? $this->author($author)
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

    /** @return array{id:int,name:string,slug:string,description:?string} */
    private function category(ContentCategory $category): array
    {
        return [
            'id' => (int) $category->getKey(),
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'description' => filled($category->description) ? (string) $category->description : null,
        ];
    }

    /** @return array{id:int,name:string,slug:string,job_title:?string,url:string} */
    private function author(ContentAuthor $author): array
    {
        return [
            'id' => (int) $author->getKey(),
            'name' => (string) $author->name,
            'slug' => (string) $author->slug,
            'job_title' => filled($author->job_title) ? (string) $author->job_title : null,
            'url' => route('content-authors.show', $author->slug, false),
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
