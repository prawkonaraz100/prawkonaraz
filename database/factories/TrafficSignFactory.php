<?php

namespace Database\Factories;

use App\Models\ContentAuthor;
use App\Models\TrafficSign;
use App\Models\TrafficSignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrafficSign>
 */
class TrafficSignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $codePrefix = fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'T']);
        $code = $codePrefix.'-'.fake()->unique()->numberBetween(1, 99);
        $name = fake()->randomElement([
            'ustąp pierwszeństwa',
            'zakaz wjazdu',
            'droga z pierwszeństwem',
            'przejście dla pieszych',
            'nakaz jazdy prosto',
        ]);

        return [
            'content_author_id' => ContentAuthor::factory(),
            'traffic_sign_category_id' => TrafficSignCategory::factory(),
            'code' => $code,
            'slug' => Str::slug($code.' '.$name),
            'name' => Str::title($name),
            'intro_definition' => fake()->sentence(),
            'meaning' => fake()->paragraph(),
            'placement' => fake()->paragraph(),
            'driver_behavior' => fake()->paragraph(),
            'legal_summary' => fake()->paragraph(),
            'legal_reference_label' => 'Prawo o ruchu drogowym',
            'legal_reference_url' => fake()->url(),
            'fine_summary' => fake()->paragraph(),
            'common_mistakes' => fake()->paragraph(),
            'editorial_notes' => fake()->boolean(60) ? fake()->sentence() : null,
            'review_notes' => fake()->boolean(40) ? fake()->sentence() : null,
            'source_notes' => fake()->boolean(60) ? fake()->sentence() : null,
            'faq_items' => [
                [
                    'question' => 'Co oznacza ten znak?',
                    'answer' => fake()->sentence(),
                ],
                [
                    'question' => 'Jak powinien zachować się kierowca?',
                    'answer' => fake()->sentence(),
                ],
            ],
            'meta_title' => $code.' - '.Str::title($name).' | Znaczenie i przepisy',
            'meta_description' => fake()->text(140),
            'image_path' => 'traffic-signs/'.Str::slug($code).'.svg',
            'image_alt' => 'Znak '.$code.' '.Str::title($name),
            'image_width' => 320,
            'image_height' => 320,
            'og_image_path' => 'traffic-signs/og/'.Str::slug($code).'.png',
            'og_image_alt' => 'Grafika OG dla znaku '.$code.' '.Str::title($name),
            'og_image_width' => 320,
            'og_image_height' => 320,
            'sort_order' => fake()->numberBetween(0, 50),
            'workflow_status' => TrafficSign::WORKFLOW_DRAFT,
            'reviewer_user_id' => null,
            'reviewed_at' => null,
            'source_checked_at' => null,
            'freshness_review_due_at' => null,
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'workflow_status' => TrafficSign::WORKFLOW_PUBLISHED,
            'reviewed_at' => now(),
            'source_checked_at' => now(),
            'freshness_review_due_at' => now()->addMonths(6),
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
