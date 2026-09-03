<?php

namespace App\Models;

use Database\Factories\UserQuestionProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserQuestionProgress extends Model
{
    /** @use HasFactory<UserQuestionProgressFactory> */
    use HasFactory;

    protected $table = 'user_question_progress';

    protected $fillable = [
        'user_id',
        'question_id',
        'easiness_factor',
        'interval_days',
        'repetitions',
        'next_review_at',
        'last_quality',
        'total_attempts',
        'correct_count',
        'incorrect_count',
        'correct_streak',
        'last_answered_at',
        'first_answered_at',
    ];

    protected function casts(): array
    {
        return [
            'easiness_factor' => 'decimal:2',
            'interval_days' => 'integer',
            'repetitions' => 'integer',
            'next_review_at' => 'date',
            'last_quality' => 'integer',
            'total_attempts' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'correct_streak' => 'integer',
            'last_answered_at' => 'datetime',
            'first_answered_at' => 'datetime',
        ];
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
