<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        if (Schema::hasTable('question_answer_daily_stats')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS study_session_answers_kind_answered_at_index ON study_session_answers (answer_kind, answered_at)');
        } else {
            Schema::table('study_session_answers', function (Blueprint $table): void {
                $table->index(['answer_kind', 'answered_at'], 'study_session_answers_kind_answered_at_index');
            });
        }

        Schema::create('question_answer_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->date('stats_date');
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('question_topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('selected_answer', 1);
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->timestamps();

            $table->unique(['stats_date', 'question_id', 'selected_answer'], 'qads_date_question_answer_unique');
            $table->index(['question_id', 'stats_date'], 'qads_question_date_index');
            $table->index(['stats_date', 'license_category_id'], 'qads_date_category_index');
            $table->index(['stats_date', 'question_topic_id'], 'qads_date_topic_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_answer_daily_stats');
    }
};
