<?php

/*
 * CORS: For Flutter web (e.g. http://localhost:62733) to call this API, the server
 * must use this config and run: php artisan config:clear
 * If your web server (nginx/Apache) adds Access-Control-Allow-Origin, remove it and let Laravel handle CORS.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://62.84.188.239')))),

    // Required for Flutter web / Vite / local dev (any localhost or 127.0.0.1 port).
    // Without this, the middleware sends only the single allowed_origin and browser blocks the request.
    'allowed_origins_patterns' => [
        '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
