<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranked_matches', function (Blueprint $table) {
            $table->timestamp('countdown_started_at')->nullable()->after('matched_at');
            $table->unsignedSmallInteger('countdown_seconds')->nullable()->after('countdown_started_at');
        });

        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->timestamp('ready_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->dropColumn('ready_at');
        });

        Schema::table('ranked_matches', function (Blueprint $table) {
            $table->dropColumn([
                'countdown_started_at',
                'countdown_seconds',
            ]);
        });
    }
};
