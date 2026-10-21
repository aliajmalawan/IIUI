<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller. Deliberately thin — validate, call one service, return a
 * response (docs/01-architecture.md §1). A controller that queries the
 * database or contains business rules is an architecture violation.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'web'): Response
    {
        return Response::view($view, $data, $layout);
    }

    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }

    protected function redirectRoute(string $name, array $params = []): Response
    {
        return Response::redirect(route($name, $params));
    }

    protected function json(array $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? base_url();

        return Response::redirect($referer);
    }
}
