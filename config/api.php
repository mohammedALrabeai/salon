<?php

return [
    'version' => env('API_VERSION', '1.0.0'),
    'access_token_ttl' => env('API_ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl_days' => env('API_REFRESH_TOKEN_TTL_DAYS', 30),
    'lockout' => [
        'max_attempts' => env('API_LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('API_LOGIN_LOCKOUT_MINUTES', 15),
    ],
];
