<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => fake()->firstName(),
            'target_category_id' => LicenseCategory::factory(),
            'preferred_learning_track' => UserProfile::LEARNING_TRACK_UNDECIDED,
            'exam_date' => today()->addWeeks(3),
            'study_streak' => fake()->numberBetween(0, 10),
            'last_study_date' => today(),
            'tier' => 'free',
            'onboarding_step' => null,
            'visual_explanations_enabled' => true,
            'visual_explanations_mode' => 'after_incorrect',
        ];
    }
}
