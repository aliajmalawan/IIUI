<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Chains route middleware around the final controller call. Middleware
 * specs are short aliases with optional CSV params, e.g. "role:admin,teacher"
 * — resolved against app/Config/middleware.php, falling back to treating the
 * spec as a fully-qualified class name if no alias matches.
 */
final class Pipeline
{
    public static function send(Request $request, array $middleware, Closure $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            fn (Closure $next, string $spec): Closure => self::wrap($spec, $next),
            $destination
        );

        return $pipeline($request);
    }

    private static function wrap(string $spec, Closure $next): Closure
    {
        [$name, $paramString] = array_pad(explode(':', $spec, 2), 2, null);
        $params = $paramString !== null ? explode(',', $paramString) : [];

        $aliases = (array) config('middleware');
        $class = $aliases[$name] ?? $name;

        return function (Request $request) use ($class, $params, $next): Response {
            /** @var Middleware $instance */
            $instance = new $class();

            return $instance->handle($request, $next, ...$params);
        };
    }
}
