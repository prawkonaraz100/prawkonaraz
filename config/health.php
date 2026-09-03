<?php

return [
    'backup_max_age_hours' => (int) env(
        'HEALTH_BACKUP_MAX_AGE_HOURS',
        env('BACKUP_SCHEDULE_FREQUENCY', 'weekly') === 'weekly' ? 192 : 36,
    ),
    'monitor_backup' => env('HEALTH_MONITOR_BACKUP', env('APP_ENV') === 'production'),
];
