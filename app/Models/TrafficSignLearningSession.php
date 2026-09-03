<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class TrafficSignLearningSession extends Model
{
    public const MODE_RECOGNITION = 'recognition';

    public const MODE_SIMILAR_SIGNS = 'similar_signs';

    public const MODE_DESCRIPTION_TO_SIGN = 'description_to_sign';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'mode',
        'status',
        'total_signs_count',
        'correct_answers_count',
        'score_percent',
        'started_at',
        'completed_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'total_signs_count' => 'integer',
            'correct_answers_count' => 'integer',
            'score_percent' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TrafficSignLearningAnswer::class);
    }

    /**
     * @return Collection<int, int>
     */
    public function trafficSignIds(): Collection
    {
        return collect($this->payload['traffic_sign_ids'] ?? [])
            ->map(fn (mixed $trafficSignId): int => (int) $trafficSignId)
            ->filter()
            ->values();
    }
}
