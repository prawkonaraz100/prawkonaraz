<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\QuestionLearningOrderSet;
use App\Models\QuestionTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionLearningOrderSet>
 */
class QuestionLearningOrderSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'license_category_id' => LicenseCategory::factory(),
            'question_topic_id' => QuestionTopic::factory(),
            'question_scope' => 'all',
            'status' => QuestionLearningOrderSet::STATUS_DRAFT,
            'version' => 1,
            'notes' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
            'published_at' => null,
            'active_marker' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => QuestionLearningOrderSet::STATUS_ACTIVE,
            'published_at' => now(),
            'active_marker' => QuestionLearningOrderSet::ACTIVE_MARKER,
        ]);
    }
}
