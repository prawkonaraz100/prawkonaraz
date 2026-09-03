<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_trainer_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('license_category_id')->nullable()->constrained('license_categories')->nullOnDelete();
            $table->foreignId('study_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->string('event_name');
            $table->string('planner_version')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'event_name', 'occurred_at']);
            $table->index(['planner_version', 'occurred_at']);
            $table->unique(['study_session_id', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_trainer_events');
    }
};
