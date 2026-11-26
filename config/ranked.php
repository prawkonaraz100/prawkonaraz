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

    // Format meczu. Pytanie nr i (od 0) odblokowuje się po starcie + i × question_interval_seconds,
    // więc gracz odpowiada najwyżej raz na odstęp. 0 = dawny tryb „we własnym tempie”.
    // 15 × 6 s: ostatnie pytanie w 84. s, czas meczu 90 s.
    'match_questions' => max(1, (int) env('RANKED_MATCH_QUESTIONS', 15)),
    'question_interval_seconds' => max(0, (int) env('RANKED_QUESTION_INTERVAL_SECONDS', 6)),
    'match_duration_seconds' => max(10, (int) env('RANKED_MATCH_DURATION_SECONDS', 90)),

    // Ile osób naraz może być w kolejce i w meczach (kolejni dostają „Brak wolnych miejsc”).
    // Pula SSE w PHP-FPM i limit nginx muszą mieć zapas ponad tę wartość (ok. +4).
    'max_concurrent_players' => max(2, (int) env('RANKED_MAX_CONCURRENT_PLAYERS', 100)),

    // Etap 1e: wspólny zegar meczów (`php artisan ranked:clock`, usługa systemd).
    // Gdy włączony i jego puls jest świeży, strumień SSE nie liczy stanu co sekundę:
    // co sse_poll_ms sprawdza wersję w Redis, a pełny stan liczy po zmianie
    // albo co sse_full_refresh_seconds. Zegar stoi dłużej niż clock_stale_seconds → stary tryb.
    'clock_enabled' => filter_var(env('RANKED_CLOCK_ENABLED', false), FILTER_VALIDATE_BOOL),
    'clock_interval_ms' => max(200, (int) env('RANKED_CLOCK_INTERVAL_MS', 1000)),
    'clock_stale_seconds' => max(2, (int) env('RANKED_CLOCK_STALE_SECONDS', 5)),
    'sse_poll_ms' => max(100, (int) env('RANKED_SSE_POLL_MS', 250)),
    'sse_full_refresh_seconds' => max(2, (int) env('RANKED_SSE_FULL_REFRESH_SECONDS', 10)),
    // Czas życia połączenia w trybie lekkim, gdy klient nie poda max_ticks (dawniej 25 s).
    // Mniej ponownych połączeń = mniej rozruchów Laravela i krótszych przerw. Keepalive co 1 s
    // trzyma połączenie przez Cloudflare (100 s) i nginx (fastcgi_read_timeout 120 s).
    'sse_lightweight_lifetime_seconds' => min(110, max(10, (int) env('RANKED_SSE_LIGHTWEIGHT_LIFETIME_SECONDS', 55))),

    // Jak długo pamiętać pozytywną decyzję dostępu do produktu dla API rankingu (0 = bez cache).
    'access_cache_seconds' => max(0, (int) env('RANKED_ACCESS_CACHE_SECONDS', 60)),
];
