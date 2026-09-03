<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RankedMatch extends Model
{
    protected $fillable = [
        'public_id',
        'license_category_id',
        'status',
        'api_version',
        'duration_seconds',
        'total_questions',
        'matched_at',
        'countdown_started_at',
        'countdown_seconds',
        'started_at',
        'finished_at',
        'abandoned_at',
        'reason',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'license_category_id' => 'integer',
            'duration_seconds' => 'integer',
            'total_questions' => 'integer',
            'matched_at' => 'datetime',
            'countdown_started_at' => 'datetime',
            'countdown_seconds' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(RankedMatchPlayer::class)->orderBy('slot');
    }

    public function queueEntries(): HasMany
    {
        return $this->hasMany(RankedQueueEntry::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RankedMatchAnswer::class)->orderBy('question_number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RankedMatchEvent::class)->orderBy('occurred_at')->orderBy('id');
    }
}
