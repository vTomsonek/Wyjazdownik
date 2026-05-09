<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base controller. Provides view rendering, redirects, and JSON helpers.
 */
abstract class Controller
{
    /**
     * Render a view (within a layout) and send it as HTML response.
     */
    protected function render(string $view, array $data = [], ?string $layout = 'app'): never
    {
        Response::html(view($view, $data, $layout));
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): never
    {
        Response::redirect($url, $status);
    }

    protected function notFound(string $message = 'Nie znaleziono'): never
    {
        Response::notFound($message);
    }
}
