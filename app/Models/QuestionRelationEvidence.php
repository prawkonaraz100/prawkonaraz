<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionRelationEvidence extends Model
{
    public const STATUS_CANDIDATE = 'candidate';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'question_relation_evidences';

    protected $fillable = [
        'question_relation_id',
        'evidence_type',
        'summary',
        'source',
        'confidence',
        'status',
        'metadata',
        'version',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function relation(): BelongsTo
    {
        return $this->belongsTo(QuestionRelation::class, 'question_relation_id');
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
