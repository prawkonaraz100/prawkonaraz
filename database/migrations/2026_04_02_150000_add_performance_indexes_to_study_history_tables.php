<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->index(['status', 'completed_at'], 'study_sessions_status_completed_at_index');
            $table->index(['user_id', 'created_at'], 'study_sessions_user_created_at_index');
            $table->index(['license_category_id', 'created_at'], 'study_sessions_category_created_at_index');
        });

        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->index(['question_id', 'answered_at'], 'study_session_answers_question_answered_at_index');
            $table->index(['question_id', 'created_at'], 'study_session_answers_question_created_at_index');
            $table->index(['study_session_id', 'created_at'], 'study_session_answers_session_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->dropIndex('study_session_answers_question_answered_at_index');
            $table->dropIndex('study_session_answers_question_created_at_index');
            $table->dropIndex('study_session_answers_session_created_at_index');
        });

        Schema::table('study_sessions', function (Blueprint $table): void {
            $table->dropIndex('study_sessions_status_completed_at_index');
            $table->dropIndex('study_sessions_user_created_at_index');
            $table->dropIndex('study_sessions_category_created_at_index');
        });
    }
};
