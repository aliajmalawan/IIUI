<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A single compiled route. Parameter constraints (`{id:\d+}`) turn a
 * malformed URL into a clean 404 before any controller code runs —
 * docs/04-routing-api.md §1.
 */
final class Route
{
    /** @var array<string,string> */
    private array $wheres = [];
    private ?string $name = null;

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $action,
        private array $middleware = []
    ) {
    }

    public function name(string $name): static
    {
        $this->name = $name;
        Router::registerName($name, $this);

        return $this;
    }

    public function where(string $param, string $pattern): static
    {
        $this->wheres[$param] = $pattern;

        return $this;
    }

    public function middleware(array|string $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getAction(): array
    {
        return $this->action;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function matches(string $path): bool
    {
        return (bool) preg_match($this->compile(), $path);
    }

    /** @return array<string,string> */
    public function parameters(string $path): array
    {
        preg_match($this->compile(), $path, $matches);

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /** Reverse-routing: substitutes {param} placeholders for route() calls. */
    public function buildUrl(array $params = []): string
    {
        $uri = $this->uri;

        foreach ($params as $key => $value) {
            $uri = (string) preg_replace(
                '#\{' . preg_quote((string) $key, '#') . '(:[^}]+)?\}#',
                (string) $value,
                $uri
            );
        }

        return $uri;
    }

    private function compile(): string
    {
        $pattern = preg_replace_callback('#\{(\w+)(:([^}]+))?\}#', function (array $m): string {
            $name = $m[1];
            $constraint = $this->wheres[$name] ?? ($m[3] ?? '[^/]+');

            return '(?P<' . $name . '>' . $constraint . ')';
        }, $this->uri);

        return '#^' . $pattern . '$#';
    }
}
