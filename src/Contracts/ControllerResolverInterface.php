<?php

declare(strict_types=1);

namespace Capsule\Contracts;

/**
 * Résout un descripteur de contrôleur en callable(Request):Response.
 *
 * Exemples supportés (selon implémentation) :
 * - callable direct (fn(Request): Response)
 * - ['App\Controller\HomeController', 'index']
 * - 'App\Controller\HomeController::index'
 *
 * @param callable|array{0:class-string,1:string}|string $desc
 * @return callable(Request):Response
 *
 * @throws \InvalidArgumentException si non résoluble
 */
interface ControllerResolverInterface
{
    /**
     * @param callable(): mixed|mixed[]|string $desc
     */
    public function resolve(callable|array|string $desc): callable;
}
