<?php

return [
    'php_binary' => '/Users/albertnel/Library/Application Support/Herd/bin/php',
    'allowed_jobs' => [
        'App\Services\UserSeederService' => ['seedUsers'],
    ],
    'retry_interval' => 5, // Retry interval in minutes
    'max_retries' => 5,    // Maximum number of retry attempts
];