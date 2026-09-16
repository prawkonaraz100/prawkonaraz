<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use DomainException;
use Illuminate\Support\Str;
use RuntimeException;

final class ContentArticleSeoService
{
    public const DEFAULT_ROBOTS = 'index,follow,max-image-preview:large';

    public function __construct(
        private readonly PublicUrlResolver $publicUrlResolver,
        private readonly NewsroomMediaStorage $mediaStorage,
    ) {}

    /**
     * Build layout-compatible SEO metadata for one publicly visible article.
     *
     * @return array<string, mixed>
     */
    public function article(ContentArticle $article): array
    {
        if (! $article->isPubliclyVisible()) {
            throw new DomainException('SEO metadata can only be generated for a publicly visible content article.');
        }

        $canonical = $this->canonicalUrl($article);
        $image = $this->socialImage($article);
        $publishedAt = $article->first_published_at;
        $modifiedAt = $article->last_substantive_update_at ?? $publishedAt;

        if ($publishedAt === null || $publishedAt->isFuture()) {
            throw new DomainException('Public content article must have a non-future first publication date.');
        }

        if ($modifiedAt === null || $modifiedAt->isFuture()) {
            throw new DomainException('Public content article must not expose a future modification date.');
        }

        return [
            'title' => $this->metaTitle($article),
            'description' => $this->metaDescription($article),
            'canonical' => $canonical,
            'robots' => filled($article->robots)
                ? trim((string) $article->robots)
                : self::DEFAULT_ROBOTS,
            'image' => $image['url'],
            'image_alt' => $image['alt'],
            'image_width' => $image['width'],
            'image_height' => $image['height'],
            'preload_image' => filled($article->hero_image_path)
                ? $this->mediaStorage->publicUrl((string) $article->hero_image_path)
                : null,
            'og_type' => 'article',
            'author_name' => $article->author?->name,
            'published_time' => $publishedAt->toIso8601String(),
            'modified_time' => $modifiedAt->toIso8601String(),
        ];
    }

    public function canonicalUrl(ContentArticle $article): string
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        $path = NewsroomRouteContract::canonicalPath($type, (string) $article->slug);
        $canonical = $this->publicUrlResolver->normalize($path);

        if (! is_string($canonical) || $canonical === '') {
            throw new RuntimeException('Content article canonical URL could not be resolved.');
        }

        return $canonical;
    }

    private function metaTitle(ContentArticle $article): string
    {
        $baseTitle = $this->plainText(
            filled($article->seo_title)
                ? (string) $article->seo_title
                : (string) $article->title,
        );
        $brand = $this->plainText((string) config('content.organization.name', config('app.name', 'PrawkoNaRaz')));

        if ($baseTitle === '') {
            return $brand;
        }

        if ($brand === '' || Str::contains(Str::lower($baseTitle), Str::lower($brand))) {
            return $baseTitle;
        }

        return $baseTitle.' - '.$brand;
    }

    private function metaDescription(ContentArticle $article): string
    {
        $description = $this->plainText(
            filled($article->seo_description)
                ? (string) $article->seo_description
                : (string) $article->lead,
        );

        return Str::limit($description, 160, '');
    }

    /**
     * @return array{url:?string,alt:?string,width:?int,height:?int}
     */
    private function socialImage(ContentArticle $article): array
    {
        if (filled($article->og_image_path)) {
            return [
                'url' => $this->mediaStorage->publicUrl((string) $article->og_image_path),
                'alt' => filled($article->og_image_alt) ? trim((string) $article->og_image_alt) : null,
                'width' => $article->og_image_width,
                'height' => $article->og_image_height,
            ];
        }

        if (filled($article->hero_image_path)) {
            return [
                'url' => $this->mediaStorage->publicUrl((string) $article->hero_image_path),
                'alt' => filled($article->hero_image_alt) ? trim((string) $article->hero_image_alt) : null,
                'width' => $article->hero_image_width,
                'height' => $article->hero_image_height,
            ];
        }

        return [
            'url' => null,
            'alt' => null,
            'width' => null,
            'height' => null,
        ];
    }

    private function plainText(string $value): string
    {
        return Str::squish(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
