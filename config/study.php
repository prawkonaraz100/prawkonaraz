<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PJM module rollout
    |--------------------------------------------------------------------------
    |
    | The recovered production baseline keeps the unfinished PJM module
    | suspended. Tests can enable it explicitly so its dormant access rules
    | remain covered without exposing the module in production.
    |
    */
    'pjm_module_enabled' => filter_var(env('PJM_MODULE_ENABLED', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Study history retention
    |--------------------------------------------------------------------------
    |
    | Completed study sessions are historical data. We keep raw answers for a
    | full year so question analytics remain trustworthy, while admin-facing
    | operational views use a shorter rolling window. In-progress sessions use
    | a much shorter window because abandoned records do not provide lasting
    | value.
    |
    */
    'completed_session_retention_days' => (int) env('STUDY_COMPLETED_SESSION_RETENTION_DAYS', 365),
    'abandoned_session_retention_days' => (int) env('STUDY_ABANDONED_SESSION_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Admin and analytics windows
    |--------------------------------------------------------------------------
    |
    | The admin panel should stay operational and lightweight, so UI metrics
    | there intentionally focus on a shorter activity window. Public question
    | analytics use a separate yearly window. Daily question aggregates keep
    | the latest year in detailed form; anything older is compacted into
    | monthly archive rows.
    |
    */
    'admin_activity_window_days' => (int) env('STUDY_ADMIN_ACTIVITY_WINDOW_DAYS', 90),
    'question_analytics_window_days' => (int) env('STUDY_QUESTION_ANALYTICS_WINDOW_DAYS', 365),
    'question_daily_stats_retention_days' => (int) env('STUDY_QUESTION_DAILY_STATS_RETENTION_DAYS', 365),
    'public_question_answer_stats_min_sample' => (int) env('STUDY_PUBLIC_QUESTION_ANSWER_STATS_MIN_SAMPLE', 30),
    'public_question_answer_stats_cache_hours' => (int) env('STUDY_PUBLIC_QUESTION_ANSWER_STATS_CACHE_HOURS', 30),

    /*
    |--------------------------------------------------------------------------
    | Monitoring thresholds
    |--------------------------------------------------------------------------
    |
    | These thresholds drive the admin monitoring console. They define when
    | the panel should escalate from normal observation to warning or danger.
    |
    */
    'monitoring' => [
        'health_warning_score' => (int) env('STUDY_MONITORING_HEALTH_WARNING_SCORE', 85),
        'health_danger_score' => (int) env('STUDY_MONITORING_HEALTH_DANGER_SCORE', 65),
        'snapshot_completeness_warning_pct' => (int) env('STUDY_MONITORING_SNAPSHOT_WARNING_PCT', 90),
        'snapshot_completeness_danger_pct' => (int) env('STUDY_MONITORING_SNAPSHOT_DANGER_PCT', 75),
        'daily_stats_warning_lag_days' => (int) env('STUDY_MONITORING_DAILY_WARNING_LAG_DAYS', 1),
        'daily_stats_danger_lag_days' => (int) env('STUDY_MONITORING_DAILY_DANGER_LAG_DAYS', 3),
        'forecast_growth_warning_ratio' => (float) env('STUDY_MONITORING_FORECAST_WARNING_RATIO', 1.15),
        'forecast_growth_danger_ratio' => (float) env('STUDY_MONITORING_FORECAST_DANGER_RATIO', 1.30),
        'disk_usage_warning_pct' => (int) env('STUDY_MONITORING_DISK_USAGE_WARNING_PCT', 75),
        'disk_usage_danger_pct' => (int) env('STUDY_MONITORING_DISK_USAGE_DANGER_PCT', 85),
        'disk_free_warning_gb' => (int) env('STUDY_MONITORING_DISK_FREE_WARNING_GB', 10),
        'disk_free_danger_gb' => (int) env('STUDY_MONITORING_DISK_FREE_DANGER_GB', 4),
        'database_latency_warning_ms' => (int) env('STUDY_MONITORING_DB_LATENCY_WARNING_MS', 100),
        'database_latency_danger_ms' => (int) env('STUDY_MONITORING_DB_LATENCY_DANGER_MS', 250),
        'database_runway_warning_days' => (int) env('STUDY_MONITORING_DB_RUNWAY_WARNING_DAYS', 90),
        'database_runway_danger_days' => (int) env('STUDY_MONITORING_DB_RUNWAY_DANGER_DAYS', 30),
        'system_load_warning_ratio' => (float) env('STUDY_MONITORING_SYSTEM_LOAD_WARNING_RATIO', 0.70),
        'system_load_danger_ratio' => (float) env('STUDY_MONITORING_SYSTEM_LOAD_DANGER_RATIO', 1.00),
        'memory_usage_warning_pct' => (int) env('STUDY_MONITORING_MEMORY_USAGE_WARNING_PCT', 75),
        'memory_usage_danger_pct' => (int) env('STUDY_MONITORING_MEMORY_USAGE_DANGER_PCT', 90),
    ],

    'incorrect_question_list' => [
        'write_enabled' => filter_var(env('INCORRECT_QUESTION_LIST_WRITE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'read_mode' => env('INCORRECT_QUESTION_LIST_READ_MODE', 'legacy'),
        'ui_enabled' => filter_var(env('INCORRECT_QUESTION_LIST_UI_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],

    'explanation_sign_references_enabled' => filter_var(
        env('STUDY_EXPLANATION_SIGN_REFERENCES_ENABLED', false),
        FILTER_VALIDATE_BOOL,
    ),
];
