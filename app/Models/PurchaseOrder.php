<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_REFUNDED = 'refunded';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'user_id',
        'product_plan_id',
        'provider',
        'provider_reference',
        'status',
        'amount_gross_cents',
        'currency',
        'access_days',
        'paid_at',
        'failed_at',
        'canceled_at',
        'refunded_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_gross_cents' => 'integer',
            'access_days' => 'integer',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'canceled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productPlan(): BelongsTo
    {
        return $this->belongsTo(ProductPlan::class);
    }

    public function productAccessGrant(): HasOne
    {
        return $this->hasOne(ProductAccessGrant::class);
    }

    public function ownerFriendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class, 'owner_purchase_order_id');
    }

    public function convertedFriendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class, 'converted_purchase_order_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount_gross_cents / 100, 2, ',', ' ').' '.$this->currency;
    }
}
