<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Argon2id password hashing — docs/05-security.md §2. Never MD5/SHA1, never
 * a hand-rolled "salt + hash" scheme.
 */
final class Hash
{
    private const OPTIONS = [
        'memory_cost' => 65536, // 64 MB
        'time_cost'   => 4,
        'threads'     => 2,
    ];

    public static function make(string $value): string
    {
        return password_hash($value, PASSWORD_ARGON2ID, self::OPTIONS);
    }

    public static function verify(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    /** Called after a successful login so hashes creep forward as cost parameters change. */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::OPTIONS);
    }
}
