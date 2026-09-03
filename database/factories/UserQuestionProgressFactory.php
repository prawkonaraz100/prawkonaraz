<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use App\Models\UserQuestionProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserQuestionProgress>
 */
class UserQuestionProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'easiness_factor' => 2.50,
            'interval_days' => 1,
            'repetitions' => 0,
            'next_review_at' => today(),
            'last_quality' => null,
            'total_attempts' => 0,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'correct_streak' => 0,
            'last_answered_at' => null,
            'first_answered_at' => null,
        ];
    }

    public function dueToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_at' => today(),
        ]);
    }

    public function scheduledInFuture(int $days = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'next_review_at' => today()->addDays($days),
        ]);
    }
}
