<?php

declare(strict_types=1);

namespace Capsule\Routing\Dispatch;

use Capsule\Contracts\ContainerLike;
use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Compiler\CompiledRoute;

/**
 * ControllerInvoker
 *
 * Rôle :
 *  - Instancier le contrôleur via le container,
 *  - Binder les arguments (SimpleArgumentBinder),
 *  - Exécuter la méthode de contrôleur,
 *  - Composer la mini-pipeline des middlewares "par route" (dans l'ordre défini).
 *
 * Politique d'erreur :
 *  - Échec de binding (param manquant / type invalide) → 400 JSON dès l'invocation.
 *    (Alternative possible : lancer une BadRequest et laisser l'ErrorBoundary mapper)
 *
 * Invariants :
 *  - Zéro I/O (pas de header()/echo).
 *  - Respecte l'ordre : middlewares globaux (dans Kernel) → middlewares route → contrôleur.
 */
final class ControllerInvoker
{
    private static ?ContainerLike $container = null;

    public static function setContainer(ContainerLike $c): void
    {
        self::$container = $c;
    }

    /**
     * @param array<string,string> $vars Path params (strings)
     */
    public static function invoke(CompiledRoute $r, Request $req, array $vars): Response
    {
        $c = self::$container ?? throw new \RuntimeException('Container not set on ControllerInvoker');

        // 1) Récupérer le contrôleur
        $controller = $c->get($r->controllerClass);
        $callable = [$controller, $r->controllerMethod];
        if (!\is_callable($callable)) {
            throw new \RuntimeException("Controller not callable: {$r->controllerClass}::{$r->controllerMethod}");
        }

        // 2) Adapter final : bind + invoke (sera en bout de pipeline)
        $final = new class ($callable, $vars, $c) implements HandlerInterface {
            /** @param array{0:object,1:string} $callable */
            /** @param array<string,string> $vars */
            public function __construct(
                private array $callable,
                private array $vars,
                private ContainerLike $c
            ) {
            }

            public function handle(Request $request): Response
            {
                [$obj, $method] = $this->callable;
                $rm = new \ReflectionMethod($obj, $method);

                try {
                    $args = SimpleArgumentBinder::bind($rm, $request, $this->vars, $this->c);
                } catch (\RuntimeException $e) {
                    /** @var \Capsule\Contracts\ResponseFactoryInterface $res */
                    $res = $this->c->get(\Capsule\Contracts\ResponseFactoryInterface::class);
                    $accept = (string)($request->headers['Accept'] ?? '');
                    $publicMsg = 'Bad request'; // ne pas exposer $e->getMessage()
                    if (str_contains($accept, 'application/json')) {
                        return $res->json(['error' => $publicMsg], 400);
                    }

                    return $res->text($publicMsg, 400);
                }

                /** @var Response $resp */
                $resp = $rm->invokeArgs($obj, $args);
                if (!$resp instanceof Response) {
                    throw new \RuntimeException('Controller must return a Response');
                }

                return $resp;
            }
        };

        // 3) Si pas de middlewares de route → exécuter directement
        if (!$r->middlewares) {
            return $final->handle($req);
        }

        // 4) Composer la mini-pipeline (LIFO)
        $handler = $final;
        for ($i = \count($r->middlewares) - 1; $i >= 0; $i--) {
            $mwClass = $r->middlewares[$i];

            /** @var object $mw */
            $mw = $c->get($mwClass);
            if (!$mw instanceof MiddlewareInterface) {
                throw new \RuntimeException("Route middleware must implement MiddlewareInterface: {$mwClass}");
            }

            $handler = new class ($mw, $handler) implements HandlerInterface {
                public function __construct(
                    private MiddlewareInterface $mw,
                    private HandlerInterface $next
                ) {
                }

                public function handle(Request $r): Response
                {
                    return $this->mw->process($r, $this->next);
                }
            };
        }

        return $handler->handle($req);
    }
}
