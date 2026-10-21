<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

/**
 * Contract for every entry in the middleware pipeline. Extra `$params`
 * arrive from route definitions like `->middleware('role:admin,principal')`.
 */
interface Middleware
{
    public function handle(Request $request, Closure $next, string ...$params): Response;
}
