<?php

return [
    'default_disk' => env('MEDIA_DISK', 'public'),
    'public_disk' => env('MEDIA_PUBLIC_DISK', env('MEDIA_DISK', 'public')),
    'public_base_url' => env('MEDIA_PUBLIC_BASE_URL'),
    'upload_disk' => env('MEDIA_UPLOAD_DISK', 'r2'),
    'upload_prefix' => trim((string) env('MEDIA_UPLOAD_PREFIX', 'media/questions'), '/'),
    'newsroom_disk' => env('MEDIA_NEWSROOM_DISK', env('MEDIA_PUBLIC_DISK', env('MEDIA_DISK', 'public'))),
    'newsroom_prefix' => trim((string) env('MEDIA_NEWSROOM_PREFIX', 'newsroom/articles'), '/'),
    'newsroom_max_dimension' => (int) env('MEDIA_NEWSROOM_MAX_DIMENSION', 10000),
    'pjm_sign_language_disk' => env('PJM_SIGN_LANGUAGE_DISK', 'media_local'),
    'pjm_sign_language_prefix' => trim((string) env('PJM_SIGN_LANGUAGE_PREFIX', 'pjm/sign-language'), '/'),
    'question_audio_disk' => env('QUESTION_AUDIO_DISK', 'media_local'),
    'question_audio_prefix' => trim((string) env('QUESTION_AUDIO_PREFIX', 'audio/questions'), '/'),
    'question_audio_locale' => env('QUESTION_AUDIO_LOCALE', 'pl-PL'),
    'question_audio_voice_provider' => env('QUESTION_AUDIO_VOICE_PROVIDER', 'elevenlabs'),
    'question_audio_voice_id' => env('QUESTION_AUDIO_VOICE_ID', 'N0GCuK2B0qwWozQNTS8F'),
    'question_audio_model_id' => env('QUESTION_AUDIO_MODEL_ID', 'eleven_multilingual_v2'),
    'question_audio_generation_version' => env('QUESTION_AUDIO_GENERATION_VERSION', 'question-v1'),
    'question_audio_encoding_format' => env('QUESTION_AUDIO_ENCODING_FORMAT', 'audio/mpeg'),
    'ffmpeg_binary' => env('MEDIA_FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe_binary' => env('MEDIA_FFPROBE_BINARY', 'ffprobe'),
    'image_thumb_width' => (int) env('MEDIA_IMAGE_THUMB_WIDTH', 480),
    'image_webp_quality' => (int) env('MEDIA_IMAGE_WEBP_QUALITY', 72),
    'presign_ttl_minutes' => (int) env('MEDIA_PRESIGN_TTL_MINUTES', 15),
    'presign_cache_store' => env('MEDIA_PRESIGN_CACHE_STORE'),
    'allowed_mime_types' => [
        'image' => [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'image/avif',
        ],
        'video' => [
            'video/mp4',
        ],
    ],
    'allowed_variants' => [
        'image' => ['full', 'thumb', 'poster'],
        'video' => ['full'],
    ],
    'max_bytes' => [
        'image' => (int) env('MEDIA_MAX_IMAGE_BYTES', 8 * 1024 * 1024),
        'video' => (int) env('MEDIA_MAX_VIDEO_BYTES', 25 * 1024 * 1024),
    ],
];
