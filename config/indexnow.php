<?php

return [
    'enabled' => env('INDEXNOW_ENABLED', false),

    'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),

    'host' => env(
        'INDEXNOW_HOST',
        parse_url((string) env('APP_URL', 'https://prawkonaraz.pl'), PHP_URL_HOST) ?: 'prawkonaraz.pl',
    ),

    'key' => env('INDEXNOW_KEY'),

    'key_source' => env('INDEXNOW_KEY_SOURCE'),

    'key_location' => env('INDEXNOW_KEY_LOCATION'),

    'timeout' => (int) env('INDEXNOW_TIMEOUT', 10),

    'max_urls_per_request' => (int) env('INDEXNOW_MAX_URLS_PER_REQUEST', 10000),

    'automation_enabled' => env('INDEXNOW_AUTOMATION_ENABLED', false),

    'queue_batch_size' => (int) env('INDEXNOW_QUEUE_BATCH_SIZE', 50),

    'queue_debounce_minutes' => (int) env('INDEXNOW_QUEUE_DEBOUNCE_MINUTES', 10),

    'queue_retry_minutes' => (int) env('INDEXNOW_QUEUE_RETRY_MINUTES', 60),
];
