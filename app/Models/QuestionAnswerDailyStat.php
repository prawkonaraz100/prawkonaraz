<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionAnswerDailyStat extends Model
{
    protected $fillable = [
        'stats_date',
        'question_id',
        'license_category_id',
        'question_topic_id',
        'selected_answer',
        'answers_count',
        'users_count',
    ];

    protected function casts(): array
    {
        return [
            'stats_date' => 'date',
            'answers_count' => 'integer',
            'users_count' => 'integer',
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
