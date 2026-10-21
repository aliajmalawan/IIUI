<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Exceptions\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use Closure;

/**
 * Generic rate limiter backed by the `cache` table (file/Redis-swappable
 * later — docs/01-architecture.md §5). Bucket name and limits come from
 * config('security.throttle.{bucket}'). Bucketed by submitted email/
 * identifier when present (closer to "3/hour per email" in
 * docs/05-security.md §10), falling back to the requester's IP.
 *
 * Login itself is NOT routed through here — see the note in
 * app/Config/security.php: it uses the dedicated login_attempts table
 * instead, which also drives per-account lockout.
 */
final class ThrottleMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response
    {
        $bucket = $params[0] ?? 'default';
        $limits = (array) config("security.throttle.{$bucket}", ['max_attempts' => 60, 'decay_minutes' => 1]);

        $identifier = $request->input('email') ?? $request->input('identifier') ?? $request->ip();
        $key = "throttle:{$bucket}:" . (string) $identifier;

        $pdo = Database::connection();
        $now = time();

        $select = $pdo->prepare('SELECT value, expires_at FROM cache WHERE key_name = :k');
        $select->execute(['k' => $key]);
        $row = $select->fetch();

        $count = 0;
        $expiresAt = $now + ((int) $limits['decay_minutes'] * 60);

        if ($row && (int) $row['expires_at'] > $now) {
            $count = (int) $row['value'];
            $expiresAt = (int) $row['expires_at'];
        }

        if ($count >= (int) $limits['max_attempts']) {
            throw new HttpException(429, 'Too many attempts. Please try again in a few minutes.', [
                'Retry-After' => (string) max(1, $expiresAt - $now),
            ]);
        }

        // Native prepares reject reusing one named placeholder twice, so the
        // INSERT and UPDATE sides of the upsert each get their own markers.
        $upsert = $pdo->prepare(
            'INSERT INTO cache (key_name, value, expires_at) VALUES (:k, :v1, :e1)
             ON DUPLICATE KEY UPDATE value = :v2, expires_at = :e2'
        );
        $newCount = (string) ($count + 1);
        $upsert->execute(['k' => $key, 'v1' => $newCount, 'e1' => $expiresAt, 'v2' => $newCount, 'e2' => $expiresAt]);

        return $next($request);
    }
}
