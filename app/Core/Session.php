<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hardened session handler — docs/05-security.md §3.
 *
 * - Cookie params set before start(): HttpOnly, SameSite=Lax, Secure follows
 *   config (off for local HTTP dev, must be on in production over HTTPS).
 * - use_strict_mode + use_only_cookies reject attacker-supplied session IDs.
 * - Fingerprint = hash(User-Agent + IP /24 subnet). Full-IP binding is
 *   deliberately NOT used — it breaks mobile users switching cell towers.
 * - Idle timeout (default 30 min) is separate from the absolute cookie
 *   lifetime (default 2 h).
 * - Session files live in storage/sessions, never the shared OS temp dir.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $config = (array) config('security.session');

        session_name((string) $config['name']);

        session_set_cookie_params([
            'lifetime' => $config['lifetime'],
            'path'     => '/',
            'domain'   => '',
            // Configured value, but never left insecure when the request actually arrived over HTTPS.
            'secure'   => (bool) $config['cookie_secure']
                || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
            'httponly' => (bool) $config['cookie_httponly'],
            'samesite' => (string) $config['cookie_samesite'],
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $config['lifetime']);

        $savePath = STORAGE_PATH . '/sessions';
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0755, true);
        }
        session_save_path($savePath);

        session_start();
        self::$started = true;

        self::guardFingerprint();
        self::guardIdleTimeout((int) $config['idle_timeout']);
        self::ageFlashData();
    }

    private static function fingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $subnet = preg_replace('/\.\d+$/', '.0', $ip) ?? $ip;

        return hash('sha256', $ua . '|' . $subnet);
    }

    private static function guardFingerprint(): void
    {
        $current = self::fingerprint();

        if (!isset($_SESSION['_fingerprint'])) {
            $_SESSION['_fingerprint'] = $current;

            return;
        }

        if (!hash_equals($_SESSION['_fingerprint'], $current)) {
            self::destroy();
            session_start();
            $_SESSION['_fingerprint'] = $current;
        }
    }

    private static function guardIdleTimeout(int $seconds): void
    {
        $last = $_SESSION['_last_activity'] ?? null;

        if ($last !== null && (time() - (int) $last) > $seconds) {
            self::destroy();
            session_start();
        }

        $_SESSION['_last_activity'] = time();
    }

    /** Regenerates the session ID — call on login, logout, and privilege change. */
    public static function regenerate(bool $deleteOld = true): void
    {
        session_regenerate_id($deleteOld);
        $_SESSION['_fingerprint'] = self::fingerprint();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    // -------------------------------------------------------------------
    // Plain get/set/has/remove, dot-notation supported (mirrors config()).
    // -------------------------------------------------------------------

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::dataGet($_SESSION, $key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$_SESSION;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }
            $target = &$target[$segment];
        }

        $target[array_shift($segments)] = $value;
    }

    public static function has(string $key): bool
    {
        $missing = new \stdClass();

        return self::dataGet($_SESSION, $key, $missing) !== $missing;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // -------------------------------------------------------------------
    // Flash data: set now, readable only on the NEXT request.
    // -------------------------------------------------------------------

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_new'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_old'][$key] ?? $default;
    }

    private static function ageFlashData(): void
    {
        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }

    private static function dataGet(array $array, string $key, mixed $default): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }
}
