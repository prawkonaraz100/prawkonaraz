<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTopicCompletionRecord extends Model
{
    protected $fillable = [
        'user_id',
        'license_category_id',
        'question_topic_id',
        'question_scope',
        'last_study_session_id',
        'last_duration_seconds',
        'last_score_percent',
        'last_completed_at',
        'last_ui_shell',
        'last_questions_count',
        'last_question_ids_hash',
        'best_study_session_id',
        'best_duration_seconds',
        'best_completed_at',
        'best_ui_shell',
        'best_questions_count',
        'best_question_ids_hash',
        'completion_count',
        'perfect_completion_count',
        'first_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_duration_seconds' => 'integer',
            'last_score_percent' => 'decimal:2',
            'last_completed_at' => 'datetime',
            'last_questions_count' => 'integer',
            'best_duration_seconds' => 'integer',
            'best_completed_at' => 'datetime',
            'best_questions_count' => 'integer',
            'completion_count' => 'integer',
            'perfect_completion_count' => 'integer',
            'first_completed_at' => 'datetime',
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

    public function questionTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class);
    }

    public function lastStudySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'last_study_session_id');
    }

    public function bestStudySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'best_study_session_id');
    }
}
