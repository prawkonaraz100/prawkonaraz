<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryHero;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionTopicCategoryHero>
 */
class QuestionTopicCategoryHeroFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_category_id' => LicenseCategory::factory(),
            'question_topic_id' => QuestionTopic::factory(),
            'hero_image_path' => 'study/topic-heroes/'.fake()->unique()->slug().'.webp',
            'hero_image_alt' => fake()->sentence(5),
            'hero_image_position' => 'center',
            'admin_note' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}

