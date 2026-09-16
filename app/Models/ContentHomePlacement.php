<?php

namespace App\Models;

use App\Models\Concerns\HasOptimisticLockVersion;
use Database\Factories\ContentHomePlacementFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentHomePlacement extends Model
{
    /** @use HasFactory<ContentHomePlacementFactory> */
    use HasFactory;
    use HasOptimisticLockVersion;

    public const SURFACE_NEWSROOM_HOME = 'newsroom_home';

    public const SLOT_LEAD = 'lead';

    public const SLOT_SECONDARY = 'secondary';

    public const SLOT_CATEGORY_LEAD = 'category_lead';

    public const SLOT_GUIDES_LEAD = 'guides_lead';

    public const SLOT_IMPORTANT_NOW = 'important_now';

    /**
     * @return list<string>
     */
    public static function allowedSlotKeys(): array
    {
        return [
            self::SLOT_LEAD,
            self::SLOT_SECONDARY,
            self::SLOT_CATEGORY_LEAD,
            self::SLOT_GUIDES_LEAD,
            self::SLOT_IMPORTANT_NOW,
        ];
    }

    protected $fillable = [
        'surface_key',
        'slot_key',
        'context_key',
        'position',
        'article_id',
        'starts_at',
        'ends_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ContentArticle::class, 'article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActiveAt(Builder $query, ?DateTimeInterface $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where(fn (Builder $builder): Builder => $builder
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $builder): Builder => $builder
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>', $at));
    }
}
