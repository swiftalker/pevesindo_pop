<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Odoo Connection
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the Odoo ERP instance.
    | Uses JSON-2 API (Odoo 17+) with Bearer token authentication.
    |
    */

    'base_url' => env('ODOO_BASE_URL', 'https://pevesindo-staging6.odoo.com/'),

    'database' => env('ODOO_DATABASE', 'pevesindo-staging6'),

    'api_key' => env('ODOO_API_KEY'),

    'login' => env('ODOO_LOGIN'),

    'password' => env('ODOO_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('ODOO_TIMEOUT', 30),

    'connect_timeout' => (int) env('ODOO_CONNECT_TIMEOUT', 10),

    'retry_times' => (int) env('ODOO_RETRY_TIMES', 3),

    'retry_sleep_ms' => (int) env('ODOO_RETRY_SLEEP_MS', 500),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Redis Throttle)
    |--------------------------------------------------------------------------
    |
    | Controls how many API calls can be made within the given window.
    | Default: 3200 requests per 86400 seconds (1 day).
    | Adjust based on your Odoo plan (e.g., 100k requests/month ≈ 3200/day).
    |
    */

    'throttle_allow' => (int) env('ODOO_THROTTLE_ALLOW', 3200),

    'throttle_every' => (int) env('ODOO_THROTTLE_EVERY', 86400),

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */

    'queue_connection' => env('ODOO_QUEUE_CONNECTION', 'redis'),

    'queue_name' => env('ODOO_QUEUE_NAME', 'odoo'),

    /*
    |--------------------------------------------------------------------------
    | Session (for PDF Reports)
    |--------------------------------------------------------------------------
    |
    | Odoo report endpoints are web controllers that require session cookies
    | and CSRF tokens. The session TTL controls how often we re-authenticate.
    |
    */

    'session_ttl_minutes' => (int) env('ODOO_SESSION_TTL', 50),

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    */

    'default_lang' => env('ODOO_DEFAULT_LANG', 'id_ID'),

    'user_agent' => 'PopApp/1.0 Laravel-Http',

];
