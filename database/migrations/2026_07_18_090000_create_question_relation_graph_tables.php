<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_seo_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('question_seo_topics')->cascadeOnUpdate()->nullOnDelete();
            $table->string('key', 120)->unique();
            $table->string('slug', 160)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedInteger('question_count')->default(0);
            $table->boolean('is_indexable')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
        });

        Schema::create('question_seo_topic_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_public_explanation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_seo_topic_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('graph');
            $table->timestamps();

            $table->unique('question_public_explanation_id', 'question_seo_membership_question_unique');
            $table->index(['question_seo_topic_id', 'question_public_explanation_id'], 'question_seo_membership_topic_index');
        });

        Schema::create('question_seo_topic_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_topic_id')->constrained('question_seo_topics')->cascadeOnDelete();
            $table->foreignId('target_topic_id')->constrained('question_seo_topics')->cascadeOnDelete();
            $table->string('relation_type', 32)->default('adjacent');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['source_topic_id', 'target_topic_id'], 'question_seo_topic_relations_pair_unique');
        });

        Schema::create('question_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('left_explanation_id')->constrained('question_public_explanations')->cascadeOnDelete();
            $table->foreignId('right_explanation_id')->constrained('question_public_explanations')->cascadeOnDelete();
            $table->string('relation_type', 32);
            $table->string('direction', 24)->default('symmetric');
            $table->string('source', 32)->default('graph');
            $table->string('status', 32)->default('candidate');
            $table->decimal('score', 7, 6)->nullable();
            $table->text('reason')->nullable();
            $table->text('difference')->nullable();
            $table->string('anchor_left_to_right', 500)->nullable();
            $table->string('anchor_right_to_left', 500)->nullable();
            $table->string('review_priority', 24)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->json('metadata')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['left_explanation_id', 'right_explanation_id'], 'question_relations_pair_unique');
            $table->index(['left_explanation_id', 'status'], 'question_relations_left_status_index');
            $table->index(['right_explanation_id', 'status'], 'question_relations_right_status_index');
            $table->index(['source', 'status', 'score'], 'question_relations_publication_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_relations');
        Schema::dropIfExists('question_seo_topic_relations');
        Schema::dropIfExists('question_seo_topic_memberships');
        Schema::dropIfExists('question_seo_topics');
    }
};
