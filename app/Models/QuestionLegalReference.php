<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionLegalReference extends Model
{
    public const SOURCE_SEED = 'seed';

    public const SOURCE_MANUAL = 'manual';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'question_id',
        'legal_unit_id',
        'legal_topic_id',
        'legal_content_page_id',
        'relation_type',
        'public_note',
        'internal_note',
        'assignment_source',
        'confidence',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function legalUnit(): BelongsTo
    {
        return $this->belongsTo(LegalUnit::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LegalTopic::class, 'legal_topic_id');
    }

    public function contentPage(): BelongsTo
    {
        return $this->belongsTo(LegalContentPage::class, 'legal_content_page_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(ContentAuthor::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_VERIFIED)
            ->whereNotNull('verified_at');
    }
}
