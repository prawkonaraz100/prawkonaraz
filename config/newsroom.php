<?php

return [
    'public_enabled' => env('NEWSROOM_PUBLIC_ENABLED', false),

    // Public read caches are event-invalidated; the short TTL is a safety net
    // for time-window changes such as placement/breaking expiry.
    'cache_ttl_seconds' => 60,
];
