<?php

return [
    'allowed_jobs' => [
        'App\Services\UserSeederService' => ['seedUsers'],
    ],
    'retry_interval' => 5, // Retry interval in minutes
    'max_retries' => 5,    // Maximum number of retry attempts
];