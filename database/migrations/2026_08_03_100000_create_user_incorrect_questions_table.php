<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_incorrect_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_incorrect_at')->nullable();
            $table->timestamp('last_incorrect_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->string('removal_reason', 32)->nullable();
            $table->foreignId('latest_study_session_id')
                ->nullable()
                ->constrained('study_sessions')
                ->nullOnDelete();
            $table->foreignId('latest_answer_id')
                ->nullable()
                ->constrained('study_session_answers')
                ->nullOnDelete();
            $table->string('created_source', 32)->default('answer');
            $table->uuid('backfill_batch_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'removed_at', 'question_id'], 'user_incorrect_questions_active_index');
            $table->index('backfill_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_incorrect_questions');
    }
};
