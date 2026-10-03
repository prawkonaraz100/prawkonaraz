<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreakRecord extends Model
{
    protected $fillable = [
        'user_id',
        'license_category_id',
        'best_run_id',
        'best_score',
        'attempts_count',
        'best_achieved_at',
    ];

    protected function casts(): array
    {
        return [
            'best_score' => 'integer',
            'attempts_count' => 'integer',
            'best_achieved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
