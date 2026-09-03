<?php

namespace Database\Factories;

use App\Models\ContentAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentAuthor>
 */
class ContentAuthorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'job_title' => fake()->randomElement([
                'Instruktor nauki jazdy',
                'Egzaminator WORD',
                'Specjalista BRD',
                'Redaktor treści edukacyjnych',
            ]),
            'bio' => fake()->paragraphs(3, true),
            'photo_path' => fake()->boolean(40) ? 'authors/'.Str::slug($name).'.jpg' : null,
            'linkedin_url' => fake()->boolean(50) ? 'https://www.linkedin.com/in/'.Str::slug($name) : null,
            'external_profile_url' => fake()->boolean(30) ? fake()->url() : null,
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
