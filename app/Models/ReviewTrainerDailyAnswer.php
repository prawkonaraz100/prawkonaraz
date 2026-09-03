<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTrainerDailyAnswer extends Model
{
    protected $fillable = [
        'user_id',
        'license_category_id',
        'question_id',
        'study_session_id',
        'study_session_answer_id',
        'review_day',
        'daily_plan_policy_version',
        'answer_kind',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'license_category_id' => 'integer',
            'question_id' => 'integer',
            'study_session_id' => 'integer',
            'study_session_answer_id' => 'integer',
            'review_day' => 'date',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function studySessionAnswer(): BelongsTo
    {
        return $this->belongsTo(StudySessionAnswer::class);
    }
}
