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
        Schema::create('traffic_sign_learning_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 32)->default('recognition');
            $table->string('status', 24)->default('in_progress');
            $table->unsignedSmallInteger('total_signs_count')->default(0);
            $table->unsignedSmallInteger('correct_answers_count')->default(0);
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_sign_learning_sessions');
    }
};
