<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringSnapshot extends Model
{
    protected $fillable = [
        'snapshot_date',
        'database_size_bytes',
        'database_volume_total_bytes',
        'database_volume_free_bytes',
        'database_latency_ms',
        'study_sessions_count',
        'study_session_answers_count',
        'question_daily_stats_count',
        'question_monthly_stats_count',
        'user_ip_histories_count',
        'content_import_runs_count',
        'review_trainer_events_count',
        'review_memory_progress_count',
        'review_trainer_daily_answers_count',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'database_size_bytes' => 'integer',
            'database_volume_total_bytes' => 'integer',
            'database_volume_free_bytes' => 'integer',
            'database_latency_ms' => 'integer',
            'study_sessions_count' => 'integer',
            'study_session_answers_count' => 'integer',
            'question_daily_stats_count' => 'integer',
            'question_monthly_stats_count' => 'integer',
            'user_ip_histories_count' => 'integer',
            'content_import_runs_count' => 'integer',
            'review_trainer_events_count' => 'integer',
            'review_memory_progress_count' => 'integer',
            'review_trainer_daily_answers_count' => 'integer',
        ];
    }
}
