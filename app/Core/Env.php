<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tiny .env file parser.
 *
 * Stands in for vlucas/phpdotenv, which normally arrives via Composer — not
 * available in this environment. Supports KEY=VALUE, "quoted" / 'quoted'
 * values, blank lines, and # comments. Nothing more elaborate is needed.
 */
final class Env
{
    /** @var array<string,string> */
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);

            if (strlen($value) > 1 && in_array($value[0], ['"', "'"], true) && $value[0] === substr($value, -1)) {
                $value = substr($value, 1, -1);
            }

            self::$variables[$name] = $value;

            if (getenv($name) === false) {
                putenv("{$name}={$value}");
            }
            $_ENV[$name] ??= $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}
