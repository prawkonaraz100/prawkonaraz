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
        Schema::create('traffic_sign_learning_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_sign_learning_session_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traffic_sign_id')->constrained('traffic_signs')->restrictOnDelete();
            $table->foreignId('selected_traffic_sign_id')->nullable()->constrained('traffic_signs')->nullOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('answer_mode', 32)->default('sign_to_meaning');
            $table->json('options')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table
                ->foreign('traffic_sign_learning_session_id', 'ts_learning_answers_session_fk')
                ->references('id')
                ->on('traffic_sign_learning_sessions')
                ->cascadeOnDelete();
            $table->unique(['traffic_sign_learning_session_id', 'position'], 'ts_learning_answers_session_position_unique');
            $table->index(['user_id', 'traffic_sign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_sign_learning_answers');
    }
};
