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
 * ->middleware('role:admin,principal') — passes if the user holds ANY of
 * the listed roles. Route-level only; record-level scoping (docs/05 §6)
 * still happens in the repository/policy layer once those modules exist.
 */
final class RoleMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        foreach ($params as $role) {
            if (Auth::hasRole($role)) {
                return $next($request);
            }
        }

        throw new HttpException(403);
    }
}
