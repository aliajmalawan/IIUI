<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use App\Core\Logger;
use App\Core\Response;
use ErrorException;
use Throwable;

/**
 * Global error/exception/shutdown handler — docs/05-security.md §9 and
 * docs/06-standards.md §1 ("never a bare catch, never @-suppression").
 *
 * Production (`app.debug` = false): every failure is logged with full detail
 * and the visitor sees only a generic, safe error page — never a stack trace.
 * Local (`app.debug` = true): the trace is rendered directly for speed of
 * development.
 */
final class Handler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0'); // we render our own pages below

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /** Converts classic PHP warnings/notices into catchable exceptions. */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false; // respects both excluded levels and @-suppressed calls
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleException(Throwable $e): void
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        Logger::log($statusCode >= 500 ? 'critical' : 'error', $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
        ]);

        if (!headers_sent()) {
            Response::sendStatusLine($statusCode);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Robots-Tag: noindex');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header("Content-Security-Policy: base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");
            header('Cache-Control: no-store');
            foreach (($e instanceof HttpException ? $e->getHeaders() : []) as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        self::render($statusCode, $e);
    }

    /** Fatal errors (parse errors, out-of-memory) never reach set_exception_handler. */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        Logger::critical($error['message'], ['file' => $error['file'], 'line' => $error['line']]);

        if (!headers_sent()) {
            Response::sendStatusLine(500);
        }

        self::render(500, null);
    }

    private static function render(int $statusCode, ?Throwable $e): void
    {
        // Stack traces expose file paths, SQL and config. Even with APP_DEBUG=true (e.g. a local
        // .env copied to the live server by mistake) they are shown to loopback requests only.
        $loopback = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
            && empty($_SERVER['HTTP_X_FORWARDED_FOR']);
        $debug = (bool) config('app.debug') && $loopback;

        if ($debug && $e !== null) {
            self::renderDebug($e, $statusCode);

            return;
        }

        $view = VIEWS_PATH . '/errors/' . $statusCode . '.php';
        if (!is_file($view)) {
            $view = VIEWS_PATH . '/errors/500.php';
        }

        if (is_file($view)) {
            require $view;
        } else {
            echo '<h1>Error ' . $statusCode . '</h1>';
        }
    }

    private static function renderDebug(Throwable $e, int $statusCode): void
    {
        echo '<!doctype html><html><head><meta charset="utf-8"><title>'
            . $statusCode . ' - ' . htmlspecialchars(get_class($e)) . '</title>';
        echo '<style>
            body{font-family:ui-monospace,Menlo,Consolas,monospace;background:#171717;color:#ECECEC;padding:32px;margin:0}
            h1{color:#F08C8C;font-size:20px;margin:0 0 8px}
            p{margin:0 0 12px}
            .file{color:#E57373;font-size:13px}
            pre{white-space:pre-wrap;background:#212121;padding:16px;border-radius:8px;font-size:13px;line-height:1.6}
        </style></head><body>';
        echo '<h1>' . $statusCode . ' &mdash; ' . htmlspecialchars(get_class($e)) . '</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p class="file">' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    }
}
