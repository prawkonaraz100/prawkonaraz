<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedQueueEntry extends Model
{
    protected $fillable = [
        'user_id',
        'license_category_id',
        'ranked_match_id',
        'status',
        'joined_at',
        'matched_at',
        'left_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'license_category_id' => 'integer',
            'ranked_match_id' => 'integer',
            'joined_at' => 'datetime',
            'matched_at' => 'datetime',
            'left_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RankedMatch::class, 'ranked_match_id');
    }
}
