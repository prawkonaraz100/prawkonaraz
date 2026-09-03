<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_topic_completion_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_topic_id')->constrained('question_topics')->cascadeOnDelete();
            $table->string('question_scope', 20)->default('all');
            $table->foreignId('last_study_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->unsignedInteger('last_duration_seconds')->nullable();
            $table->decimal('last_score_percent', 5, 2)->nullable();
            $table->timestamp('last_completed_at')->nullable();
            $table->string('last_ui_shell', 20)->nullable();
            $table->unsignedSmallInteger('last_questions_count')->nullable();
            $table->string('last_question_ids_hash', 64)->nullable();
            $table->foreignId('best_study_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->unsignedInteger('best_duration_seconds')->nullable();
            $table->timestamp('best_completed_at')->nullable();
            $table->string('best_ui_shell', 20)->nullable();
            $table->unsignedSmallInteger('best_questions_count')->nullable();
            $table->string('best_question_ids_hash', 64)->nullable();
            $table->unsignedInteger('completion_count')->default(0);
            $table->unsignedInteger('perfect_completion_count')->default(0);
            $table->timestamp('first_completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'license_category_id', 'question_topic_id', 'question_scope'],
                'topic_records_unique_user_category_topic_scope',
            );
            $table->index(['user_id', 'license_category_id'], 'topic_records_user_category_idx');
            $table->index(
                ['license_category_id', 'question_topic_id', 'question_scope', 'best_duration_seconds'],
                'topic_records_leaderboard_idx',
            );
            $table->index(
                ['license_category_id', 'question_topic_id', 'question_scope', 'best_question_ids_hash', 'best_duration_seconds'],
                'topic_records_hash_leaderboard_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_topic_completion_records');
    }
};
