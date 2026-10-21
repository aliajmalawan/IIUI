<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;

/**
 * An exception that carries an HTTP status code, so the global Handler can
 * render the matching error view (404, 403, 419, 422...) without a Router
 * having to catch it manually. The Router/middleware phase throws these.
 */
class HttpException extends RuntimeException
{
    /** @param array<string,string> $headers */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly array $headers = []
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    private static function defaultMessage(int $code): string
    {
        return match ($code) {
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for could not be found.',
            419 => 'Your session has expired. Please refresh and try again.',
            422 => 'The submitted data was invalid.',
            429 => 'Too many requests. Please slow down and try again shortly.',
            503 => 'The site is temporarily unavailable for maintenance.',
            default => 'An unexpected error occurred.',
        };
    }
}
