<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Simple regex-based router.
 *
 * Routes are registered with method + pattern + handler:
 *   $router->get('/', [HomeController::class, 'index']);
 *   $router->get('/p/{token}', [ParticipantController::class, 'show']);
 *
 * Pattern placeholders:
 *   {name}         matches [^/]+
 *   {name:slug}    matches [a-z0-9-]+
 *   {name:hex}     matches [a-f0-9]+
 *   {name:int}     matches \d+
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,regex:string,params:list<string>,handler:array|Closure,middleware:list<callable>}> */
    private array $routes = [];

    /** @var list<callable> */
    private array $globalMiddleware = [];

    public function get(string $pattern, array|Closure $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array|Closure $handler, array $middleware = []): self
    {
        return $this->add('POST', $pattern, $handler, $middleware);
    }

    /**
     * Register middleware that runs for every dispatched request.
     */
    public function middleware(callable $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function add(string $method, string $pattern, array|Closure $handler, array $middleware = []): self
    {
        [$regex, $params] = $this->compile($pattern);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'regex'      => $regex,
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }

            $args = [];
            foreach ($route['params'] as $name) {
                $args[$name] = $matches[$name] ?? null;
            }

            // Run global middleware then route-specific middleware.
            $stack = [...$this->globalMiddleware, ...$route['middleware']];
            foreach ($stack as $middleware) {
                $middleware($request, $args);
            }

            $this->invoke($route['handler'], $request, $args);
            return;
        }

        Response::notFound();
    }

    /**
     * @param array{0:class-string,1:string}|Closure $handler
     * @param array<string,?string> $args
     */
    private function invoke(array|Closure $handler, Request $request, array $args): void
    {
        if ($handler instanceof Closure) {
            $handler($request, $args);
            return;
        }
        [$class, $method] = $handler;
        if (!class_exists($class)) {
            throw new RuntimeException("Controller class not found: {$class}");
        }
        $controller = new $class();
        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Action not found: {$class}::{$method}");
        }
        $controller->$method($request, $args);
    }

    /**
     * Compile a route pattern into a PCRE regex and capture-name list.
     * @return array{0:string,1:list<string>}
     */
    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback(
            '#\{(\w+)(?::(\w+))?\}#',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                $type = $m[2] ?? '';
                $charClass = match ($type) {
                    'int'   => '\d+',
                    'hex'   => '[a-f0-9]+',
                    'slug'  => '[a-z0-9-]+',
                    default => '[^/]+',
                };
                return '(?P<' . $m[1] . '>' . $charClass . ')';
            },
            $pattern
        );
        return ['#^' . $regex . '$#', $params];
    }
}
