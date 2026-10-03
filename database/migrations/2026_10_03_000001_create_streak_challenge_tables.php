<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streak_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('failed_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->string('status', 16)->default('in_progress');
            $table->unsignedInteger('score')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['license_category_id', 'status', 'score']);
        });

        Schema::create('streak_run_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('streak_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('selected_answer', 1);
            $table->boolean('is_correct');
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['streak_run_id', 'question_id']);
            $table->unique(['streak_run_id', 'sequence']);
        });

        Schema::create('streak_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('best_run_id')->nullable()->constrained('streak_runs')->nullOnDelete();
            $table->unsignedInteger('best_score')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->timestamp('best_achieved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'license_category_id']);
            $table->index(['license_category_id', 'best_score', 'best_achieved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streak_records');
        Schema::dropIfExists('streak_run_answers');
        Schema::dropIfExists('streak_runs');
    }
};
