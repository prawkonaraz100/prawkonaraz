<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_trainer_daily_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->constrained('license_categories')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_session_answer_id')->constrained('study_session_answers')->cascadeOnDelete();
            $table->date('review_day');
            $table->string('daily_plan_policy_version', 80);
            $table->string('answer_kind', 30);
            $table->boolean('is_correct')->default(false);
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique('study_session_answer_id', 'rtda_answer_unique');
            $table->index(['user_id', 'license_category_id', 'review_day'], 'rtda_user_category_day_index');
            $table->index(['user_id', 'review_day'], 'rtda_user_day_index');
            $table->index(['question_id', 'review_day'], 'rtda_question_day_index');
            $table->index('daily_plan_policy_version', 'rtda_policy_version_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_trainer_daily_answers');
    }
};
