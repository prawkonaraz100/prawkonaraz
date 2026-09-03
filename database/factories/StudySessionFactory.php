<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudySession>
 */
class StudySessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subMinutes(fake()->numberBetween(10, 60));
        $completedAt = (clone $startedAt)->addMinutes(fake()->numberBetween(5, 45));
        $totalQuestions = fake()->numberBetween(5, 20);
        $correctAnswers = fake()->numberBetween(0, $totalQuestions);

        return [
            'user_id' => User::factory(),
            'license_category_id' => LicenseCategory::factory(),
            'mode' => fake()->randomElement(['exam', 'learn', 'sr_review', 'quick']),
            'status' => 'completed',
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'correct_answers_count' => $correctAnswers,
            'total_questions_count' => $totalQuestions,
            'score_percent' => round(($correctAnswers / max($totalQuestions, 1)) * 100, 2),
            'payload' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
            'score_percent' => null,
        ]);
    }
}
