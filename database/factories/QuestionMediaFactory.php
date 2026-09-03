<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionMedia>
 */
class QuestionMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'kind' => fake()->randomElement(['image', 'video']),
            'disk' => (string) config('media.default_disk', 'public'),
            'path' => 'questions/'.fake()->uuid().'.webp',
            'poster_path' => fake()->boolean(40) ? 'questions/posters/'.fake()->uuid().'.webp' : null,
            'mime_type' => 'image/webp',
            'bytes' => fake()->numberBetween(50_000, 2_000_000),
            'duration_seconds' => null,
            'width' => 1280,
            'height' => 720,
            'variant' => 'full',
            'sort_order' => 0,
            'metadata' => null,
        ];
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => 'video',
            'path' => 'questions/'.fake()->uuid().'.mp4',
            'poster_path' => 'questions/posters/'.fake()->uuid().'.webp',
            'mime_type' => 'video/mp4',
            'bytes' => fake()->numberBetween(500_000, 8_000_000),
            'duration_seconds' => fake()->numberBetween(5, 45),
            'variant' => 'full',
        ]);
    }
}
