<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur minimal : chemins exacts ou à segments nommés (/fiche/{id}/), méthodes GET (HEAD inclus) et POST.
 * Chemin connu avec une autre méthode : 405 et en-tête Allow (RFC 9110, 15.5.6). Chemin inconnu : notFound, sinon 404.
 */
final class Router
{
    /** @var array<string, array<string, array{regex: string, handler: callable}>> */
    private array $routes = [];

    /** Page introuvable personnalisée. @var (callable(): void)|null */
    public $notFound = null;

    /** Page d'erreur au statut donné (405, et 404 sans notFound). @var (callable(int): void)|null */
    public $error = null;

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = ['regex' => self::compile($path), 'handler' => $handler];
    }

    /** Vrai si une route de cette méthode (GET par défaut) répond à ce chemin exact. */
    public function has(string $path, string $method = 'GET'): bool
    {
        foreach ($this->routes[strtoupper($method)] ?? [] as $route) {
            if (preg_match($route['regex'], $path) === 1) {
                return true;
            }
        }
        return false;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $lookup = $method === 'HEAD' ? 'GET' : $method;
        foreach ($this->routes[$lookup] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $m) === 1) {
                ($route['handler'])(...array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));
                return;
            }
        }
        $allowed = [];
        foreach ($this->routes as $verb => $routes) {
            foreach ($routes as $route) {
                if (preg_match($route['regex'], $path) === 1) {
                    $allowed[] = $verb;
                    if ($verb === 'GET') {
                        $allowed[] = 'HEAD';
                    }
                    break;
                }
            }
        }
        if ($allowed !== []) {
            header('Allow: ' . implode(', ', array_unique($allowed)));
            $this->error !== null ? ($this->error)(405) : http_response_code(405);
            return;
        }
        if ($this->notFound !== null) {
            ($this->notFound)();
            return;
        }
        $this->error !== null ? ($this->error)(404) : http_response_code(404);
    }

    /** Partie fixe échappée (un « . » ou un « + » du chemin reste littéral), segment {nom} : tout sauf « / ». */
    private static function compile(string $path): string
    {
        $parts = preg_split('/(\{\w+\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '';
        foreach ($parts as $part) {
            $regex .= preg_match('/^\{(\w+)\}$/', $part, $m) === 1 ? '(?P<' . $m[1] . '>[^/]+)' : preg_quote($part, '#');
        }
        return '#^' . $regex . '$#';
    }
}
