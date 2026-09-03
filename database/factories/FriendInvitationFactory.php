<?php

namespace Database\Factories;

use App\Models\FriendInvitation;
use App\Models\ProductAccessGrant;
use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FriendInvitation>
 */
class FriendInvitationFactory extends Factory
{
    protected $model = FriendInvitation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'owner_user_id' => User::factory(),
            'owner_product_access_grant_id' => ProductAccessGrant::query()->inRandomOrder()->value('id'),
            'owner_purchase_order_id' => PurchaseOrder::query()->inRandomOrder()->value('id'),
            'product_plan_id' => ProductPlan::query()->inRandomOrder()->value('id'),
            'status' => FriendInvitation::STATUS_PENDING,
            'token_hash' => hash('sha256', Str::random(64)),
            'code_hash' => hash('sha256', Str::random(16)),
            'display_code_last4' => strtoupper(Str::random(4)),
            'expires_at' => now()->addDays(14),
            'metadata' => [],
        ];
    }
}
