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
        Schema::create('study_session_answers', function (Blueprint $table) {
            $table->id();
            // Foreign keys are added in a follow-up migration so fresh PostgreSQL
            // bootstraps do not depend on alphabetical execution of same-second migrations.
            $table->foreignId('study_session_id');
            $table->foreignId('question_id');
            $table->string('selected_answer', 1)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['study_session_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_session_answers');
    }
};
