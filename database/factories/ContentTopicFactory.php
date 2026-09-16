<?php

namespace Database\Factories;

use App\Models\ContentTopic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentTopic>
 */
class ContentTopicFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraph(),
            'status' => ContentTopic::STATUS_DRAFT,
            'featured_article_id' => null,
            'seo_title' => null,
            'seo_description' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentTopic::STATUS_PUBLISHED,
            'published_at' => now()->subMinute(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => ContentTopic::STATUS_ARCHIVED,
            'published_at' => now()->subDay(),
        ]);
    }
}
