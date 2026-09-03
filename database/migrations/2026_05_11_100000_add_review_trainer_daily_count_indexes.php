<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->index(
                ['user_id', 'mode', 'license_category_id', 'id'],
                'study_sessions_user_mode_category_id_idx',
            );
        });

        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->index(
                ['study_session_id', 'answered_at'],
                'study_session_answers_session_answered_at_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->dropIndex('study_session_answers_session_answered_at_idx');
        });

        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->dropIndex('study_sessions_user_mode_category_id_idx');
        });
    }
};
