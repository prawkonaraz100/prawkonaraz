<?php

return [
    'public_auth_drawers' => [
        'lazy' => (bool) env('PUBLIC_AUTH_DRAWERS_LAZY', false),
    ],

    'perf_smoke' => [
        'session_start_ms' => (float) env('PERF_SMOKE_MAX_SESSION_START_MS', 150),
        'first_answer_ms' => (float) env('PERF_SMOKE_MAX_FIRST_ANSWER_MS', 150),
        'session_complete_ms' => (float) env('PERF_SMOKE_MAX_SESSION_COMPLETE_MS', 150),
        'dashboard_ms' => (float) env('PERF_SMOKE_MAX_DASHBOARD_MS', 150),
    ],
];
