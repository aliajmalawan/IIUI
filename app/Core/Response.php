<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable-ish response value object: body, status, headers. Nothing
 * outside this class ever calls echo/header()/http_response_code() directly
 * for a normal request/response cycle (the global exception Handler is the
 * one documented exception, since it must work even if this class fails).
 */
final class Response
{
    /**
     * Apache/mod_php only recognizes status codes in its own built-in
     * reason-phrase table; a code it doesn't know (419 is not an official
     * IANA status) silently gets rewritten to 500. Sending an explicit
     * status line via header() — rather than relying on
     * http_response_code() — is what makes non-standard codes actually work.
     */
    private const REASON_PHRASES = [
        200 => 'OK', 201 => 'Created', 204 => 'No Content',
        301 => 'Moved Permanently', 302 => 'Found',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
        404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict',
        419 => 'Page Expired', 422 => 'Unprocessable Entity', 429 => 'Too Many Requests',
        500 => 'Internal Server Error', 503 => 'Service Unavailable',
    ];

    /** @var array<string,string> */
    private array $headers;

    private function __construct(
        private string $content,
        private int $statusCode = 200,
        array $headers = []
    ) {
        $this->headers = $headers;
    }

    public static function make(string $content = '', int $statusCode = 200, array $headers = []): self
    {
        return new self($content, $statusCode, $headers);
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function view(string $view, array $data = [], ?string $layout = 'web', int $statusCode = 200): self
    {
        return new self(View::render($view, $data, $layout), $statusCode);
    }

    /**
     * Streams a file that lives outside the webroot (docs/05-security.md §5
     * point 7) with a forced Content-Type/Content-Disposition, so it always
     * downloads rather than ever being rendered in the browser's origin.
     */
    public static function download(string $absolutePath, string $downloadName, string $mimeType): self
    {
        $content = (string) file_get_contents($absolutePath);
        $safeName = str_replace(['"', "\r", "\n"], '', $downloadName);

        return new self($content, 200, [
            'Content-Type'            => $mimeType,
            'Content-Disposition'     => 'attachment; filename="' . $safeName . '"',
            'Content-Length'          => (string) strlen($content),
            'X-Content-Type-Options'  => 'nosniff',
        ]);
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function reasonPhrase(int $statusCode): string
    {
        return self::REASON_PHRASES[$statusCode] ?? 'Status';
    }

    /**
     * Sent on every response unless a controller already set the same header.
     * The CSP is deliberately limited to directives that cannot break the site's
     * inline scripts/styles: it stops framing by other sites (clickjacking), <base>
     * hijacking, plugin embeds, and forms that post to another origin.
     */
    private static function defaultHeaders(): array
    {
        $headers = [
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Content-Security-Policy' => "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'",
        ];
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        if ($https) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        return $headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            self::sendStatusLine($this->statusCode);
            $present = array_change_key_case($this->headers, CASE_LOWER);
            foreach (self::defaultHeaders() as $name => $value) {
                if (!isset($present[strtolower($name)])) {
                    header("{$name}: {$value}");
                }
            }
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }

    public static function sendStatusLine(int $statusCode): void
    {
        header('HTTP/1.1 ' . $statusCode . ' ' . self::reasonPhrase($statusCode), true, $statusCode);
    }
}
