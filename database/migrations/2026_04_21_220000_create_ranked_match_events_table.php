<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranked_match_id')->constrained('ranked_matches')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_id')->unique();
            $table->string('event_name');
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['ranked_match_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_match_events');
    }
};
