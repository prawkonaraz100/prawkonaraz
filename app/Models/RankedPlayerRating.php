<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedPlayerRating extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'peak_rating',
        'matches_played',
        'wins',
        'losses',
        'draws',
        'current_streak',
        'best_streak',
    ];

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $keyType = 'int';

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'rating' => 'integer',
            'peak_rating' => 'integer',
            'matches_played' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'current_streak' => 'integer',
            'best_streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
