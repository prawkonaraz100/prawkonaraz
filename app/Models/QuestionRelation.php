<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionRelation extends Model
{
    public const SOURCE_EDITORIAL = 'editorial';

    public const SOURCE_GRAPH = 'graph';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_AUTOMATIC = 'automatic';

    public const STATUS_CANDIDATE = 'candidate';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'left_explanation_id',
        'right_explanation_id',
        'relation_type',
        'direction',
        'source',
        'status',
        'score',
        'reason',
        'difference',
        'anchor_left_to_right',
        'anchor_right_to_left',
        'review_priority',
        'display_order',
        'metadata',
        'version',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function leftExplanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class, 'left_explanation_id');
    }

    public function rightExplanation(): BelongsTo
    {
        return $this->belongsTo(QuestionPublicExplanation::class, 'right_explanation_id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(QuestionRelationEvidence::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_VERIFIED, self::STATUS_AUTOMATIC]);
    }
}
