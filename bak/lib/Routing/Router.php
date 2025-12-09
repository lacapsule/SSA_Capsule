<?php

declare(strict_types=1);

namespace CapsuleLib\Routing;

use CapsuleLib\Middleware\MiddlewareRunner;

final class Router
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<int, array{prefix:string, mws:array<int, callable>}> */
    private array $groupStack = [];

    /** @var array<string, Route> */
    private array $named = [];

    /** @var callable():void */
    private $notFound = null;

    /* ---------- Enregistrement ---------- */

    public function get(string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->map('GET', $path, $handler, $middlewares, $name);
    }
    public function post(string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->map('POST', $path, $handler, $middlewares, $name);
    }
    public function put(string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->map('PUT', $path, $handler, $middlewares, $name);
    }
    public function delete(string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->map('DELETE', $path, $handler, $middlewares, $name);
    }
    public function patch(string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        $this->map('PATCH', $path, $handler, $middlewares, $name);
    }

    public function map(string $method, string $path, callable $handler, array $middlewares = [], ?string $name = null): void
    {
        [$prefixed, $mergedMws] = $this->applyGroupContext($path, $middlewares);
        $route = new Route($method, $prefixed, $handler, $mergedMws, $name);

        $this->routes[] = $route;
        if ($name) {
            $this->named[$name] = $route;
        }
    }

    /**
     * Groupes de routes avec préfixe et middlewares.
     * Usage:
     *   $router->group('/dashboard', [mw1, mw2], function (Router $r) { ... });
     */
    public function group(string $prefix, array $middlewares, callable $callback): void
    {
        $this->groupStack[] = ['prefix' => rtrim($prefix, '/'), 'mws' => $middlewares];
        $callback($this);
        array_pop($this->groupStack);
    }

    private function applyGroupContext(string $path, array $middlewares): array
    {
        $prefix = '';
        $merged = [];

        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'];
            $merged = array_merge($merged, $g['mws']);
        }

        $prefixed = rtrim($prefix, '/') . '/' . ltrim($path, '/');
        if ($prefixed === '') $prefixed = '/';
        $prefixed = '/' . ltrim($prefixed, '/');

        return [$prefixed, array_merge($merged, $middlewares)];
    }

    /* ---------- Dispatch ---------- */

    public function dispatch(?string $method = null, ?string $path = null): void
    {
        $method ??= $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $path ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = $uri ?: '/';

        foreach ($this->routes as $route) {
            if ($route->method !== $method) continue;

            if (preg_match($route->toRegex(), $uri, $m)) {
                // params nommés
                $params = [];
                foreach ($m as $k => $v) {
                    if (!is_int($k)) $params[$k] = $v;
                }
                // run middlewares + handler
                $callable = MiddlewareRunner::with($route->handler, $route->middlewares);
                $callable($params);
                return;
            }
        }

        if ($this->notFound) {
            ($this->notFound)();
            return;
        }
        http_response_code(404);
        echo '404 Not Found';
    }

    /* ---------- Divers ---------- */

    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFound = $handler;
    }

    /** Génération d’URL par nom: route('article.edit', ['id'=>12]) → /dashboard/articles/edit/12 */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new \RuntimeException("Route name not found: $name");
        }
        $pattern = $this->named[$name]->pathPattern;

        // Remplace {name:regex} et {name}
        $url = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\:([^}]+)\}|\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function ($m) use ($params) {
                $key = $m[1] ?: $m[3];
                if (!array_key_exists($key, $params)) {
                    throw new \RuntimeException("Missing param '$key' for route generation");
                }
                return rawurlencode((string)$params[$key]);
            },
            $pattern
        );

        return $url ?? $pattern;
    }
}
