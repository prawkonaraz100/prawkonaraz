<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuestionSeoTopic extends Model
{
    public const KIND_MACRO = 'macro';

    public const KIND_TOPIC = 'topic';

    public const KIND_SUBTOPIC = 'subtopic';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const QUALITY_MISSING = 'missing';

    public const QUALITY_DRAFT = 'draft';

    public const QUALITY_LEGACY = 'legacy';

    public const QUALITY_APPROVED = 'approved';

    protected $fillable = [
        'parent_id',
        'key',
        'slug',
        'label',
        'description',
        'question_count',
        'is_indexable',
        'sort_order',
        'kind',
        'status',
        'content_quality_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'question_count' => 'integer',
            'is_indexable' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function explanations(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionPublicExplanation::class,
            'question_seo_topic_memberships',
        )->withPivot([
            'source',
            'is_primary',
            'role',
            'status',
            'confidence',
            'reason',
            'metadata',
            'reviewed_by_user_id',
            'reviewed_at',
        ])->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(QuestionSeoTopicMembership::class);
    }

    public function adjacentTopics(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'question_seo_topic_relations',
            'source_topic_id',
            'target_topic_id',
        )->withPivot([
            'relation_type',
            'display_order',
            'status',
            'source',
            'reason',
            'score',
            'reviewed_by_user_id',
            'reviewed_at',
            'metadata',
        ])->withTimestamps();
    }

    public function outboundRelations(): HasMany
    {
        return $this->hasMany(QuestionSeoTopicRelation::class, 'source_topic_id');
    }

    public function inboundRelations(): HasMany
    {
        return $this->hasMany(QuestionSeoTopicRelation::class, 'target_topic_id');
    }

    public function rankingRuns(): HasMany
    {
        return $this->hasMany(QuestionRelationRankingRun::class);
    }

    public function rollout(): HasOne
    {
        return $this->hasOne(QuestionRelationRollout::class);
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->where('is_indexable', true);
    }
}
