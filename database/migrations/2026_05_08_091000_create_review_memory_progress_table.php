<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_memory_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained('license_categories')->cascadeOnDelete();
            $table->unsignedInteger('verified_attempts_count')->default(0);
            $table->unsignedInteger('verified_correct_count')->default(0);
            $table->unsignedInteger('verified_unknown_count')->default(0);
            $table->unsignedInteger('verified_incorrect_count')->default(0);
            $table->unsignedInteger('verified_correct_streak')->default(0);
            $table->string('last_verified_result', 30)->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('last_study_session_answer_id')
                ->nullable()
                ->constrained('study_session_answers')
                ->nullOnDelete();
            $table->date('next_verified_review_at')->nullable();
            $table->string('verified_memory_state', 40)->default('new');
            $table->string('source_policy_version', 80)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'license_category_id', 'next_verified_review_at'], 'rmp_user_category_next_index');
            $table->index(['user_id', 'verified_memory_state'], 'rmp_user_state_index');
            $table->index('last_study_session_answer_id', 'rmp_last_answer_index');
            $table->index('source_policy_version', 'rmp_policy_version_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_memory_progress');
    }
};
