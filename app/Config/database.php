<?php

declare(strict_types=1);

// Mirrors docs/01-architecture.md §5 "Database": ERRMODE_EXCEPTION,
// EMULATE_PREPARES=false, and utf8mb4 are non-negotiable — see app/Core/Database.php.
return [
    'driver'   => 'mysql',
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'iiui_mardan'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
];
