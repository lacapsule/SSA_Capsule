<?php

declare(strict_types=1);

namespace CapsuleLib\Middleware;

final class MiddlewareRunner
{
    /**
     * Construit un pipeline de middlewares autour d'un handler.
     *
     * $middlewares est une liste de callables de forme:
     *   function (array $params, callable $next): void
     *
     * $handler final est de forme:
     *   function (array $params): void
     */
    public static function with(callable $handler, array $middlewares): callable
    {
        // $next initial appelle le handler final
        $next = function (array $params = []) use ($handler): void {
            $handler($params);
        };

        // On empile les middlewares en ordre inverse
        foreach (array_reverse($middlewares) as $mw) {
            $prevNext = $next;
            $next = function (array $params = []) use ($mw, $prevNext): void {
                // Chaque middleware reçoit (params, next)
                $mw($params, $prevNext);
            };
        }

        // La callable retournée par MiddlewareRunner::with est celle qu’appellera le Router
        return function (array $params = []) use ($next): void {
            $next($params);
        };
    }
}
