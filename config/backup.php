<?php

return [
    'disk' => env('BACKUP_DISK', env('APP_ENV') === 'production' ? 'r2' : 'backup_local'),
    'directory' => trim((string) env('BACKUP_DIRECTORY', 'backups/database'), '/'),
    'manifest_directory' => trim((string) env('BACKUP_MANIFEST_DIRECTORY', 'backups/database-manifests'), '/'),
    'temporary_directory' => env('BACKUP_TEMP_DIRECTORY', storage_path('app/backup-tmp')),
    'schedule' => [
        'frequency' => env('BACKUP_SCHEDULE_FREQUENCY', 'weekly'),
        'day_of_week' => (int) env('BACKUP_SCHEDULE_DAY_OF_WEEK', 0),
        'at' => env('BACKUP_SCHEDULE_AT', '02:15'),
    ],
    'keep_daily' => (int) env('BACKUP_KEEP_DAILY', 7),
    'keep_weekly' => (int) env('BACKUP_KEEP_WEEKLY', 4),
    'keep_monthly' => (int) env('BACKUP_KEEP_MONTHLY', 3),
    'pgsql' => [
        'pg_dump_binary' => env('BACKUP_PG_DUMP_BINARY', 'pg_dump'),
        'psql_binary' => env('BACKUP_PSQL_BINARY', 'psql'),
        'timeout_seconds' => (int) env('BACKUP_PGSQL_TIMEOUT_SECONDS', 300),
    ],
];
