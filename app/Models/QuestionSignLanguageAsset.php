<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionSignLanguageAsset extends Model
{
    use HasFactory;

    public const ROLE_QUESTION = 'question';

    public const ROLE_ANSWER_A = 'answer_a';

    public const ROLE_ANSWER_B = 'answer_b';

    public const ROLE_ANSWER_C = 'answer_c';

    public const STATUS_READY = 'ready';

    public const STATUS_REVIEW_REQUIRED = 'review_required';

    public const STATUS_DISABLED = 'disabled';

    public const VARIANT_STANDARD = 'standard';

    protected $fillable = [
        'external_id',
        'asset_role',
        'disk',
        'path',
        'source_filename',
        'source_path',
        'mime_type',
        'bytes',
        'duration_seconds',
        'width',
        'height',
        'variant',
        'processing_profile',
        'processing_status',
        'is_active',
        'review_required',
        'checksum_sha256',
        'metadata',
    ];

    protected $casts = [
        'bytes' => 'integer',
        'duration_seconds' => 'float',
        'width' => 'integer',
        'height' => 'integer',
        'is_active' => 'boolean',
        'review_required' => 'boolean',
        'metadata' => 'array',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'external_id', 'external_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->whereIn('processing_status', [
            self::STATUS_READY,
            self::STATUS_REVIEW_REQUIRED,
        ]);
    }

    public function scopeQuestionRole(Builder $query): Builder
    {
        return $query->where('asset_role', self::ROLE_QUESTION);
    }

    public function isQuestionAsset(): bool
    {
        return $this->asset_role === self::ROLE_QUESTION;
    }
}
