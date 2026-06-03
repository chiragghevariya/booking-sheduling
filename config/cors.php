<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | CORS only matters for *browser* clients. The Vue SPA needs it because
    | it sends cookies (Sanctum SPA mode). A React Native app on a real
    | device makes native HTTP calls and is NOT subject to CORS, so the
    | mobile token flow works regardless. We still allow common Expo dev
    | origins below so an Expo Web preview also works locally.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    /*
    | Exact origins. Keep the localhost entries for the SPA. The Expo Web
    | preview commonly serves on 8081 or 19006.
    */
    'allowed_origins' => [
        'http://localhost',
        'http://localhost:5173',
        'http://localhost:8000',
        'http://localhost:8081',
        'http://localhost:19006',
        'http://127.0.0.1',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8081',
        'http://127.0.0.1:19006',
    ],

    /*
    | Regex patterns for LOCAL DEVELOPMENT only. Allows any private-LAN IP
    | (RFC1918) on any port — covers the case where you open the API from
    | your phone or Expo Web on http://192.168.x.x:8000. The patterns are
    | scoped to private network ranges so they cannot leak to the public
    | internet.
    |
    | ⚠️ PRODUCTION: remove these patterns and pin `allowed_origins` to
    |    your real frontend hostnames (e.g. https://app.example.com).
    */
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^https?://10\.\d+\.\d+\.\d+(:\d+)?$#',
        '#^https?://192\.168\.\d+\.\d+(:\d+)?$#',
        '#^https?://172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | Must stay true so the Vue SPA's cookie auth keeps working. Note that
    | a token client (mobile) sends no cookies — this flag has no effect
    | on Bearer-token requests.
    */
    'supports_credentials' => true,

];
