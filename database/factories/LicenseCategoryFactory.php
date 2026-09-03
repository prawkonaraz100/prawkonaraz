<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LicenseCategory>
 */
class LicenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->bothify('TC######');

        return [
            'code' => $code,
            'slug' => Str::slug($code),
            'name' => 'Kategoria '.$code,
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function withCode(string $code, ?string $name = null): static
    {
        $normalizedCode = Str::upper(trim($code));

        return $this->state(fn () => [
            'code' => $normalizedCode,
            'slug' => Str::slug($normalizedCode),
            'name' => $name ?? 'Kategoria '.$normalizedCode,
        ]);
    }

    public function categoryA(): static
    {
        return $this->withCode('A');
    }

    public function categoryB(): static
    {
        return $this->withCode('B');
    }

    public function categoryC(): static
    {
        return $this->withCode('C');
    }

    public function categoryD(): static
    {
        return $this->withCode('D');
    }

    public function categoryT(): static
    {
        return $this->withCode('T');
    }
}
