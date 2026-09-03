<?php

return [
    'enabled' => env('QUESTION_RELATIONS_ENABLED', true),
    'minimum_links' => (int) env('QUESTION_RELATIONS_MINIMUM_LINKS', 15),
    'automatic_score_threshold' => (float) env('QUESTION_RELATIONS_AUTOMATIC_SCORE_THRESHOLD', 0.50),
    'indexable_topic_minimum_questions' => (int) env('QUESTION_RELATIONS_INDEXABLE_TOPIC_MINIMUM', 10),
    'mobile_visible_links' => 8,
    'desktop_visible_links' => 15,

    /*
    |--------------------------------------------------------------------------
    | V2 shadow comparison
    |--------------------------------------------------------------------------
    |
    | V2 is evaluated next to V1 only when the global V2 kill switch and this
    | shadow switch are enabled, and the question's primary topic has an
    | explicit `mode=shadow` rollout.
    | The shadow result is deliberately not rendered by the public Blade view.
    |
    */
    'v2_enabled' => env('QUESTION_RELATIONS_V2_ENABLED', false),
    'v2_shadow_enabled' => env('QUESTION_RELATIONS_V2_SHADOW_ENABLED', false),
    'v2_shadow_monitor_at' => env('QUESTION_RELATIONS_V2_SHADOW_MONITOR_AT', '04:00'),

    /*
    |--------------------------------------------------------------------------
    | V2 public canary
    |--------------------------------------------------------------------------
    |
    | This is a separate kill switch from shadow. A topic must additionally
    | have an explicit mode=canary rollout with a deterministic cohort before
    | a normal public request may receive V2.
    |
    */
    'v2_canary_enabled' => env('QUESTION_RELATIONS_V2_CANARY_ENABLED', false),
    'v2_canary_monitor_at' => env('QUESTION_RELATIONS_V2_CANARY_MONITOR_AT', '04:15'),
];
