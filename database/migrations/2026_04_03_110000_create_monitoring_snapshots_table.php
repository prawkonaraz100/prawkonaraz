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
        Schema::create('monitoring_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->unsignedBigInteger('database_size_bytes')->nullable();
            $table->unsignedInteger('study_sessions_count')->default(0);
            $table->unsignedInteger('study_session_answers_count')->default(0);
            $table->unsignedInteger('question_daily_stats_count')->default(0);
            $table->unsignedInteger('question_monthly_stats_count')->default(0);
            $table->unsignedInteger('user_ip_histories_count')->default(0);
            $table->unsignedInteger('content_import_runs_count')->default(0);
            $table->timestamps();

            $table->unique('snapshot_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_snapshots');
    }
};
