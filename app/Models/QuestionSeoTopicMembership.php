<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionSeoTopicMembership extends Model
{
    public const ROLE_PRIMARY = 'primary';

    public const ROLE_SUPPORTING = 'supporting';

    public const ROLE_CONTRAST = 'contrast';

    public const ROLE_PREREQUISITE = 'prerequisite';

    public const STATUS_CANDIDATE = 'candidate';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'question_public_explanation_id',
        'question_seo_topic_id',
        'source',
        'is_primary',
        'role',
        'status',
        'confidence',
        'reason',
        'metadata',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'confidence' => 'float',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function explanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class, 'question_public_explanation_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(QuestionSeoTopic::class, 'question_seo_topic_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
