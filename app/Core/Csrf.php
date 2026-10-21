<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token issue/verify — docs/05-security.md §4. One token per session,
 * compared with hash_equals() to avoid timing attacks.
 */
final class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) Session::get('_csrf_token');
    }

    public static function verify(?string $token): bool
    {
        $stored = Session::get('_csrf_token');

        return is_string($stored) && is_string($token) && $token !== '' && hash_equals($stored, $token);
    }
}
