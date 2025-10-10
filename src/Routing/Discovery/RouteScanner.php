<?php

declare(strict_types=1);

namespace Capsule\Routing\Discovery;

use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Routing\Compiler\RouteCompiler;
use Capsule\Routing\Compiler\CompiledRoute;
use Capsule\Routing\RouterHandler;
use ReflectionClass;
use ReflectionMethod;

/**
 * Scanner de Routes par Attributs
 *
 * Découvre automatiquement les routes définies via les attributs PHP 8+
 * dans les contrôleurs et les enregistre dans le router.
 *
 * @package Capsule\Routing\Discovery
 * @final
 */
final class RouteScanner
{
    /**
     * Enregistre toutes les routes découvertes via attributs
     *
     * Scan les contrôleurs fournis, détecte les attributs #[Route] et #[RoutePrefix],
     * compile les routes et les ajoute au router.
     *
     * @param list<class-string> $controllerClasses Liste des classes de contrôleurs à scanner
     * @param RouterHandler $router Instance du router où enregistrer les routes
     * @return void
     */
    public static function register(array $controllerClasses, RouterHandler $router): void
    {
        foreach ($controllerClasses as $class) {
            $rc = new ReflectionClass($class);

            // Récupération du préfixe de route si défini
            $prefix = '';
            $prefAttr = $rc->getAttributes(RoutePrefix::class)[0] ?? null;
            if ($prefAttr) {
                $prefix = rtrim($prefAttr->newInstance()->prefix, '/');
            }

            // Scan de toutes les méthodes publiques
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
                foreach ($m->getAttributes(Route::class) as $attr) {
                    /** @var Route $r */
                    $r = $attr->newInstance();

                    // Construction du chemin complet
                    $path = $prefix . '/' . ltrim($r->path, '/');

                    // Compilation de la route en regex
                    $compiled = RouteCompiler::compile($path);

                    // Enregistrement de la route compilée
                    $router->add(new CompiledRoute(
                        regex: $compiled['regex'],
                        variables: $compiled['vars'],
                        methods: array_values(array_unique(array_map('strtoupper', $r->methods))),
                        controllerClass: $class,
                        controllerMethod: $m->getName(),
                        middlewares: $r->middlewares,
                        name: $r->name
                    ));
                }
            }
        }
    }
}
