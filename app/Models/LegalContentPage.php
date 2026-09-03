<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalContentPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    protected $fillable = [
        'legal_topic_id',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'intro',
        'summary',
        'exam_context',
        'body',
        'key_points',
        'source_note',
        'author_id',
        'reviewer_id',
        'published_at',
        'last_reviewed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'key_points' => 'array',
            'published_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LegalTopic::class, 'legal_topic_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'reviewer_id');
    }

    public function legalUnits(): BelongsToMany
    {
        return $this->belongsToMany(LegalUnit::class, 'legal_content_page_legal_unit')
            ->withPivot(['relation_type', 'note', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function questionLegalReferences(): HasMany
    {
        return $this->hasMany(QuestionLegalReference::class);
    }

    public function trafficSignLegalReferences(): HasMany
    {
        return $this->hasMany(TrafficSignLegalReference::class);
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
