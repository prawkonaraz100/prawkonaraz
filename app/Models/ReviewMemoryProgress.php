<?php

namespace App\Models;

use Database\Factories\ReviewMemoryProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewMemoryProgress extends Model
{
    /** @use HasFactory<ReviewMemoryProgressFactory> */
    use HasFactory;

    public const STATE_NEW = 'new';

    public const STATE_REVIEW = 'review';

    public const STATE_VERIFIED_MEMORY = 'verified_memory';

    public const STATE_NEEDS_RECOVERY = 'needs_recovery';

    public const RESULT_CORRECT = 'correct';

    public const RESULT_INCORRECT = 'incorrect';

    public const RESULT_UNKNOWN = 'unknown';

    public const RESULT_SKIPPED = 'skipped';

    protected $fillable = [
        'user_id',
        'question_id',
        'license_category_id',
        'verified_attempts_count',
        'verified_correct_count',
        'verified_unknown_count',
        'verified_incorrect_count',
        'verified_correct_streak',
        'last_verified_result',
        'last_verified_at',
        'last_study_session_answer_id',
        'next_verified_review_at',
        'verified_memory_state',
        'source_policy_version',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'question_id' => 'integer',
            'license_category_id' => 'integer',
            'verified_attempts_count' => 'integer',
            'verified_correct_count' => 'integer',
            'verified_unknown_count' => 'integer',
            'verified_incorrect_count' => 'integer',
            'verified_correct_streak' => 'integer',
            'last_verified_at' => 'datetime',
            'last_study_session_answer_id' => 'integer',
            'next_verified_review_at' => 'date',
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

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function lastStudySessionAnswer(): BelongsTo
    {
        return $this->belongsTo(StudySessionAnswer::class, 'last_study_session_answer_id');
    }
}
