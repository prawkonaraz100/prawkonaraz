<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranked_player_ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->integer('rating')->default(1500);
            $table->integer('peak_rating')->default(1500);
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->integer('current_streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranked_player_ratings');
    }
};
