<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->string('presence_state')->default('online')->after('status');
            $table->timestamp('last_seen_at')->nullable()->after('sum_response_time_ms');
            $table->timestamp('disconnected_at')->nullable()->after('last_seen_at');
            $table->timestamp('reconnect_deadline_at')->nullable()->after('disconnected_at');

            $table->index(['ranked_match_id', 'presence_state'], 'ranked_match_players_presence_index');
        });
    }

    public function down(): void
    {
        Schema::table('ranked_match_players', function (Blueprint $table) {
            $table->dropIndex('ranked_match_players_presence_index');
            $table->dropColumn([
                'presence_state',
                'last_seen_at',
                'disconnected_at',
                'reconnect_deadline_at',
            ]);
        });
    }
};
