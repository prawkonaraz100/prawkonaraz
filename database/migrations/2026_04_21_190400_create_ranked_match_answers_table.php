<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_match_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranked_match_id')->constrained('ranked_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('question_number');
            $table->string('selected_answer');
            $table->boolean('is_correct');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['ranked_match_id', 'user_id', 'question_id']);
            $table->index(['ranked_match_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_match_answers');
    }
};
