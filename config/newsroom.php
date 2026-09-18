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

    // Bounded latest-news window for the public Atom feed.
    'feed_items_limit' => 50,

    // SEO artifact freshness uses the configured cache store. Production uses
    // Redis, while tests can inherit the isolated default cache store.
    'seo_artifact_cache_store' => env('NEWSROOM_SEO_ARTIFACT_CACHE_STORE'),

    // Long enough to cover one full sitemap generation/audit pass. The
    // version marker remains dirty if a refresh fails or changes race it.
    'seo_artifact_refresh_lock_seconds' => (int) env('NEWSROOM_SEO_ARTIFACT_REFRESH_LOCK_SECONDS', 300),
];
