<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionExplanationAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionExplanationAsset>
 */
class QuestionExplanationAssetFactory extends Factory
{
    protected $model = QuestionExplanationAsset::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'kind' => QuestionExplanationAsset::KIND_REFERENCE_SIGN,
            'disk' => 'public',
            'file_path' => 'question-explanations/reference-sign.webp',
            'title' => 'Znak A-7',
            'body' => fake()->paragraph(),
            'caption' => fake()->sentence(3),
            'alt_text' => 'Referencyjny znak drogowy',
            'position' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
