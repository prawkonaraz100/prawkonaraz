<?php

namespace Database\Factories;

use App\Enums\ContentArticleSourceType;
use App\Models\ContentArticle;
use App\Models\ContentArticleSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentArticleSource>
 */
class ContentArticleSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => ContentArticle::factory(),
            'source_type' => ContentArticleSourceType::Official->value,
            'publisher' => fake()->company(),
            'title' => fake()->sentence(),
            'url' => fake()->url(),
            'published_at' => now()->subDays(2),
            'accessed_at' => now(),
            'is_primary' => false,
            'is_official' => true,
            'is_publicly_cited' => true,
            'note' => null,
            'sort_order' => 0,
        ];
    }

    public function privateEvidence(): static
    {
        return $this->state(fn (): array => [
            'is_publicly_cited' => false,
            'note' => fake()->sentence(),
        ]);
    }
}
