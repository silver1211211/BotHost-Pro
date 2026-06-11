<?php

return [
    'mode' => env('RUNTIME_MODE', 'local'),
    'docker' => [
        'enabled' => filter_var(env('RUNTIME_DOCKER_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'image' => env('RUNTIME_DOCKER_IMAGE', 'bothost-node-runtime'),
        'container_prefix' => env('RUNTIME_CONTAINER_PREFIX', 'bothost-bot'),
        'http_port_start' => (int) env('RUNTIME_HTTP_PORT_START', 8800),
        'internal_port' => (int) env('RUNTIME_INTERNAL_PORT', 8787),
        'timeout_ms' => (int) env('RUNTIME_TIMEOUT_MS', 5000),
        'memory_limit' => env('RUNTIME_MEMORY_LIMIT', '128m'),
        'cpu_limit' => env('RUNTIME_CPU_LIMIT', '0.25'),
        'network' => env('RUNTIME_NETWORK', 'bothost-runtime'),
        'keep_paused_warm' => filter_var(env('RUNTIME_KEEP_PAUSED_WARM', false), FILTER_VALIDATE_BOOLEAN),
        'auto_restart' => filter_var(env('RUNTIME_AUTO_RESTART', true), FILTER_VALIDATE_BOOLEAN),
        'build_context' => env('RUNTIME_BUILD_CONTEXT', 'runtime-node'),
    ],
];
