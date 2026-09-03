<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionRelationRankingRun extends Model
{
    public const STATUS_GENERATED = 'generated';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'question_seo_topic_id',
        'input_version',
        'generator_version',
        'config_hash',
        'status',
        'metrics',
        'generated_at',
        'validated_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'generated_at' => 'datetime',
            'validated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuestionSeoTopic::class, 'question_seo_topic_id');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(QuestionRelationRecommendation::class, 'question_relation_ranking_run_id');
    }

    public function activeRollouts(): HasMany
    {
        return $this->hasMany(QuestionRelationRollout::class, 'active_ranking_run_id');
    }
}
