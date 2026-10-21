<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal PSR-4-style autoloader.
 *
 * No Composer is installed in this environment (checked during setup), so
 * this class stands in for vendor/autoload.php for the App\ namespace. It is
 * loaded with a plain `require` in public/index.php before anything else can
 * run, since it cannot autoload itself.
 *
 * Third-party libraries added later (PHPMailer, etc.) either ship their own
 * loader or are required manually — this class only ever resolves App\*.
 */
final class Autoloader
{
    /** @var array<string,string> namespace prefix => base directory */
    private static array $prefixes = [];

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix  = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;

        self::$prefixes[$prefix] = $baseDir;
    }

    public static function load(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
}
