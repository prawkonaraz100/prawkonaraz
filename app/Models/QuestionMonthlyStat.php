<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionMonthlyStat extends Model
{
    protected $fillable = [
        'stats_month',
        'question_id',
        'license_category_id',
        'question_topic_id',
        'days_covered_count',
        'answers_count',
        'user_days_count',
        'correct_answers_count',
        'incorrect_answers_count',
        'response_time_count',
        'response_time_avg_ms',
        'progress_user_days_count',
        'mastered_progress_user_days_count',
        'avg_total_attempts',
    ];

    protected function casts(): array
    {
        return [
            'stats_month' => 'date',
            'days_covered_count' => 'integer',
            'answers_count' => 'integer',
            'user_days_count' => 'integer',
            'correct_answers_count' => 'integer',
            'incorrect_answers_count' => 'integer',
            'response_time_count' => 'integer',
            'response_time_avg_ms' => 'integer',
            'progress_user_days_count' => 'integer',
            'mastered_progress_user_days_count' => 'integer',
            'avg_total_attempts' => 'decimal:2',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function questionTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class);
    }
}
