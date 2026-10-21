<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Plain-PHP template renderer with layout inheritance via section capture.
 * No compiled template language — a view is just a PHP file that trusts
 * e() for escaping (see app/Helpers/functions.php).
 */
final class View
{
    /** @var array<string,string> */
    private static array $sections = [];
    private static ?string $currentSection = null;

    /** @var array<string,mixed> */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Renders $view (dot notation, e.g. "web.welcome" → Views/web/welcome.php).
     * If $layout is given, the view's output becomes the "content" section
     * inside Views/layouts/{$layout}.php.
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $data = array_merge(self::$shared, $data);

        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        $content = self::capture($viewFile, $data);

        if ($layout === null) {
            return $content;
        }

        $layoutFile = VIEWS_PATH . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }

        self::$sections['content'] = $content;

        return self::capture($layoutFile, $data);
    }

    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /** Starts capturing a named section — call View::endSection() to close it. */
    public static function section(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection !== null) {
            self::$sections[self::$currentSection] = (string) ob_get_clean();
            self::$currentSection = null;
        }
    }

    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }
}
