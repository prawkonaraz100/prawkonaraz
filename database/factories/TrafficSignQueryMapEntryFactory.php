<?php

namespace Database\Factories;

use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSignQueryMapEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrafficSignQueryMapEntry>
 */
class TrafficSignQueryMapEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'traffic_sign_id' => TrafficSign::factory(),
            'traffic_sign_category_id' => TrafficSignCategory::factory(),
            'primary_query' => fake()->unique()->sentence(3),
            'mapped_title' => fake()->sentence(4),
            'target_type' => fake()->randomElement(array_keys(TrafficSignQueryMapEntry::targetTypeOptions())),
            'search_intent' => fake()->randomElement(array_keys(TrafficSignQueryMapEntry::searchIntentOptions())),
            'priority' => fake()->randomElement(array_keys(TrafficSignQueryMapEntry::priorityOptions())),
            'rollout_status' => fake()->randomElement(array_keys(TrafficSignQueryMapEntry::rolloutStatusOptions())),
            'batch_label' => 'batch-'.fake()->numberBetween(1, 3),
            'target_path' => '/znaki-drogowe/'.fake()->slug(),
            'watch_reason' => fake()->boolean(60) ? fake()->sentence() : null,
            'source_plan' => fake()->sentence(),
            'correction_notes' => fake()->boolean(50) ? fake()->sentence() : null,
            'competitor_notes' => fake()->boolean(40) ? fake()->sentence() : null,
            'first_mover_note' => fake()->boolean(30) ? fake()->sentence() : null,
            'notes' => fake()->boolean(60) ? fake()->paragraph() : null,
        ];
    }
}
