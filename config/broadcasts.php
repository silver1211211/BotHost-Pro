<?php

return [
    'limits' => [
        'free' => env('BROADCAST_LIMIT_FREE', 20000),
        'pro' => env('BROADCAST_LIMIT_PRO', 100000),
        'business' => env('BROADCAST_LIMIT_BUSINESS', 'unlimited'),
    ],

    'batch_size' => (int) env('BROADCAST_BATCH_SIZE', 20),
    'message_delay_ms' => (int) env('BROADCAST_MESSAGE_DELAY_MS', 100),
    'batch_delay_seconds' => (int) env('BROADCAST_BATCH_DELAY_SECONDS', 1),

    'image' => [
        'max_size_kb' => (int) env('BROADCAST_IMAGE_MAX_KB', 10240),
        'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
