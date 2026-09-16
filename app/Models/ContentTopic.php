<?php

namespace App\Models;

use Database\Factories\ContentTopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContentTopic extends Model
{
    /** @use HasFactory<ContentTopicFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const PUBLICATION_CORPUS_MINIMUM = 3;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'featured_article_id',
        'seo_title',
        'seo_description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
        ];
    }

    public function featuredArticle(): BelongsTo
    {
        return $this->belongsTo(ContentArticle::class, 'featured_article_id');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentArticle::class,
            'content_article_topic',
            'topic_id',
            'article_id',
        )->withPivot('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function meetsPublicationCorpusBaseline(): bool
    {
        return $this->articles()
            ->activelyDistributed()
            ->indexable()
            ->count() >= self::PUBLICATION_CORPUS_MINIMUM;
    }

    public function hasEligibleFeaturedArticle(): bool
    {
        if ($this->featured_article_id === null) {
            return true;
        }

        return $this->articles()
            ->whereKey($this->featured_article_id)
            ->activelyDistributed()
            ->indexable()
            ->exists();
    }

    public function meetsPublicationRequirements(): bool
    {
        return trim((string) $this->description) !== ''
            && $this->meetsPublicationCorpusBaseline()
            && $this->hasEligibleFeaturedArticle();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
