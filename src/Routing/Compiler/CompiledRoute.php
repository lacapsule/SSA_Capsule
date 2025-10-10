<?php

declare(strict_types=1);

namespace Capsule\Routing\Compiler;

/**
 * CompiledRoute
 *
 * Rôle : description immuable d’une route **déjà compilée** :
 *  - regex prête à matcher
 *  - noms de variables capturées (ordre d’apparition)
 *  - méthodes HTTP autorisées
 *  - cible (classe + méthode de contrôleur)
 *  - middlewares spécifiques à la route (optionnels)
 *  - nom logique (optionnel)
 *
 * Invariants :
 *  - $methods : UPPERCASE, dé-dupliquées.
 *  - $regex : délimiteurs + ^…$ + /u (voir RouteCompiler).
 *  - Immuable : toutes les propriétés sont readonly.
 *
 * NOTE : Pas de logique de coercion ici (SRP). On extrait seulement des strings.
 */
final class CompiledRoute
{
    /**
     * @param list<string> $variables
     * @param list<string> $methods
     * @param list<class-string> $middlewares
     */
    public function __construct(
        public readonly string $regex,
        public readonly array $variables,
        public readonly array $methods,
        public readonly string $controllerClass,
        public readonly string $controllerMethod,
        public readonly array $middlewares = [],
        public readonly ?string $name = null,
    ) {
    }
}
