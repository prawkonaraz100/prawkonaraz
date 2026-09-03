<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionDailyStat extends Model
{
    protected $fillable = [
        'stats_date',
        'question_id',
        'license_category_id',
        'question_topic_id',
        'answers_count',
        'users_count',
        'correct_answers_count',
        'incorrect_answers_count',
        'response_time_count',
        'response_time_avg_ms',
        'response_time_median_ms',
        'progress_users_count',
        'mastered_progress_users_count',
        'avg_total_attempts',
    ];

    protected function casts(): array
    {
        return [
            'stats_date' => 'date',
            'answers_count' => 'integer',
            'users_count' => 'integer',
            'correct_answers_count' => 'integer',
            'incorrect_answers_count' => 'integer',
            'response_time_count' => 'integer',
            'response_time_avg_ms' => 'integer',
            'response_time_median_ms' => 'integer',
            'progress_users_count' => 'integer',
            'mastered_progress_users_count' => 'integer',
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
