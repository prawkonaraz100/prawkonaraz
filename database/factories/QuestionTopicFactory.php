<?php

namespace Database\Factories;

use App\Models\QuestionTopic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QuestionTopic>
 */
class QuestionTopicFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'key' => Str::slug($name, '_'),
            'name' => Str::headline($name),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
