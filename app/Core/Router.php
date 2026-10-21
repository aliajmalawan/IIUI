<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use Closure;
use RuntimeException;

/**
 * Route table + dispatcher. A static facade, consistent with the rest of
 * Core (Database, Session, View) — no container is built yet, so there is
 * nothing to inject a Router instance from.
 */
final class Router
{
    /** @var array<string,Route[]> */
    private static array $routes = [
        'GET' => [], 'POST' => [], 'PUT' => [], 'PATCH' => [], 'DELETE' => [],
    ];

    /** @var array<string,Route> */
    private static array $named = [];

    /** @var array<int,array{prefix?:string,middleware?:array}> */
    private static array $groupStack = [];

    public static function get(string $uri, array $action): Route
    {
        return self::add('GET', $uri, $action);
    }

    public static function post(string $uri, array $action): Route
    {
        return self::add('POST', $uri, $action);
    }

    public static function put(string $uri, array $action): Route
    {
        return self::add('PUT', $uri, $action);
    }

    public static function patch(string $uri, array $action): Route
    {
        return self::add('PATCH', $uri, $action);
    }

    public static function delete(string $uri, array $action): Route
    {
        return self::add('DELETE', $uri, $action);
    }

    public static function group(array $attributes, Closure $callback): void
    {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    private static function add(string $method, string $uri, array $action): Route
    {
        $prefixParts = [];
        $middleware = [];

        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefixParts[] = trim((string) $group['prefix'], '/');
            }
            $middleware = array_merge($middleware, (array) ($group['middleware'] ?? []));
        }

        $prefix = implode('/', $prefixParts);
        $fullUri = '/' . trim($prefix . '/' . ltrim($uri, '/'), '/');

        $route = new Route($method, $fullUri, $action, $middleware);
        self::$routes[$method][] = $route;

        return $route;
    }

    public static function registerName(string $name, Route $route): void
    {
        self::$named[$name] = $route;
    }

    public static function url(string $name, array $params = []): string
    {
        if (!isset(self::$named[$name])) {
            throw new RuntimeException("Route [{$name}] is not defined.");
        }

        return self::$named[$name]->buildUrl($params);
    }

    public static function dispatch(Request $request): Response
    {
        $method = $request->method();

        // /about/ and /about are the same page — send crawlers and people to one URL.
        $rawPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/');
        if (in_array($method, ['GET', 'HEAD'], true) && $request->path() !== '/' && strlen($rawPath) > 1 && str_ends_with($rawPath, '/')) {
            $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

            return Response::redirect(rtrim($rawPath, '/') . ($query !== '' ? '?' . $query : ''), 301);
        }

        // HEAD is GET without a body (the web server drops the body); uptime
        // monitors and crawlers use it, so it must resolve to the same routes.
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach (self::$routes[$method] ?? [] as $route) {
            if ($route->matches($request->path())) {
                $request->setParameters($route->parameters($request->path()));

                return Pipeline::send($request, $route->getMiddleware(), function (Request $req) use ($route) {
                    return self::runAction($route->getAction(), $req);
                });
            }
        }

        throw new HttpException(404);
    }

    private static function runAction(array $action, Request $request): Response
    {
        [$class, $method] = $action;
        $controller = new $class();
        $result = $controller->$method($request);

        return $result instanceof Response ? $result : Response::make((string) $result);
    }
}
