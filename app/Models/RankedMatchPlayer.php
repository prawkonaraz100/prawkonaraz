<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedMatchPlayer extends Model
{
    protected $fillable = [
        'ranked_match_id',
        'user_id',
        'slot',
        'status',
        'presence_state',
        'username_snapshot',
        'elo_before',
        'elo_after',
        'elo_change',
        'correct_answers',
        'total_answered',
        'points',
        'sum_response_time_ms',
        'last_seen_at',
        'ready_at',
        'disconnected_at',
        'reconnect_deadline_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'ranked_match_id' => 'integer',
            'user_id' => 'integer',
            'elo_before' => 'integer',
            'elo_after' => 'integer',
            'elo_change' => 'integer',
            'correct_answers' => 'integer',
            'total_answered' => 'integer',
            'points' => 'integer',
            'sum_response_time_ms' => 'integer',
            'last_seen_at' => 'datetime',
            'ready_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'reconnect_deadline_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RankedMatch::class, 'ranked_match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
