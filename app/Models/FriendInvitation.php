<?php

namespace App\Models;

use Database\Factories\FriendInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendInvitation extends Model
{
    /** @use HasFactory<FriendInvitationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CONVERTED = 'converted';

    public const REVOKED_REASON_OWNER = 'owner_revoked';

    public const REVOKED_REASON_SLOT_TAKEN = 'slot_taken';

    public const REVOKED_REASON_REFUND = 'owner_refund';

    public const REVOKED_REASON_OWNER_ACCESS_INACTIVE = 'owner_access_inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'owner_user_id',
        'owner_product_access_grant_id',
        'owner_purchase_order_id',
        'product_plan_id',
        'status',
        'token_hash',
        'code_hash',
        'display_code_last4',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
        'guest_product_access_grant_id',
        'guest_access_starts_at',
        'guest_access_expires_at',
        'refund_processed_at',
        'refund_buffer_expires_at',
        'converted_at',
        'converted_purchase_order_id',
        'revoked_at',
        'revoked_reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'guest_access_starts_at' => 'datetime',
            'guest_access_expires_at' => 'datetime',
            'refund_processed_at' => 'datetime',
            'refund_buffer_expires_at' => 'datetime',
            'converted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerProductAccessGrant(): BelongsTo
    {
        return $this->belongsTo(ProductAccessGrant::class, 'owner_product_access_grant_id');
    }

    public function ownerPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'owner_purchase_order_id');
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function guestProductAccessGrant(): BelongsTo
    {
        return $this->belongsTo(ProductAccessGrant::class, 'guest_product_access_grant_id');
    }

    public function convertedPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_purchase_order_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }
}
