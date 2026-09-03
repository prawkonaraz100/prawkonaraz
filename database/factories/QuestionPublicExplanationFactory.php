<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionPublicExplanation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionPublicExplanation>
 */
class QuestionPublicExplanationFactory extends Factory
{
    protected $model = QuestionPublicExplanation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => null,
            'external_id' => fake()->unique()->bothify('####'),
            'title' => 'Omówienie sytuacji',
            'body' => fake()->paragraphs(2, true),
            'dont_confuse_with' => null,
            'exam_trap' => null,
            'common_mistakes' => null,
            'related_questions' => null,
            'status' => QuestionPublicExplanation::STATUS_DRAFT,
            'published_at' => null,
            'last_reviewed_at' => null,
            'source_note' => null,
            'internal_note' => null,
        ];
    }

    public function forQuestionExternalId(Question $question): static
    {
        return $this->state(fn (): array => [
            'external_id' => (string) $question->external_id,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => QuestionPublicExplanation::STATUS_PUBLISHED,
            'published_at' => now(),
            'last_reviewed_at' => today(),
        ]);
    }
}
