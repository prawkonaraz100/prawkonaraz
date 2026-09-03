<?php

namespace Database\Factories;

use App\Models\TrafficSignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrafficSignCategory>
 */
class TrafficSignCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Znaki ostrzegawcze',
            'Znaki zakazu',
            'Znaki nakazu',
            'Znaki informacyjne',
        ]).' '.fake()->unique()->numerify('##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'intro_title' => 'Wprowadzenie do kategorii',
            'intro_body' => fake()->paragraphs(2, true),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
