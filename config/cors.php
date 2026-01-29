<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://store.rasiat.sa',
        'http://store.rasiat.sa',
        'https://backstore.rasiat.sa',
        'http://backstore.rasiat.sa',
        'http://localhost:4200',
        'http://localhost:3000',
        'http://localhost:8000',
    ],

    'allowed_origins_patterns' => [
        '#^https?://[a-z0-9-]+\.ngrok-free\.app$#',
        '#^https?://[a-z0-9-]+\.ngrok\.io$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Authorization',
        'X-Requested-With',
        'Content-Type',
        'Accept',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];
