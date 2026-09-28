<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | El frontend (Vite/React) consume la API desde otro origen. Los orígenes
    | permitidos se definen en CORS_ALLOWED_ORIGINS (separados por coma) y por
    | defecto se usa FRONTEND_URL. La autenticación va por token Bearer (JWT),
    | por lo que no se necesitan cookies ni credenciales.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
