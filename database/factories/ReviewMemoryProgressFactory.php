<?php

namespace Database\Factories;

use App\Models\LicenseCategory;
use App\Models\Question;
use App\Models\ReviewMemoryProgress;
use App\Models\User;
use App\Support\ReviewMemoryProgressService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewMemoryProgress>
 */
class ReviewMemoryProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'license_category_id' => LicenseCategory::factory(),
            'verified_attempts_count' => 0,
            'verified_correct_count' => 0,
            'verified_unknown_count' => 0,
            'verified_incorrect_count' => 0,
            'verified_correct_streak' => 0,
            'last_verified_result' => null,
            'last_verified_at' => null,
            'last_study_session_answer_id' => null,
            'next_verified_review_at' => null,
            'verified_memory_state' => ReviewMemoryProgress::STATE_NEW,
            'source_policy_version' => ReviewMemoryProgressService::VERSION,
        ];
    }
}
