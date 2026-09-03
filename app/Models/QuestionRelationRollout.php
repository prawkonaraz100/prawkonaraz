<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionRelationRollout extends Model
{
    public const MODE_V1 = 'v1';

    public const MODE_SHADOW = 'shadow';

    public const MODE_CANARY = 'canary';

    public const MODE_V2 = 'v2';

    protected $fillable = [
        'question_seo_topic_id',
        'mode',
        'active_ranking_run_id',
        'exposure_percentage',
        'cohort_seed',
        'started_at',
        'ended_at',
        'approved_by_user_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'exposure_percentage' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuestionSeoTopic::class, 'question_seo_topic_id');
    }

    public function activeRankingRun(): BelongsTo
    {
        return $this->belongsTo(QuestionRelationRankingRun::class, 'active_ranking_run_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
