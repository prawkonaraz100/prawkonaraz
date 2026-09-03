<?php

namespace Database\Factories;

use App\Models\QuestionSignLanguageAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionSignLanguageAsset>
 */
class QuestionSignLanguageAssetFactory extends Factory
{
    protected $model = QuestionSignLanguageAsset::class;

    public function definition(): array
    {
        $externalId = (string) $this->faker->unique()->numberBetween(1000, 999999);

        return [
            'external_id' => $externalId,
            'asset_role' => QuestionSignLanguageAsset::ROLE_QUESTION,
            'disk' => 'public',
            'path' => "pjm/standard/{$externalId}.mp4",
            'source_filename' => "{$externalId}.mp4",
            'mime_type' => 'video/mp4',
            'bytes' => $this->faker->numberBetween(250_000, 2_500_000),
            'duration_seconds' => $this->faker->randomFloat(3, 3, 30),
            'width' => 720,
            'height' => 1280,
            'variant' => QuestionSignLanguageAsset::VARIANT_STANDARD,
            'processing_profile' => 'safe-center-crop-80',
            'processing_status' => QuestionSignLanguageAsset::STATUS_READY,
            'is_active' => true,
            'review_required' => false,
            'metadata' => null,
        ];
    }

    public function answer(string $role = QuestionSignLanguageAsset::ROLE_ANSWER_A): static
    {
        return $this->state(fn (): array => [
            'asset_role' => $role,
        ]);
    }

    public function reviewRequired(): static
    {
        return $this->state(fn (): array => [
            'processing_status' => QuestionSignLanguageAsset::STATUS_REVIEW_REQUIRED,
            'review_required' => true,
        ]);
    }
}
