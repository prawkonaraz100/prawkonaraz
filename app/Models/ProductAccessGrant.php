<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductAccessGrant extends Model
{
    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_MODERATOR_GRANT = 'moderator_grant';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_INVITATION_GUEST = 'invitation_guest';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'source',
        'status',
        'starts_at',
        'expires_at',
        'revoked_at',
        'granted_by_user_id',
        'purchase_order_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function ownedFriendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class, 'owner_product_access_grant_id');
    }

    public function guestFriendInvitation(): HasOne
    {
        return $this->hasOne(FriendInvitation::class, 'guest_product_access_grant_id');
    }

    public function isCurrentlyActive(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        return $this->status === self::STATUS_ACTIVE
            && $this->revoked_at === null
            && ($this->starts_at === null || $this->starts_at->lessThanOrEqualTo($now))
            && ($this->expires_at === null || $this->expires_at->greaterThan($now));
    }
}
