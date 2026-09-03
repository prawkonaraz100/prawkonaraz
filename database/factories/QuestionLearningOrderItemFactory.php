<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionLearningOrderItem;
use App\Models\QuestionLearningOrderSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionLearningOrderItem>
 */
class QuestionLearningOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_learning_order_set_id' => QuestionLearningOrderSet::factory(),
            'question_id' => Question::factory(),
            'position' => fake()->unique()->numberBetween(10, 10000),
        ];
    }
}
