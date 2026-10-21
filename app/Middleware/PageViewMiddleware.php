<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Logs one row per successful public-page request into `page_views`, feeding
 * the admin Analytics dashboard. Wrapped around the whole web route group
 * (routes/web.php) — never admin/auth — and only records once the response
 * is known to be a real page (skips 4xx/5xx, e.g. a 404 or a failed redirect
 * target). IP is stored one-way hashed (ip_hash), the same plain sha256
 * convention Session::fingerprint() already uses for its device fingerprint
 * — enough to count unique visitors without retaining anything that
 * identifies them.
 */
final class PageViewMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400 && $request->method() !== 'HEAD') {
            $this->record($request);
        }

        return $response;
    }

    private function record(Request $request): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO page_views (url, ip_hash, referrer, user_agent, viewed_at)
             VALUES (:url, :ip_hash, :referrer, :user_agent, NOW())'
        );
        $stmt->execute([
            'url'        => mb_substr($request->path(), 0, 255),
            'ip_hash'    => hash('sha256', $request->ip()),
            'referrer'   => $request->header('Referer') !== null ? mb_substr((string) $request->header('Referer'), 0, 255) : null,
            'user_agent' => mb_substr($request->userAgent(), 0, 255),
        ]);
    }
}
