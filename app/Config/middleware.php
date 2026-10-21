<?php

declare(strict_types=1);

use App\Middleware\AuthenticateMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PageViewMiddleware;
use App\Middleware\AdminAccessMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\ThrottleMiddleware;

// Short aliases route definitions use, e.g. ->middleware('role:admin,principal').
// Resolved by app/Core/Pipeline.php.
return [
    'auth'       => AuthenticateMiddleware::class,
    'guest'      => GuestMiddleware::class,
    'csrf'       => CsrfMiddleware::class,
    'throttle'   => ThrottleMiddleware::class,
    'role'       => RoleMiddleware::class,
    'permission' => PermissionMiddleware::class,
    'admin.access' => AdminAccessMiddleware::class,
    'pageview'   => PageViewMiddleware::class,
];
