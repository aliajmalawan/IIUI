<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Wraps $_GET/$_POST/$_FILES/$_SERVER — nothing outside Core touches the
 * superglobals directly (docs/06-standards.md §1). Supports method spoofing
 * via a `_method` hidden field for PUT/PATCH/DELETE from HTML forms.
 */
final class Request
{
    /** @var array<string,string> route parameters bound by the Router */
    private array $parameters = [];

    public function __construct(
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly array $cookies = []
    ) {
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_FILES, $_SERVER, $_COOKIE);
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));

        if ($method === 'POST' && isset($this->post['_method'])) {
            $spoofed = strtoupper((string) $this->post['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        return $method;
    }

    /**
     * The request path with the app's own base path stripped, so routes are
     * defined relative to the site root regardless of whether it's served
     * from a vhost or from a subdirectory under XAMPP's htdocs.
     */
    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');

        $base = $this->basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function basePath(): string
    {
        $scriptDir = str_replace('\\', '/', dirname((string) ($this->server['SCRIPT_NAME'] ?? '')));

        // SCRIPT_NAME resolves to .../public/index.php once the .htaccess
        // rewrite hands off; strip the trailing /public so routes never
        // need to know about it either.
        return (string) preg_replace('#/public$#', '', $scriptDir);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $this->server[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        $accept = (string) $this->header('Accept', '');

        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = (string) $this->header('Authorization', '');

        return str_starts_with($header, 'Bearer ') ? substr($header, 7) : null;
    }
}
