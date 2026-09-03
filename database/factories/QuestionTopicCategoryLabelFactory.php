<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\QuestionTopic;
use App\Models\QuestionTopicCategoryLabel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionTopicCategoryLabel>
 */
class QuestionTopicCategoryLabelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_category_id' => LicenseCategory::factory(),
            'question_topic_id' => QuestionTopic::factory(),
            'display_name' => fake()->unique()->words(4, true),
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

