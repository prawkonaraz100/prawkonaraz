<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedMatchEvent extends Model
{
    protected $fillable = [
        'ranked_match_id',
        'actor_user_id',
        'event_id',
        'event_name',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'ranked_match_id' => 'integer',
            'actor_user_id' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(RankedMatch::class, 'ranked_match_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
