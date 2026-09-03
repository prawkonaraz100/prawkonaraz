<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTrafficSignProgress extends Model
{
    public const STATE_NEW = 'new';

    public const STATE_LEARNING = 'learning';

    public const STATE_NEEDS_REVIEW = 'needs_review';

    public const STATE_MASTERED = 'mastered';

    protected $table = 'user_traffic_sign_progress';

    protected $fillable = [
        'user_id',
        'traffic_sign_id',
        'state',
        'attempts_count',
        'correct_count',
        'incorrect_count',
        'correct_streak',
        'last_confused_with_traffic_sign_id',
        'last_answered_at',
        'next_review_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts_count' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'correct_streak' => 'integer',
            'last_answered_at' => 'datetime',
            'next_review_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
    }

    public function lastConfusedWithTrafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class, 'last_confused_with_traffic_sign_id');
    }
}
