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
        Schema::create('user_traffic_sign_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traffic_sign_id')->constrained('traffic_signs')->cascadeOnDelete();
            $table->string('state', 24)->default('new');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('incorrect_count')->default(0);
            $table->unsignedInteger('correct_streak')->default(0);
            $table->foreignId('last_confused_with_traffic_sign_id')->nullable()->constrained('traffic_signs')->nullOnDelete();
            $table->timestamp('last_answered_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'traffic_sign_id']);
            $table->index(['user_id', 'state', 'next_review_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_traffic_sign_progress');
    }
};
