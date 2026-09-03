<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankedMatchAnswer extends Model
{
    protected $fillable = [
        'ranked_match_id',
        'user_id',
        'question_id',
        'question_number',
        'selected_answer',
        'is_correct',
        'response_time_ms',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'ranked_match_id' => 'integer',
            'user_id' => 'integer',
            'question_id' => 'integer',
            'question_number' => 'integer',
            'is_correct' => 'boolean',
            'response_time_ms' => 'integer',
            'answered_at' => 'datetime',
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

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
