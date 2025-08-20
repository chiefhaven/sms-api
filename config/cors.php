<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin requests.
    | These settings determine which domains are allowed to access your API.
    |
    */

    'paths' => ['api/*', 'send-sms'], // endpoints that accept CORS requests

    'allowed_methods' => ['*'], // allow all HTTP methods (GET, POST, etc.)

    'allowed_origins' => ['http://dsms.darondrivingschool.com',
        'https://dsms.darondrivingschool.com',],
    // or specify your frontend domains:
    // ['https://frontend1.example.com', 'https://frontend2.example.com']

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // allow all headers (Authorization, Content-Type, etc.)

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
    // true if using cookies/session authentication; false for token-based API auth

];
