<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_acts', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('short_title')->nullable();
            $table->string('publisher')->nullable();
            $table->string('source_url', 2048);
            $table->string('eli_url', 2048)->nullable();
            $table->string('isap_url', 2048)->nullable();
            $table->date('effective_from')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('legal_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at', 'sort_order']);
        });

        Schema::create('legal_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_act_id')
                ->constrained('legal_acts')
                ->restrictOnDelete();
            $table->string('type', 32);
            $table->string('label', 80);
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('official_excerpt')->nullable();
            $table->string('source_url', 2048);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();

            $table->unique(['legal_act_id', 'slug']);
            $table->index(['legal_act_id', 'status']);
        });

        Schema::create('legal_content_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_topic_id')
                ->constrained('legal_topics')
                ->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->text('intro')->nullable();
            $table->text('summary')->nullable();
            $table->text('exam_context')->nullable();
            $table->longText('body')->nullable();
            $table->json('key_points')->nullable();
            $table->text('source_note')->nullable();
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('content_authors')
                ->nullOnDelete();
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('content_authors')
                ->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['legal_topic_id', 'status']);
        });

        Schema::create('legal_content_page_legal_unit', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_content_page_id')
                ->constrained('legal_content_pages')
                ->cascadeOnDelete();
            $table->foreignId('legal_unit_id')
                ->constrained('legal_units')
                ->cascadeOnDelete();
            $table->string('relation_type', 32)->default('direct_basis');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['legal_content_page_id', 'legal_unit_id']);
            $table->index(['legal_content_page_id', 'sort_order']);
        });

        Schema::create('question_legal_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();
            $table->foreignId('legal_unit_id')
                ->constrained('legal_units')
                ->restrictOnDelete();
            $table->foreignId('legal_topic_id')
                ->constrained('legal_topics')
                ->restrictOnDelete();
            $table->foreignId('legal_content_page_id')
                ->nullable()
                ->constrained('legal_content_pages')
                ->nullOnDelete();
            $table->string('relation_type', 32)->default('direct_basis');
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->unsignedTinyInteger('confidence')->default(80);
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('content_authors')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['question_id', 'legal_unit_id', 'legal_topic_id'], 'question_legal_reference_unique');
            $table->index(['question_id', 'status']);
            $table->index(['legal_content_page_id', 'status'], 'question_legal_reference_page_status_index');
        });

        Schema::create('traffic_sign_legal_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('traffic_sign_id')
                ->constrained('traffic_signs')
                ->cascadeOnDelete();
            $table->foreignId('legal_unit_id')
                ->constrained('legal_units')
                ->restrictOnDelete();
            $table->foreignId('legal_topic_id')
                ->constrained('legal_topics')
                ->restrictOnDelete();
            $table->foreignId('legal_content_page_id')
                ->nullable()
                ->constrained('legal_content_pages')
                ->nullOnDelete();
            $table->string('relation_type', 32)->default('direct_basis');
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('content_authors')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['traffic_sign_id', 'legal_unit_id', 'legal_topic_id'], 'traffic_sign_legal_reference_unique');
            $table->index(['traffic_sign_id', 'status']);
        });

        Schema::create('legal_source_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_unit_id')
                ->constrained('legal_units')
                ->cascadeOnDelete();
            $table->foreignId('checked_by')
                ->nullable()
                ->constrained('content_authors')
                ->nullOnDelete();
            $table->timestamp('checked_at');
            $table->string('source_url', 2048);
            $table->string('source_status', 64)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['legal_unit_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_source_checks');
        Schema::dropIfExists('traffic_sign_legal_references');
        Schema::dropIfExists('question_legal_references');
        Schema::dropIfExists('legal_content_page_legal_unit');
        Schema::dropIfExists('legal_content_pages');
        Schema::dropIfExists('legal_units');
        Schema::dropIfExists('legal_topics');
        Schema::dropIfExists('legal_acts');
    }
};
