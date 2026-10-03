<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreakRunAnswer extends Model
{
    protected $fillable = [
        'streak_run_id',
        'question_id',
        'sequence',
        'selected_answer',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(StreakRun::class, 'streak_run_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
