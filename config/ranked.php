<?php

return [
    'presence_store' => env('RANKED_PRESENCE_STORE', env('CACHE_STORE', 'database')),
    'presence_key_prefix' => env('RANKED_PRESENCE_KEY_PREFIX', 'ranked:presence'),
    'presence_ttl_seconds' => (int) env('RANKED_PRESENCE_TTL_SECONDS', 90),
    'websocket_enabled' => filter_var(env('RANKED_WEBSOCKET_ENABLED', false), FILTER_VALIDATE_BOOL),
    'websocket_url' => env('RANKED_WEBSOCKET_URL'),
    'websocket_host' => env('RANKED_WEBSOCKET_HOST', '0.0.0.0'),
    'websocket_port' => (int) env('RANKED_WEBSOCKET_PORT', 8080),
    'websocket_path' => env('RANKED_WEBSOCKET_PATH', '/ranked'),
    'websocket_publish_interval_ms' => (int) env('RANKED_WEBSOCKET_PUBLISH_INTERVAL_MS', 1000),
    'websocket_ticket_store' => env('RANKED_WEBSOCKET_TICKET_STORE'),
    'websocket_ticket_key_prefix' => env('RANKED_WEBSOCKET_TICKET_KEY_PREFIX', 'ranked:websocket-ticket'),
    'websocket_ticket_ttl_seconds' => (int) env('RANKED_WEBSOCKET_TICKET_TTL_SECONDS', 180),
    'sse_enabled' => filter_var(env('RANKED_SSE_ENABLED', true), FILTER_VALIDATE_BOOL),
    'sse_retry_ms' => (int) env('RANKED_SSE_RETRY_MS', 2000),
];
