<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stats_date');
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('question_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('correct_answers_count')->default(0);
            $table->unsignedInteger('incorrect_answers_count')->default(0);
            $table->unsignedInteger('response_time_count')->default(0);
            $table->unsignedInteger('response_time_avg_ms')->nullable();
            $table->unsignedInteger('response_time_median_ms')->nullable();
            $table->unsignedInteger('progress_users_count')->default(0);
            $table->unsignedInteger('mastered_progress_users_count')->default(0);
            $table->decimal('avg_total_attempts', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['stats_date', 'question_id']);
            $table->index(['stats_date', 'license_category_id']);
            $table->index(['stats_date', 'question_topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_daily_stats');
    }
};
