<?php

namespace Database\Factories;

use App\Models\ProductPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPlan>
 */
class ProductPlanFactory extends Factory
{
    protected $model = ProductPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'price_gross_cents' => 3900,
            'currency' => 'PLN',
            'access_days' => 30,
            'sort_order' => 10,
            'is_active' => true,
        ];
    }
}
