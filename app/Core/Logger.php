<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Daily-rotated file logger, PSR-3-shaped levels. Personal data, passwords,
 * tokens, and CNICs must never be passed into $context — see docs/05-security.md §9.
 */
final class Logger
{
    private const LEVELS = ['debug', 'info', 'warning', 'error', 'critical'];

    public static function log(string $level, string $message, array $context = []): void
    {
        $level = in_array($level, self::LEVELS, true) ? $level : 'info';

        $line = sprintf(
            '[%s] %s: %s%s' . PHP_EOL,
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES)
        );

        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        error_log($line, 3, $file);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }
}
