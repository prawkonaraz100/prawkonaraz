<?php

namespace Database\Factories;

use App\Models\ContentArticle;
use App\Models\ContentHomePlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentHomePlacement>
 */
class ContentHomePlacementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'surface_key' => ContentHomePlacement::SURFACE_NEWSROOM_HOME,
            'slot_key' => fake()->randomElement(ContentHomePlacement::allowedSlotKeys()),
            'context_key' => null,
            'position' => 0,
            'article_id' => ContentArticle::factory(),
            'starts_at' => null,
            'ends_at' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
