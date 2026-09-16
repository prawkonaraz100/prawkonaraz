<?php

namespace Database\Factories;

use App\Enums\ContentArticleOriginType;
use App\Enums\ContentArticleRegulatoryStatus;
use App\Enums\ContentArticleType;
use App\Enums\ContentArticleWorkflowStatus;
use App\Models\ContentArticle;
use App\Models\ContentAuthor;
use App\Models\ContentCategory;
use App\Support\NewsroomBodyContract;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentArticle>
 */
class ContentArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'type' => ContentArticleType::News->value,
            'category_id' => ContentCategory::factory(),
            'author_id' => null,
            'reviewer_id' => null,
            'origin_type' => ContentArticleOriginType::Original->value,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'lead' => fake()->paragraph(),
            'body_blocks' => NewsroomBodyContract::normalize([
                [
                    'type' => NewsroomBodyContract::BLOCK_RICH_TEXT,
                    'data' => [
                        'content' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        [
                                            'type' => 'text',
                                            'text' => fake()->paragraph(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            'body_schema_version' => NewsroomBodyContract::CURRENT_SCHEMA_VERSION,
            'key_points' => null,
            'correction_note' => null,
            'editorial_note' => null,
            'regulatory_status' => ContentArticleRegulatoryStatus::NotApplicable->value,
            'effective_from' => null,
            'change_summary' => null,
            'applies_to' => null,
            'exam_impact' => null,
            'workflow_status' => ContentArticleWorkflowStatus::Draft->value,
            'published_at' => null,
            'first_published_at' => null,
            'scheduled_for' => null,
            'reviewed_at' => null,
            'needs_review_at' => null,
            'archived_at' => null,
            'withdrawn_at' => null,
            'withdrawal_reason' => null,
            'is_featured' => false,
            'is_breaking' => false,
            'breaking_expires_at' => null,
            'editorial_priority' => 0,
            'hero_image_path' => null,
            'hero_image_alt' => null,
            'hero_image_width' => null,
            'hero_image_height' => null,
            'hero_image_caption' => null,
            'hero_focal_x' => null,
            'hero_focal_y' => null,
            'og_image_path' => null,
            'og_image_alt' => null,
            'og_image_width' => null,
            'og_image_height' => null,
            'image_credit' => null,
            'image_license_note' => null,
            'seo_title' => null,
            'seo_description' => null,
            'robots' => null,
            'source_checked_at' => null,
            'freshness_review_due_at' => null,
            'last_substantive_update_at' => null,
            'public_state_changed_at' => null,
        ];
    }

    public function published(): static
    {
        $publishedAt = now()->subHour();

        return $this->state(fn (): array => [
            'author_id' => ContentAuthor::factory()->published(),
            'workflow_status' => ContentArticleWorkflowStatus::Published->value,
            'reviewed_at' => $publishedAt->copy()->subMinute(),
            'published_at' => $publishedAt,
            'first_published_at' => $publishedAt,
            'source_checked_at' => $publishedAt->copy()->subMinute(),
            'public_state_changed_at' => $publishedAt,
        ]);
    }

    public function needsReview(): static
    {
        $firstPublishedAt = now()->subDays(2);

        return $this->state(fn (): array => [
            'author_id' => ContentAuthor::factory()->published(),
            'workflow_status' => ContentArticleWorkflowStatus::NeedsReview->value,
            'published_at' => $firstPublishedAt,
            'first_published_at' => $firstPublishedAt,
            'needs_review_at' => now()->subMinute(),
            'public_state_changed_at' => now()->subMinute(),
        ]);
    }

    public function archived(): static
    {
        $firstPublishedAt = now()->subDays(7);

        return $this->state(fn (): array => [
            'author_id' => ContentAuthor::factory()->published(),
            'workflow_status' => ContentArticleWorkflowStatus::Archived->value,
            'published_at' => $firstPublishedAt,
            'first_published_at' => $firstPublishedAt,
            'archived_at' => now()->subMinute(),
            'public_state_changed_at' => now()->subMinute(),
        ]);
    }

    public function withdrawn(): static
    {
        $firstPublishedAt = now()->subDays(3);

        return $this->state(fn (): array => [
            'author_id' => ContentAuthor::factory()->published(),
            'workflow_status' => ContentArticleWorkflowStatus::Withdrawn->value,
            'published_at' => $firstPublishedAt,
            'first_published_at' => $firstPublishedAt,
            'withdrawn_at' => now()->subMinute(),
            'withdrawal_reason' => 'Wycofano testowo.',
            'public_state_changed_at' => now()->subMinute(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'author_id' => ContentAuthor::factory()->published(),
            'workflow_status' => ContentArticleWorkflowStatus::Scheduled->value,
            'scheduled_for' => now()->addHour(),
            'published_at' => null,
            'first_published_at' => null,
        ]);
    }

    public function noindex(): static
    {
        return $this->state(fn (): array => [
            'robots' => 'noindex,follow',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }

    public function breaking(): static
    {
        return $this->published()->state(fn (): array => [
            'type' => ContentArticleType::News->value,
            'is_breaking' => true,
            'breaking_expires_at' => now()->addHour(),
        ]);
    }

    public function guide(): static
    {
        return $this->state(fn (): array => [
            'type' => ContentArticleType::Guide->value,
        ]);
    }
}
