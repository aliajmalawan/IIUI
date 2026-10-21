<?php

declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'IIUI Schools & College'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url'      => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Karachi'),
    'locale'   => env('APP_LOCALE', 'en'),
    'key'      => env('APP_KEY', ''),
];
