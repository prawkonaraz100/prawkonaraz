<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->string('answer_kind', 20)->default('choice');
            $table->index(['answer_kind', 'created_at'], 'study_session_answers_kind_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('study_session_answers', function (Blueprint $table): void {
            $table->dropIndex('study_session_answers_kind_created_at_index');
            $table->dropColumn('answer_kind');
        });
    }
};
