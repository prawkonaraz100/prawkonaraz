<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\StudySession;
use App\Models\StudySessionAnswer;
use App\Support\StudySessionAnswerKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudySessionAnswer>
 */
class StudySessionAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_session_id' => StudySession::factory(),
            'question_id' => Question::factory(),
            'selected_answer' => fake()->randomElement(['a', 'b', 'c']),
            'answer_kind' => StudySessionAnswerKind::CHOICE,
            'is_correct' => fake()->boolean(),
            'response_time_ms' => fake()->numberBetween(2000, 40000),
            'answered_at' => now(),
        ];
    }

    public function unknown(): static
    {
        return $this->state(fn (): array => [
            'selected_answer' => null,
            'answer_kind' => StudySessionAnswerKind::UNKNOWN,
            'is_correct' => false,
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn (): array => [
            'selected_answer' => null,
            'answer_kind' => StudySessionAnswerKind::TIMEOUT,
            'is_correct' => false,
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (): array => [
            'selected_answer' => null,
            'answer_kind' => StudySessionAnswerKind::SKIPPED,
            'is_correct' => false,
            'response_time_ms' => null,
        ]);
    }
}
