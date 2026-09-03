<?php

namespace Database\Factories;

use App\Models\ProductPlan;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'user_id' => User::factory(),
            'product_plan_id' => ProductPlan::factory(),
            'provider' => 'sandbox',
            'status' => PurchaseOrder::STATUS_PENDING,
            'amount_gross_cents' => 4900,
            'currency' => 'PLN',
            'access_days' => 30,
            'metadata' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PurchaseOrder::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
