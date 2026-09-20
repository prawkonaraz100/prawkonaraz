<?php

namespace App\Models;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use Database\Factories\ContentArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentArticle extends Model
{
    /** @use HasFactory<ContentArticleFactory> */
    use HasFactory;

    protected $fillable = [
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
        'editorial_note',
        'regulatory_status',
        'effective_from',
        'change_summary',
        'applies_to',
        'exam_impact',
        'workflow_status',
        'published_at',
        'first_published_at',
        'scheduled_for',
        'reviewed_at',
        'needs_review_at',
        'archived_at',
        'withdrawn_at',
        'withdrawal_reason',
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
        'image_license_note',
        'seo_title',
        'seo_description',
        'robots',
        'source_checked_at',
        'freshness_review_due_at',
        'last_substantive_update_at',
        'public_state_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContentArticleType::class,
            'origin_type' => ContentArticleOriginType::class,
            'regulatory_status' => ContentArticleRegulatoryStatus::class,
            'workflow_status' => ContentArticleWorkflowStatus::class,
            'body_blocks' => 'array',
            'body_schema_version' => 'integer',
            'key_points' => 'array',
            'effective_from' => 'immutable_date',
            'published_at' => 'immutable_datetime',
            'first_published_at' => 'immutable_datetime',
            'scheduled_for' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'needs_review_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'withdrawn_at' => 'immutable_datetime',
            'is_featured' => 'boolean',
            'is_breaking' => 'boolean',
            'breaking_expires_at' => 'immutable_datetime',
            'editorial_priority' => 'integer',
            'hero_image_width' => 'integer',
            'hero_image_height' => 'integer',
            'hero_focal_x' => 'decimal:4',
            'hero_focal_y' => 'decimal:4',
            'og_image_width' => 'integer',
            'og_image_height' => 'integer',
            'source_checked_at' => 'immutable_datetime',
            'freshness_review_due_at' => 'immutable_datetime',
            'last_substantive_update_at' => 'immutable_datetime',
            'public_state_changed_at' => 'immutable_datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'reviewer_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentTag::class,
            'content_article_tag',
            'article_id',
            'tag_id',
        )->withPivot('created_at');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentTopic::class,
            'content_article_topic',
            'article_id',
            'topic_id',
        )->withPivot('created_at');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ContentArticleSource::class, 'article_id')->orderBy('sort_order');
    }

    public function homePlacements(): HasMany
    {
        return $this->hasMany(ContentHomePlacement::class, 'article_id');
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(ContentArticleRedirect::class, 'article_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,
            'content_article_question',
            'article_id',
            'question_id',
        )
            ->withPivot(['relation_type', 'sort_order', 'note'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function legalUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            LegalUnit::class,
            'content_article_legal_unit',
            'article_id',
            'legal_unit_id',
        )
            ->withPivot(['relation_type', 'sort_order', 'note'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function trafficSigns(): BelongsToMany
    {
        return $this->belongsToMany(
            TrafficSign::class,
            'content_article_traffic_sign',
            'article_id',
            'traffic_sign_id',
        )
            ->withPivot(['relation_type', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereNotNull('first_published_at')
            ->where('first_published_at', '<=', now())
            ->whereIn('workflow_status', [
                ContentArticleWorkflowStatus::Published->value,
                ContentArticleWorkflowStatus::NeedsReview->value,
                ContentArticleWorkflowStatus::Archived->value,
            ]);
    }

    public function scopeActivelyDistributed(Builder $query): Builder
    {
        return $query
            ->where('workflow_status', ContentArticleWorkflowStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query
            ->publiclyVisible()
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('robots')
                    ->orWhereRaw('LOWER(robots) NOT LIKE ?', ['%noindex%']);
            });
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('workflow_status', ContentArticleWorkflowStatus::Scheduled->value);
    }

    public function scopeForCategory(Builder $query, ContentCategory|int $category): Builder
    {
        $categoryId = $category instanceof ContentCategory ? $category->getKey() : $category;

        return $query->where('category_id', $categoryId);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeActiveBreaking(Builder $query): Builder
    {
        return $query
            ->activelyDistributed()
            ->where('type', ContentArticleType::News->value)
            ->where('is_breaking', true)
            ->whereNotNull('breaking_expires_at')
            ->where('breaking_expires_at', '>', now());
    }

    public function scopeNeedsFreshnessReview(Builder $query): Builder
    {
        return $query
            ->whereNotNull('freshness_review_due_at')
            ->where('freshness_review_due_at', '<=', now());
    }

    public function freshnessStatus(): string
    {
        if ($this->freshness_review_due_at === null) {
            return 'not_scheduled';
        }

        return $this->freshness_review_due_at->lte(now())
            ? 'overdue'
            : 'fresh';
    }

    public function isPubliclyVisible(): bool
    {
        return $this->first_published_at !== null
            && $this->first_published_at->lte(now())
            && in_array($this->workflow_status, [
                ContentArticleWorkflowStatus::Published,
                ContentArticleWorkflowStatus::NeedsReview,
                ContentArticleWorkflowStatus::Archived,
            ], true);
    }

    public function isActivelyDistributed(): bool
    {
        return $this->workflow_status === ContentArticleWorkflowStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isIndexable(): bool
    {
        return $this->isPubliclyVisible()
            && ! str_contains(strtolower((string) $this->robots), 'noindex');
    }

    public function hasActiveCategory(): bool
    {
        return $this->category()->active()->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
