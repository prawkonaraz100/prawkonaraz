<?php

namespace App\Models;

use App\Support\UserReviewPhotoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReview extends Model
{
    public const SOCIAL_LINK_LABELS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'website' => 'Strona WWW',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'rating',
        'content',
        'photo_path',
        'photo_uploaded_at',
        'photo_privacy_confirmed_at',
        'social_links',
        'status',
        'published_at',
        'moderated_by_user_id',
        'moderated_at',
    ];

    protected static function booted(): void
    {
        static::deleted(function (UserReview $review): void {
            app(UserReviewPhotoService::class)->delete($review->photo_path);
        });
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'photo_uploaded_at' => 'datetime',
            'photo_privacy_confirmed_at' => 'datetime',
            'social_links' => 'array',
            'published_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_APPROVED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->published()
            ->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereNull('banned_at'));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Opublikowana',
            self::STATUS_REJECTED => 'Wymaga poprawy',
            default => 'Czeka na zatwierdzenie',
        };
    }
}
