<?php

namespace App\Support;

use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class ContentArticlePublicCatalogService
{
    private const DETAIL_COLUMNS = [
        'id',
        'type',
        'category_id',
        'author_id',
        'reviewer_id',
        'origin_type',
        'title',
        'slug',
        'lead',
        'body_blocks',
        'body_schema_version',
        'key_points',
        'correction_note',
        'regulatory_status',
        'effective_from',
        'change_summary',
        'applies_to',
        'exam_impact',
        'workflow_status',
        'published_at',
        'first_published_at',
        'reviewed_at',
        'needs_review_at',
        'archived_at',
        'is_featured',
        'is_breaking',
        'breaking_expires_at',
        'editorial_priority',
        'hero_image_path',
        'hero_image_alt',
        'hero_image_width',
        'hero_image_height',
        'hero_image_caption',
        'hero_focal_x',
        'hero_focal_y',
        'og_image_path',
        'og_image_alt',
        'og_image_width',
        'og_image_height',
        'image_credit',
        'seo_title',
        'seo_description',
        'robots',
        'source_checked_at',
        'last_substantive_update_at',
        'public_state_changed_at',
        'updated_at',
    ];

    private const LIST_COLUMNS = [
        'id',
        'type',
        'category_id',
        'author_id',
        'title',
        'slug',
        'lead',
        'workflow_status',
        'published_at',
        'first_published_at',
        'is_featured',
        'is_breaking',
        'breaking_expires_at',
        'editorial_priority',
        'hero_image_path',
        'hero_image_alt',
        'hero_image_width',
        'hero_image_height',
        'hero_focal_x',
        'hero_focal_y',
        'robots',
        'public_state_changed_at',
        'updated_at',
    ];

    private const GONE_COLUMNS = [
        'id',
        'type',
        'slug',
        'workflow_status',
        'first_published_at',
        'withdrawn_at',
        'public_state_changed_at',
    ];

    public function findPubliclyVisibleBySlug(string $family, string $slug): ?ContentArticle
    {
        $types = $this->typesForFamily($family);
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $article = ContentArticle::query()
            ->select(self::DETAIL_COLUMNS)
            ->publiclyVisible()
            ->whereIn('type', $types)
            ->where('slug', $slug)
            ->with($this->detailRelations())
            ->first();

        if (! $article instanceof ContentArticle || ! $this->hasValidCanonicalPath($article)) {
            return null;
        }

        return $article;
    }

    public function resolveDetailBySlug(string $family, string $slug): ContentArticlePublicResolution
    {
        $visible = $this->findPubliclyVisibleBySlug($family, $slug);

        if ($visible instanceof ContentArticle) {
            return ContentArticlePublicResolution::visible($visible);
        }

        $slug = trim($slug);

        if ($slug === '') {
            return ContentArticlePublicResolution::notFound();
        }

        $withdrawn = ContentArticle::query()
            ->select(self::GONE_COLUMNS)
            ->whereIn('type', $this->typesForFamily($family))
            ->where('workflow_status', ContentArticleWorkflowStatus::Withdrawn->value)
            ->whereNotNull('first_published_at')
            ->where('first_published_at', '<=', now())
            ->where('slug', $slug)
            ->first();

        if ($withdrawn instanceof ContentArticle && $this->hasValidCanonicalPath($withdrawn)) {
            return ContentArticlePublicResolution::gone($withdrawn);
        }

        return ContentArticlePublicResolution::notFound();
    }

    /**
     * Query boundary for hub/list/feed candidates.
     *
     * This intentionally uses activelyDistributed(), not publiclyVisible() or
     * indexable(). Consumers such as sitemaps may add indexable() separately.
     */
    public function activelyDistributedQuery(string $family): Builder
    {
        return ContentArticle::query()
            ->select(self::LIST_COLUMNS)
            ->activelyDistributed()
            ->whereIn('type', $this->typesForFamily($family))
            ->with([
                'category:id,name,slug,description,is_active',
                'author:id,name,slug,job_title,photo_path,is_published,published_at',
            ])
            ->orderByDesc('first_published_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<int, string|\Closure>
     */
    private function detailRelations(): array
    {
        return [
            'category:id,name,slug,description,is_active,seo_title,seo_description',
            'author:id,name,slug,job_title,bio,photo_path,is_published,published_at',
            'reviewer:id,name,slug,job_title,bio,photo_path,is_published,published_at',
            'sources' => fn (Builder $query): Builder => $query
                ->publiclyCited()
                ->select([
                    'id',
                    'article_id',
                    'source_type',
                    'publisher',
                    'title',
                    'url',
                    'published_at',
                    'accessed_at',
                    'is_primary',
                    'is_official',
                    'is_publicly_cited',
                    'sort_order',
                ]),
            'tags:id,name,slug',
            'topics' => fn (Builder $query): Builder => $query
                ->published()
                ->select([
                    'content_topics.id',
                    'title',
                    'slug',
                    'description',
                    'status',
                    'featured_article_id',
                    'published_at',
                ]),
        ];
    }

    /**
     * @return list<string>
     */
    private function typesForFamily(string $family): array
    {
        return match ($family) {
            NewsroomRouteContract::FAMILY_NEWSROOM => NewsroomRouteContract::NEWSROOM_TYPES,
            NewsroomRouteContract::FAMILY_GUIDES => NewsroomRouteContract::GUIDE_TYPES,
            default => throw new InvalidArgumentException("Unsupported newsroom route family [{$family}]."),
        };
    }

    private function hasValidCanonicalPath(ContentArticle $article): bool
    {
        $type = $article->type instanceof ContentArticleType
            ? $article->type->value
            : (string) $article->type;

        try {
            NewsroomRouteContract::canonicalPath($type, (string) $article->slug);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
