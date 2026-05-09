<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tiny wrapper around the global request state.
 * Used by controllers to read input without poking at $_GET/$_POST directly.
 */
final class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;
    /** @var array<string,mixed> */
    public readonly array $query;
    /** @var array<string,mixed> */
    public readonly array $body;
    /** @var array<string,array<string,mixed>> */
    public readonly array $files;
    /** @var array<string,string> */
    public readonly array $headers;

    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri     = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path    = $this->resolvePath();
        $this->query   = $_GET ?? [];
        $this->body    = $this->resolveBody();
        $this->files   = $_FILES ?? [];
        $this->headers = $this->resolveHeaders();
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        return strtolower($this->headers['x-requested-with'] ?? '') === 'xmlhttprequest';
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public function expectsJson(): bool
    {
        $accept = strtolower($this->headers['accept'] ?? '');
        return $this->isAjax() || str_contains($accept, 'application/json');
    }

    /**
     * Resolve the request path relative to the application's base path.
     * Strips the document-root prefix (e.g. /wyjazdownik/public) when present.
     */
    private function resolvePath(): string
    {
        $uri  = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $base = $this->basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if ($uri === '' || $uri === false) {
            return '/';
        }
        return '/' . trim($uri, '/');
    }

    private function basePath(): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        // /wyjazdownik/public/index.php  →  /wyjazdownik/public
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        return $base === '/' ? '' : $base;
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveBody(): array
    {
        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST ?? [];
    }

    /**
     * @return array<string,string>
     */
    private function resolveHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = (string) $_SERVER['CONTENT_LENGTH'];
        }
        return $headers;
    }
}
