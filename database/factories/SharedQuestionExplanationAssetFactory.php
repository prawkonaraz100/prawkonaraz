<?php

namespace Database\Factories;

use App\Models\SharedQuestionExplanationAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SharedQuestionExplanationAsset>
 */
class SharedQuestionExplanationAssetFactory extends Factory
{
    protected $model = SharedQuestionExplanationAsset::class;

    public function definition(): array
    {
        return [
            'external_id' => 'B-001',
            'source_scope' => 'gov.pl-mi',
            'kind' => SharedQuestionExplanationAsset::KIND_REFERENCE_SIGN,
            'disk' => 'public',
            'file_path' => 'question-explanations/shared/reference-sign.webp',
            'title' => 'Znak A-7',
            'body' => fake()->paragraph(),
            'caption' => fake()->sentence(3),
            'alt_text' => 'Wspolny material referencyjny',
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
