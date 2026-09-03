<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionSeoTopicRelation extends Model
{
    public const STATUS_CANDIDATE = 'candidate';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'source_topic_id',
        'target_topic_id',
        'relation_type',
        'display_order',
        'status',
        'source',
        'reason',
        'score',
        'reviewed_by_user_id',
        'reviewed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'score' => 'float',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function sourceTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionSeoTopic::class, 'source_topic_id');
    }

    public function targetTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionSeoTopic::class, 'target_topic_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
