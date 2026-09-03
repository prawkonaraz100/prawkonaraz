<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIncorrectQuestion extends Model
{
    public const REMOVAL_REASON_MANUAL = 'manual';

    public const REMOVAL_REASON_CORRECT_ANSWER = 'correct_answer';

    public const SOURCE_ANSWER = 'answer';

    public const SOURCE_LEGACY_BACKFILL = 'legacy_backfill';

    protected $fillable = [
        'user_id',
        'question_id',
        'first_incorrect_at',
        'last_incorrect_at',
        'removed_at',
        'removal_reason',
        'latest_study_session_id',
        'latest_answer_id',
        'created_source',
        'backfill_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'first_incorrect_at' => 'datetime',
            'last_incorrect_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('removed_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function latestStudySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'latest_study_session_id');
    }

    public function latestAnswer(): BelongsTo
    {
        return $this->belongsTo(StudySessionAnswer::class, 'latest_answer_id');
    }
}
