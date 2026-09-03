<?php

namespace App\Models;

use Database\Factories\QuestionLearningOrderSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionLearningOrderSet extends Model
{
    /** @use HasFactory<QuestionLearningOrderSetFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const ACTIVE_MARKER = 'active';

    protected $fillable = [
        'license_category_id',
        'question_topic_id',
        'question_scope',
        'status',
        'version',
        'notes',
        'created_by',
        'updated_by',
        'published_at',
        'active_marker',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function licenseCategory(): BelongsTo
    {
        return $this->belongsTo(LicenseCategory::class);
    }

    public function questionTopic(): BelongsTo
    {
        return $this->belongsTo(QuestionTopic::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuestionLearningOrderItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->active_marker === self::ACTIVE_MARKER;
    }
}
