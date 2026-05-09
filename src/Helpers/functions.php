<?php
declare(strict_types=1);

/**
 * Global helper functions.
 * Loaded by composer (files autoload) or by bootstrap.php fallback.
 */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        $lower = strtolower((string) $value);
        return match ($lower) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = null;
        if ($cache === null) {
            $cache = require BASE_PATH . '/config/config.php';
        }
        $segments = explode('.', $key);
        $value    = $cache;
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
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        if ($path === '') {
            return $base;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }
}

if (!function_exists('view')) {
    function view(string $path, array $vars = [], ?string $layout = 'app'): string
    {
        $viewFile = BASE_PATH . '/views/' . $path . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $path);
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }
        $layoutFile = BASE_PATH . '/views/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }
        $title = $vars['title'] ?? config('app.name', 'Wyjazdownik');
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        return null;
    }
}
