<?php

namespace App\Models;

use Database\Factories\ProductPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPlan extends Model
{
    /** @use HasFactory<ProductPlanFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'price_gross_cents',
        'currency',
        'access_days',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_gross_cents' => 'integer',
            'access_days' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function friendInvitations(): HasMany
    {
        return $this->hasMany(FriendInvitation::class);
    }

    public function formattedPrice(): string
    {
        return number_format($this->price_gross_cents / 100, 2, ',', ' ').' '.$this->currency;
    }
}
