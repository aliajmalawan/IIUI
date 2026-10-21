<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

final class AuthenticateMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (!Auth::check()) {
            Session::set('_intended_url', $request->path());

            return Response::redirect(route('login'));
        }

        return $next($request);
    }
}
