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
 * ->middleware('permission:fees.collect,fees.waive_fine') — any-of match on full permission slugs.
 *
 * A bare module name (no dot) is expanded from the request itself, so one line
 * protects a whole route group:
 *
 *   ->middleware('permission:cms_posts')
 *
 *   GET  …            -> cms_posts.view
 *   GET  …/create     -> cms_posts.create
 *   GET  …/{id}/edit  -> cms_posts.edit
 *   GET  …?export=csv -> cms_posts.export
 *   POST …/delete     -> cms_posts.delete
 *   POST (anything else) -> cms_posts.create OR cms_posts.edit
 */
final class PermissionMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        foreach ($params as $param) {
            $required = str_contains($param, '.')
                ? [$param]
                : self::requiredFor($param, $request->method(), $request->path(), (string) $request->query('export', ''));

            foreach ($required as $permission) {
                if (Auth::can($permission)) {
                    return $next($request);
                }
            }
        }

        throw new HttpException(403);
    }

    /**
     * @return string[] permission slugs, any one of which allows the request
     */
    public static function requiredFor(string $module, string $method, string $path, string $export = ''): array
    {
        $path = rtrim($path, '/');

        if ($method === 'GET' || $method === 'HEAD') {
            if ($export === 'csv') {
                return [$module . '.export'];
            }
            if (str_ends_with($path, '/create')) {
                return [$module . '.create'];
            }
            if (str_ends_with($path, '/edit')) {
                return [$module . '.edit'];
            }

            return [$module . '.view'];
        }

        if (str_ends_with($path, '/delete')) {
            return [$module . '.delete'];
        }
        if (str_ends_with($path, '/convert')) {
            return [$module . '.convert', $module . '.edit'];
        }

        return [$module . '.create', $module . '.edit'];
    }
}
