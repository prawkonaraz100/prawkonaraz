<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalTopic extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'sort_order',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function contentPages(): HasMany
    {
        return $this->hasMany(LegalContentPage::class);
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
}
