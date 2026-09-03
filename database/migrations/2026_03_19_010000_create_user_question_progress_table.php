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
        Schema::create('user_question_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->decimal('easiness_factor', 4, 2)->default(2.50);
            $table->unsignedSmallInteger('interval_days')->default(1);
            $table->unsignedSmallInteger('repetitions')->default(0);
            $table->date('next_review_at');
            $table->unsignedTinyInteger('last_quality')->nullable();
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('correct_streak')->default(0);
            $table->timestamp('last_answered_at')->nullable();
            $table->timestamp('first_answered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'next_review_at']);
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_question_progress');
    }
};
