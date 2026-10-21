<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Exceptions\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Verified on every state-changing request; failure is a clean 419, never a
 * silent partial action — docs/05-security.md §4.
 */
final class CsrfMiddleware implements Middleware
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $token = $request->input('_token');
        if (!is_string($token) || $token === '') {
            $token = $request->header('X-CSRF-TOKEN');
        }

        if (!Csrf::verify(is_string($token) ? $token : null)) {
            throw new HttpException(419);
        }

        return $next($request);
    }
}
