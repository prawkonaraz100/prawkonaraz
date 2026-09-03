<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalAct extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'slug',
        'title',
        'short_title',
        'publisher',
        'source_url',
        'eli_url',
        'isap_url',
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

    public function legalUnits(): HasMany
    {
        return $this->hasMany(LegalUnit::class);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
