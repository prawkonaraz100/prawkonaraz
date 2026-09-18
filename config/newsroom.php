<?php

return [
    'public_enabled' => env('NEWSROOM_PUBLIC_ENABLED', false),

    // Public read caches are event-invalidated; the short TTL is a safety net
    // for time-window changes such as placement/breaking expiry.
    'cache_ttl_seconds' => 60,

    // Stable ID-range sharding avoids offset churn when individual articles
    // leave the indexable corpus. Protocol limits are enforced separately.
    'article_sitemap_shard_id_span' => 10000,

    // Google News currently allows at most 1,000 news:news entries per file.
    // Keep this configurable downward for deterministic boundary regression.
    'news_sitemap_max_entries' => 1000,
];
