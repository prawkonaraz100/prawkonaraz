<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\SEO\Schema\SchemaIds;
use App\SEO\Schema\SchemaRenderer;
use App\SEO\Schema\SiteIdentitySchema;
use DomainException;
use Illuminate\Support\Str;

final class ContentArticleSchemaService
{
    public function __construct(
        private readonly ContentArticleSeoService $seoService,
        private readonly NewsroomMediaStorage $mediaStorage,
        private readonly SchemaIds $schemaIds,
        private readonly SchemaRenderer $schemaRenderer,
        private readonly SiteIdentitySchema $siteIdentitySchema,
    ) {}

    /**
     * Build the canonical JSON-LD graph for one publicly visible newsroom article.
     *
     * @return array{@context:string,@graph:list<array<string,mixed>>}
     */
    public function article(ContentArticle $article): array
    {
        if (! $article->isPubliclyVisible()) {
            throw new DomainException('Structured data can only be generated for a publicly visible content article.');
        }

        $article->loadMissing([
            'author:id,name,slug,job_title,bio,linkedin_url,external_profile_url,is_published,published_at',
            'category:id,name,slug,is_active',
        ]);

        $author = $article->author;
        $category = $article->category;

        if (! $author instanceof ContentAuthor || ! $author->isPubliclyVisible()) {
            throw new DomainException('Public content article schema requires a published author.');
        }

        if (! $category instanceof ContentCategory || ! $category->isPublicationEligible()) {
            throw new DomainException('Public content article schema requires an active category.');
        }

        $meta = $this->seoService->article($article);
        $canonical = (string) $meta['canonical'];
        $webPageId = $this->schemaIds->contentArticleWebPage($canonical);
        $articleId = $this->schemaIds->contentArticle($canonical);
        $breadcrumbId = $this->schemaIds->contentArticleBreadcrumb($canonical);
        $authorId = $this->schemaIds->contentAuthorPerson($author);

        $images = $this->imageNodes($article, $canonical);
        $imageReferences = array_map(
            static fn (array $image): array => ['@id' => $image['@id']],
            $images,
        );
        $primaryImageId = $this->primaryImageId($article, $canonical, $images);

        return $this->schemaRenderer->graph([
            $this->siteIdentitySchema->organization(),
            $this->siteIdentitySchema->website(),
            $this->personNode($author, $authorId),
            $this->breadcrumbNode($article, $category, $canonical, $breadcrumbId),
            ...$images,
            array_filter([
                '@id' => $webPageId,
                '@type' => 'WebPage',
                'name' => $this->plainText((string) $article->title),
                'description' => $this->nonBlankString($meta['description'] ?? null),
                'url' => $canonical,
                'inLanguage' => 'pl-PL',
                'isPartOf' => [
                    '@id' => $this->schemaIds->website(),
                ],
                'breadcrumb' => [
                    '@id' => $breadcrumbId,
                ],
                'mainEntity' => [
                    '@id' => $articleId,
                ],
                'primaryImageOfPage' => $primaryImageId !== null
                    ? ['@id' => $primaryImageId]
                    : null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []),
            array_filter([
                '@id' => $articleId,
                '@type' => $this->articleSchemaType($article),
                'headline' => $this->plainText((string) $article->title),
                'description' => $this->nonBlankString($meta['description'] ?? null),
                'url' => $canonical,
                'mainEntityOfPage' => [
                    '@id' => $webPageId,
                ],
                'author' => [
                    '@id' => $authorId,
                ],
                'publisher' => [
                    '@id' => $this->schemaIds->organization(),
                ],
                'publishingPrinciples' => route('about.editorial-principles'),
                'isPartOf' => [
                    '@id' => $this->schemaIds->website(),
                ],
                'articleSection' => $this->plainText((string) $category->name),
                'inLanguage' => 'pl-PL',
                'datePublished' => $meta['published_time'] ?? null,
                'dateModified' => $meta['modified_time'] ?? null,
                'image' => $imageReferences !== [] ? $imageReferences : null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []),
        ]);
    }

    private function articleSchemaType(ContentArticle $article): string
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type
            : ContentArticleType::tryFrom((string) $article->type);

        if ($type === null) {
            throw new DomainException('Unsupported content article type for structured data.');
        }

        return $type === ContentArticleType::News ? 'NewsArticle' : 'Article';
    }

    /**
     * @return array<string,mixed>
     */
    private function personNode(ContentAuthor $author, string $authorId): array
    {
        $sameAs = array_values(array_filter([
            $this->nonBlankString($author->linkedin_url),
            $this->nonBlankString($author->external_profile_url),
        ]));

        return array_filter([
            '@id' => $authorId,
            '@type' => 'Person',
            'name' => $this->plainText((string) $author->name),
            'url' => route('content-authors.show', $author->slug),
            'jobTitle' => $this->nonBlankString($author->job_title),
            'description' => $this->nonBlankString($author->bio),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
            'worksFor' => [
                '@id' => $this->schemaIds->organization(),
            ],
        ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @return array<string,mixed>
     */
    private function breadcrumbNode(
        ContentArticle $article,
        ContentCategory $category,
        string $canonical,
        string $breadcrumbId,
    ): array {
        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;
        $family = NewsroomRouteContract::familyForType($type);

        $items = [
            ['name' => 'Strona główna', 'item' => route('home')],
        ];

        if ($family === NewsroomRouteContract::FAMILY_GUIDES) {
            $items[] = ['name' => 'Poradniki', 'item' => route('public.guides')];
        } else {
            $items[] = ['name' => 'Aktualności', 'item' => route('public.news')];
            $items[] = [
                'name' => $this->plainText((string) $category->name),
                'item' => route('public.news.categories.show', $category->slug),
            ];
        }

        $items[] = [
            'name' => $this->plainText((string) $article->title),
            'item' => $canonical,
        ];

        return [
            '@id' => $breadcrumbId,
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['item'],
                ],
                $items,
                array_keys($items),
            ),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function imageNodes(ContentArticle $article, string $canonical): array
    {
        $images = [];
        $seenUrls = [];

        if (filled($article->hero_image_path)) {
            $node = $this->imageNode(
                $this->schemaIds->contentArticleHeroImage($canonical),
                (string) $article->hero_image_path,
                $article->hero_image_alt,
                $article->hero_image_width,
                $article->hero_image_height,
                $article->hero_image_caption,
                $article->image_credit,
            );

            $images[] = $node;
            $seenUrls[] = $node['url'];
        }

        if (filled($article->og_image_path)) {
            $node = $this->imageNode(
                $this->schemaIds->contentArticleOgImage($canonical),
                (string) $article->og_image_path,
                $article->og_image_alt,
                $article->og_image_width,
                $article->og_image_height,
                null,
                $article->image_credit,
            );

            if (! in_array($node['url'], $seenUrls, true)) {
                $images[] = $node;
            }
        }

        return $images;
    }

    /**
     * @return array<string,mixed>
     */
    private function imageNode(
        string $id,
        string $path,
        mixed $alt,
        mixed $width,
        mixed $height,
        mixed $caption,
        mixed $credit,
    ): array {
        $url = $this->mediaStorage->publicUrl($path);

        return array_filter([
            '@id' => $id,
            '@type' => 'ImageObject',
            'url' => $url,
            'contentUrl' => $url,
            'name' => $this->nonBlankString($alt),
            'caption' => $this->nonBlankString($caption),
            'creditText' => $this->nonBlankString($credit),
            'width' => $this->positiveInt($width),
            'height' => $this->positiveInt($height),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  list<array<string,mixed>>  $images
     */
    private function primaryImageId(ContentArticle $article, string $canonical, array $images): ?string
    {
        if ($images === []) {
            return null;
        }

        if (filled($article->hero_image_path)) {
            return $this->schemaIds->contentArticleHeroImage($canonical);
        }

        return (string) ($images[0]['@id'] ?? '');
    }

    private function nonBlankString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = $this->plainText((string) $value);

        return $value !== '' ? $value : null;
    }

    private function plainText(string $value): string
    {
        return Str::squish(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
