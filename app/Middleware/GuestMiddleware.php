<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/** Keeps an already-logged-in user off the login/forgot-password screens. */
final class GuestMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (Auth::check()) {
            return Response::redirect(route('admin.dashboard'));
        }

        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
