<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Read-side authentication facade: user(), check(), hasRole(), can(). The
 * actual login procedure (credential verification, lockout, audit) lives in
 * AuthService — this class only ever answers "who is logged in and what may
 * they do", exactly as docs/01-architecture.md §3 specifies for Core/Auth.php.
 */
final class Auth
{
    private static ?array $userCache = null;
    private static bool $resolved = false;

    /** Called by AuthService once credentials are verified. */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('_auth_user_id', (int) $user['id']);
        self::$userCache = $user;
        self::$resolved = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$userCache = null;
        self::$resolved = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user !== null ? (int) $user['id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$userCache;
        }

        self::$resolved = true;

        $id = Session::get('_auth_user_id');
        if ($id === null) {
            return self::$userCache = null;
        }

        return self::$userCache = (new UserRepository())->findActiveById((int) $id);
    }

    public static function hasRole(string $slug): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return (new UserRepository())->userHasRole((int) $user['id'], $slug);
    }

    /** @var array<string,true>|null */
    private static ?array $permissionSet = null;

    private static ?bool $superAdmin = null;

    /** Lowest role level that counts as staff (students and parents are 20). */
    private const STAFF_MIN_LEVEL = 30;

    public static function can(string $permission): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        // One query per request for the whole permission set — menus and middleware ask many times.
        if (self::$permissionSet === null) {
            $repo = new UserRepository();
            self::$superAdmin = $repo->userHasRole((int) $user['id'], 'super_admin');
            self::$permissionSet = array_fill_keys($repo->permissionSlugs((int) $user['id']), true);
        }

        return self::$superAdmin === true || isset(self::$permissionSet[$permission]);
    }

    /** Whether this account may use the staff console at all (logged-in students/parents may not). */
    public static function canAccessAdmin(): bool
    {
        $user = self::user();

        return $user !== null
            && (new UserRepository())->maxRoleLevel((int) $user['id']) >= self::STAFF_MIN_LEVEL;
    }
}
