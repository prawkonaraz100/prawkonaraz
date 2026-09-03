<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_snapshots', function (Blueprint $table): void {
            $table->unsignedInteger('review_trainer_events_count')->default(0)->after('content_import_runs_count');
            $table->unsignedInteger('review_memory_progress_count')->default(0)->after('review_trainer_events_count');
            $table->unsignedInteger('review_trainer_daily_answers_count')->default(0)->after('review_memory_progress_count');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_snapshots', function (Blueprint $table): void {
            $table->dropColumn([
                'review_trainer_events_count',
                'review_memory_progress_count',
                'review_trainer_daily_answers_count',
            ]);
        });
    }
};
