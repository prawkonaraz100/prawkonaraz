<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tryb rankingowy: mecz z nagraniem (2026-11-25).
 * - ranked_matches.replay_source_*: z którego meczu i którego gracza pochodzi nagranie,
 * - ranked_match_players.is_replay: wiersz odtwarzanego przeciwnika (nie gra na żywo,
 *   nie zmienia mu się ELO i nie jest liczony jako uczestnik w jego historii meczów).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranked_matches', function (Blueprint $table) {
            $table->foreignId('replay_source_match_id')
                ->nullable()
                ->after('license_category_id')
                ->constrained('ranked_matches')
                ->nullOnDelete();
            $table->foreignId('replay_source_user_id')
                ->nullable()
                ->after('replay_source_match_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->boolean('is_replay')->default(false)->after('slot');
            $table->index(['user_id', 'is_replay']);
        });
    }

    public function down(): void
    {
        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_replay']);
            $table->dropColumn('is_replay');
        });

        Schema::table('ranked_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replay_source_user_id');
            $table->dropConstrainedForeignId('replay_source_match_id');
        });
    }
};
