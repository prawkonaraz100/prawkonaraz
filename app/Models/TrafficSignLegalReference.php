<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficSignLegalReference extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'traffic_sign_id',
        'legal_unit_id',
        'legal_topic_id',
        'legal_content_page_id',
        'relation_type',
        'public_note',
        'internal_note',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function trafficSign(): BelongsTo
    {
        return $this->belongsTo(TrafficSign::class);
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
