<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Exceptions\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Gate for the whole /admin area: being logged in is not enough — students and
 * parents have accounts too, but no business in the staff console. Also stops
 * browsers and proxies from caching admin pages (the back button after logout
 * must not show them again).
 */
final class AdminAccessMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (!Auth::canAccessAdmin()) {
            throw new HttpException(403);
        }

        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
