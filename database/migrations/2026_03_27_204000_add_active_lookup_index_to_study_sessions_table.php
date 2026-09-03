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
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'status', 'started_at', 'id'],
                'study_sessions_user_status_started_id_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex('study_sessions_user_status_started_id_idx');
        });
    }
};
