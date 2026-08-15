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
    // Gated to the local environment only: combined with CORS_SUPPORTS_CREDENTIALS=true
    // in any non-local environment, an unconditional localhost/127.0.0.1 pattern would let
    // any local process on that origin pattern make authenticated cross-origin requests.
    // Uses env() directly (not app()->environment()) - config files are read by
    // config:cache/config:clear before the container's env/application bindings
    // are ready, and app() there throws "Target class [env] does not exist".
    'allowed_origins_patterns' => env('APP_ENV', 'production') === 'local' ? [
        '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/',
    ] : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
