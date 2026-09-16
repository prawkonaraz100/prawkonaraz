<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->foreignId('category_id')
                ->constrained('content_categories')
                ->restrictOnDelete();
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('content_authors')
                ->restrictOnDelete();
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('content_authors')
                ->restrictOnDelete();
            $table->string('origin_type', 32)->default('original');

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('lead')->nullable();
            $table->jsonb('body_blocks')->nullable();
            $table->smallInteger('body_schema_version')->default(1);
            $table->jsonb('key_points')->nullable();
            $table->text('correction_note')->nullable();
            $table->text('editorial_note')->nullable();

            $table->string('regulatory_status', 32)->default('not_applicable');
            $table->date('effective_from')->nullable();
            $table->text('change_summary')->nullable();
            $table->text('applies_to')->nullable();
            $table->text('exam_impact')->nullable();

            $table->string('workflow_status', 32)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('first_published_at')->nullable();
            $table->timestampTz('scheduled_for')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('needs_review_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->text('withdrawal_reason')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_breaking')->default(false);
            $table->timestampTz('breaking_expires_at')->nullable();
            $table->smallInteger('editorial_priority')->default(0);

            $table->string('hero_image_path', 1024)->nullable();
            $table->string('hero_image_alt', 500)->nullable();
            $table->unsignedInteger('hero_image_width')->nullable();
            $table->unsignedInteger('hero_image_height')->nullable();
            $table->text('hero_image_caption')->nullable();
            $table->decimal('hero_focal_x', 5, 4)->nullable();
            $table->decimal('hero_focal_y', 5, 4)->nullable();
            $table->string('og_image_path', 1024)->nullable();
            $table->string('og_image_alt', 500)->nullable();
            $table->unsignedInteger('og_image_width')->nullable();
            $table->unsignedInteger('og_image_height')->nullable();
            $table->string('image_credit', 500)->nullable();
            $table->text('image_license_note')->nullable();

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('robots', 128)->nullable();

            $table->timestampTz('source_checked_at')->nullable();
            $table->timestampTz('freshness_review_due_at')->nullable();
            $table->timestampTz('last_substantive_update_at')->nullable();
            $table->timestampTz('public_state_changed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['workflow_status', 'first_published_at'],
                'content_articles_workflow_first_published_idx',
            );
            $table->index(
                ['category_id', 'workflow_status', 'first_published_at'],
                'content_articles_category_workflow_published_idx',
            );
            $table->index(
                ['type', 'workflow_status', 'first_published_at'],
                'content_articles_type_workflow_published_idx',
            );
            $table->index(
                ['is_featured', 'workflow_status', 'editorial_priority'],
                'content_articles_featured_workflow_priority_idx',
            );
            $table->index(
                ['is_breaking', 'breaking_expires_at'],
                'content_articles_breaking_expires_idx',
            );
            $table->index('freshness_review_due_at', 'content_articles_freshness_due_idx');
            $table->index(
                ['scheduled_for', 'workflow_status'],
                'content_articles_scheduled_workflow_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_articles');
    }
};
