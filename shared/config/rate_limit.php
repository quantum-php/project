<?php

return [
    /**
     * ---------------------------------------------------------
     * Default rate limit adapter
     * ---------------------------------------------------------
     */
    'default' => env('RATE_LIMIT_ADAPTER', 'file'),

    /**
     * ---------------------------------------------------------
     * Rate limit connections
     * ---------------------------------------------------------
     */
    'file' => [
        'prefix' => str_replace(' ', '', env('APP_NAME') ?? ''),
        'path' => base_dir() . DS . 'cache' . DS . 'data',
        'ttl' => 60,
    ],
    'redis' => [
        'prefix' => str_replace(' ', '', env('APP_NAME') ?? ''),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env_int('REDIS_PORT', 6379),
        'ttl' => 60,
    ],
];
