<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_match_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranked_match_id')->constrained('ranked_matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slot');
            $table->string('status')->default('matched');
            $table->string('username_snapshot');
            $table->integer('elo_before')->default(1500);
            $table->integer('elo_after')->nullable();
            $table->integer('elo_change')->nullable();
            $table->unsignedSmallInteger('correct_answers')->default(0);
            $table->unsignedSmallInteger('total_answered')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('sum_response_time_ms')->default(0);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['ranked_match_id', 'slot']);
            $table->unique(['ranked_match_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_match_players');
    }
};
