<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_category_id' => LicenseCategory::factory(),
            'external_id' => fake()->unique()->bothify('Q-####'),
            'prompt' => fake()->sentence(12).'?',
            'explanation' => fake()->paragraph(),
            'option_a' => fake()->sentence(4),
            'option_b' => fake()->sentence(4),
            'option_c' => fake()->sentence(4),
            'correct_answer' => fake()->randomElement(['a', 'b', 'c']),
            'difficulty' => fake()->numberBetween(1, 5),
            'points' => fake()->randomElement([1, 2, 3]),
            'question_type' => 'single_choice',
            'is_active' => true,
            'requires_primary_media' => false,
            'delivery_issue' => null,
            'source' => 'internal',
            'published_at' => now(),
        ];
    }

    public function booleanType(): static
    {
        return $this->state(fn (array $attributes) => [
            'option_c' => null,
            'question_type' => 'boolean',
            'correct_answer' => fake()->randomElement(['a', 'b']),
        ]);
    }
}
