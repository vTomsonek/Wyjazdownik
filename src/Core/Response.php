<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal response helpers. Static-only - not constructed.
 */
final class Response
{
    private function __construct()
    {
    }

    public static function html(string $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $body;
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $location, int $status = 302): never
    {
        header('Location: ' . $location, true, $status);
        exit;
    }

    public static function notFound(string $message = 'Nie znaleziono'): never
    {
        $body = view('errors/404', ['title' => '404 - ' . $message]);
        self::html($body, 404);
    }

    public static function serverError(string $message = 'Błąd serwera'): never
    {
        $body = view('errors/500', ['title' => '500 - ' . $message]);
        self::html($body, 500);
    }
}
