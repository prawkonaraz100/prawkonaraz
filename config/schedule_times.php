<?php

// Godziny zadań harmonogramu (routes/console.php). Wartości czytane z .env tutaj,
// żeby działały także po `php artisan config:cache` (env() poza config/ zwraca wtedy null).

return [
    'user_ip_history_prune_at' => env('USER_IP_HISTORY_PRUNE_AT', '03:05'),
    'question_daily_stats_aggregate_at' => env('QUESTION_DAILY_STATS_AGGREGATE_AT', '02:55'),
    'question_answer_daily_stats_aggregate_at' => env('QUESTION_ANSWER_DAILY_STATS_AGGREGATE_AT', '02:57'),
    'monitoring_snapshot_at' => env('MONITORING_SNAPSHOT_AT', '03:00'),
    'question_monthly_stats_rollup_at' => env('QUESTION_MONTHLY_STATS_ROLLUP_AT', '03:10'),
    'study_history_prune_at' => env('STUDY_HISTORY_PRUNE_AT', '03:20'),
    'seo_sitemap_refresh_at' => env('SEO_SITEMAP_REFRESH_AT', '03:30'),
    'sqlite_optimize_at' => env('SQLITE_OPTIMIZE_AT', '03:35'),
    'sqlite_vacuum_at' => env('SQLITE_VACUUM_AT', '04:10'),
];
