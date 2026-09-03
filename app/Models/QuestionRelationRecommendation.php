<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionRelationRecommendation extends Model
{
    public const STATUS_SELECTED = 'selected';

    public const STATUS_SUPPRESSED = 'suppressed';

    protected $fillable = [
        'question_relation_ranking_run_id',
        'source_explanation_id',
        'target_explanation_id',
        'question_relation_id',
        'scope',
        'group_key',
        'rank',
        'score',
        'score_components',
        'status',
        'suppression_reason',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'score' => 'float',
            'score_components' => 'array',
        ];
    }

    public function rankingRun(): BelongsTo
    {
        return $this->belongsTo(QuestionRelationRankingRun::class, 'question_relation_ranking_run_id');
    }

    public function sourceExplanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class, 'source_explanation_id');
    }

    public function targetExplanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class, 'target_explanation_id');
    }

    public function relation(): BelongsTo
    {
        return $this->belongsTo(QuestionRelation::class, 'question_relation_id');
    }

    public function scopeSelected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SELECTED);
    }
}
