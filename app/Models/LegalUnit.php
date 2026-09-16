<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalUnit extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'legal_act_id',
        'parent_legal_unit_id',
        'type',
        'label',
        'canonical_path',
        'slug',
        'title',
        'summary',
        'official_excerpt',
        'source_url',
        'effective_from',
        'last_checked_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'last_checked_at' => 'datetime',
        ];
    }

    public function legalAct(): BelongsTo
    {
        return $this->belongsTo(LegalAct::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_legal_unit_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_legal_unit_id');
    }

    public function contentArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            ContentArticle::class,
            'content_article_legal_unit',
            'legal_unit_id',
            'article_id',
        )
            ->withPivot(['relation_type', 'sort_order', 'note'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function contentPages(): BelongsToMany
    {
        return $this->belongsToMany(LegalContentPage::class, 'legal_content_page_legal_unit')
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

    public function sourceChecks(): HasMany
    {
        return $this->hasMany(LegalSourceCheck::class);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
