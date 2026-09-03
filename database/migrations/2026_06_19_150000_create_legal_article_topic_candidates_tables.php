<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_article_topic_candidates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('level', 32)->index();
            $table->string('status', 32)->default('candidate')->index();
            $table->string('existing_article_slug')->nullable()->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('rule_version', 64);
            $table->timestamps();
        });

        Schema::create('legal_article_topic_candidate_question', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legal_article_topic_candidate_id')
                ->constrained('legal_article_topic_candidates')
                ->cascadeOnDelete();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete();
            $table->string('canonical_external_id');
            $table->string('match_type', 32)->index();
            $table->string('matched_by', 255)->nullable();
            $table->unsignedTinyInteger('confidence');
            $table->string('assignment_source', 32)->default('prompt_scan')->index();
            $table->timestamps();

            $table->unique(
                ['legal_article_topic_candidate_id', 'question_id'],
                'legal_article_candidate_question_unique',
            );
            $table->index(
                ['legal_article_topic_candidate_id', 'canonical_external_id'],
                'legal_article_candidate_canonical_index',
            );
            $table->index(['question_id', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_article_topic_candidate_question');
        Schema::dropIfExists('legal_article_topic_candidates');
    }
};
