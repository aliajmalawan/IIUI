<?php

declare(strict_types=1);

/**
 * Global helper functions — the small, framework-wide vocabulary every
 * layer is allowed to reach for (views, controllers, services alike).
 * Nothing here touches $_POST/$_GET directly and nothing here queries the
 * database; nothing here belongs to a single module.
 */

use App\Core\Env;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

if (!function_exists('env')) {
    /** Reads a .env value with light type coercion (true/false/null/empty). */
    function env(string $key, mixed $default = null): mixed
    {
        $value = Env::get($key, $default);

        if (is_string($value)) {
            return match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'null'  => null,
                'empty' => '',
                default => $value,
            };
        }

        return $value;
    }
}

if (!function_exists('config')) {
    /** Reads app/Config/{file}.php with dot notation, e.g. config('app.debug'). */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!array_key_exists($file, $cache)) {
            $path = CONFIG_PATH . '/' . $file . '.php';
            $cache[$file] = is_file($path) ? require $path : [];
        }

        $value = $cache[$file];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('e')) {
    /** The one and only way user data reaches HTML output — docs/05-security.md §4. */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $rel = ltrim($path, '/');
        $file = PUBLIC_PATH . '/assets/' . $rel;
        $v = is_file($file) ? '?v=' . filemtime($file) : '';

        return base_url('assets/' . $rel) . $v;
    }
}

if (!function_exists('thumb_url')) {
    /**
     * URL of the small "-thumb" copy of an uploaded image when one exists
     * (Uploader makes them for card/grid images), else the original.
     * Use for grids and cards; use base_url() for full-size display.
     */
    function thumb_url(string $path): string
    {
        $rel = ltrim($path, '/');
        $thumb = (string) preg_replace('/(\.[A-Za-z0-9]+)$/', '-thumb$1', $rel);

        return base_url(is_file(PUBLIC_PATH . '/' . $thumb) ? $thumb : $rel);
    }
}

if (!function_exists('youtube_id')) {
    /** Extracts the 11-character video ID from a YouTube URL (or a bare ID); null if it isn't one. */
    function youtube_id(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }
        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/]|$)~i', $input, $m)) {
            return $m[1];
        }

        return null;
    }
}

if (!function_exists('gallery_item_thumb')) {
    /** Grid thumbnail for a gallery item: the -thumb copy for photos, YouTube's still for videos. */
    function gallery_item_thumb(array $item): string
    {
        if (($item['media_type'] ?? 'image') === 'video') {
            return 'https://i.ytimg.com/vi/' . rawurlencode((string) $item['video_id']) . '/hqdefault.jpg';
        }

        return thumb_url((string) $item['image_path']);
    }
}

if (!function_exists('srcset_url')) {
    /**
     * srcset value ("…-w640.jpg 640w, …-w1280.jpg 1280w, original 1919w") built from the
     * width variants Uploader::responsiveVariants() made; just the original if none exist.
     */
    function srcset_url(string $path, array $widths = [640, 1280]): string
    {
        $rel = ltrim($path, '/');
        $parts = [];
        foreach ($widths as $w) {
            $variant = (string) preg_replace('/(\.[A-Za-z0-9]+)$/', '-w' . $w . '$1', $rel);
            if (is_file(PUBLIC_PATH . '/' . $variant)) {
                $parts[] = base_url($variant) . ' ' . $w . 'w';
            }
        }
        $info = is_file(PUBLIC_PATH . '/' . $rel) ? @getimagesize(PUBLIC_PATH . '/' . $rel) : false;
        $parts[] = base_url($rel) . ($info !== false ? ' ' . $info[0] . 'w' : '');

        return implode(', ', $parts);
    }
}

if (!function_exists('slugify')) {
    /** URL-safe slug from a title — every content module (Gallery, News, Events, Pages…) needs this. */
    function slugify(string $text): string
    {
        $text = trim($text);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($transliterated) && $transliterated !== '') {
            $text = $transliterated;
        }
        $text = strtolower($text);
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        return $text !== '' ? $text : 'item-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}

if (!function_exists('resolve_url')) {
    /**
     * Admin-entered link fields (slider button_url, CTA button_url, ...)
     * can legitimately hold either a site-relative path ("/admission-form")
     * or a full external URL ("https://facebook.com/..."). Blindly prefixing
     * every value with base_url() would mangle external links; leaving
     * relative paths un-prefixed breaks them under the current subdirectory
     * deployment (base_url() isn't just "/"). This tells the two apart.
     */
    function resolve_url(string $value): string
    {
        if ($value === '') {
            return '#';
        }

        if (str_starts_with($value, 'mailto:') || str_starts_with($value, 'tel:')) {
            return $value;
        }

        // Matches "https://…", "http://…", and protocol-relative "//…".
        if (preg_match('#^([a-z][a-z0-9+.\-]*:)?//#i', $value) === 1) {
            return $value;
        }

        return base_url($value);
    }
}

if (!function_exists('icon')) {
    /** Renders a <use> reference into the self-hosted Bootstrap Icons sprite. */
    function icon(string $name, string $class = 'icon'): string
    {
        return '<svg class="' . e($class) . '" aria-hidden="true"><use href="'
            . e(asset('vendor/bootstrap-icons/icons.svg')) . '#bi-' . e($name) . '"></use></svg>';
    }
}

if (!function_exists('old')) {
    /** Re-populates a form field after a failed submission. Escaped by default. */
    function old(string $key, string $default = ''): string
    {
        $stored = Session::getFlash('_old_input', []);
        $value = is_array($stored) ? ($stored[$key] ?? null) : null;

        return e($value !== null ? (string) $value : $default);
    }
}

if (!function_exists('flash')) {
    /** Reads a one-time flash value set on the previous request. */
    function flash(string $key, mixed $default = null): mixed
    {
        return Session::getFlash($key, $default);
    }
}

if (!function_exists('errors')) {
    /** @return array<string,string[]> */
    function errors(): array
    {
        return (array) Session::getFlash('errors', []);
    }
}

if (!function_exists('error')) {
    function error(string $field): ?string
    {
        return errors()[$field][0] ?? null;
    }
}

if (!function_exists('has_error')) {
    function has_error(string $field): bool
    {
        return error($field) !== null;
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return base_url(ltrim(Router::url($name, $params), '/'));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (!Session::has('_csrf_token')) {
            Session::set('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) Session::get('_csrf_token');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], ?string $layout = 'web'): string
    {
        return View::render($name, $data, $layout);
    }
}

if (!function_exists('dd')) {
    /** Dump-and-die — local debugging only; a no-op page in production. */
    function dd(mixed ...$vars): never
    {
        if (!(bool) config('app.debug')) {
            http_response_code(500);
            exit('An error occurred.');
        }

        echo '<pre style="background:#0E1512;color:#E8EEEB;padding:16px;border-radius:8px;overflow:auto">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit;
    }
}
