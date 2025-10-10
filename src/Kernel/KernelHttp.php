<?php

declare(strict_types=1);

namespace Capsule\Kernel;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

/**
 * Kernel — minimal.
 *
 * Rôle : composer une pile de middlewares autour d'un handler final ($last).
 *
 * Invariants :
 * - Ordre d’exécution = ordre fourni : M1 → M2 → ... → Mn → $last.
 * - Un middleware peut court-circuiter en retournant sa propre Response.
 * - Aucune I/O ici (pas de header(), pas d’emit) — orchestration pure.
 * - $last est appelé si aucun middleware ne court-circuite.
 */
final class KernelHttp implements HandlerInterface
{
    /** @var list<MiddlewareInterface> */
    private array $middlewares;

    private HandlerInterface $pipeline;

    /**
     * @param list<MiddlewareInterface> $middlewares
     */
    public function __construct(array $middlewares, private readonly HandlerInterface $last)
    {
        // Fail-fast : on vérifie le contrat
        foreach ($middlewares as $i => $mw) {
            if (!$mw instanceof MiddlewareInterface) {
                throw new \InvalidArgumentException("Middleware #$i must implement MiddlewareInterface");
            }
        }

        // Normalisation + construction de la chaîne
        $this->middlewares = array_values($middlewares);
        $this->pipeline = self::buildPipeline($this->middlewares, $this->last);
    }

    public function handle(Request $req): Response
    {
        // O(k) appels imbriqués (k = nb de middlewares)
        return $this->pipeline->handle($req);
    }

    /**
     * @param list<MiddlewareInterface> $middlewares
     */
    private static function buildPipeline(array $middlewares, HandlerInterface $last): HandlerInterface
    {
        // Assemble LIFO : le dernier middleware enveloppe $last
        $handler = $last;

        for ($i = \count($middlewares) - 1; $i >= 0; $i--) {
            $mw = $middlewares[$i];

            // Nœud d’adaptation : délègue mw->process($req, $next)
            $handler = new class ($mw, $handler) implements HandlerInterface {
                public function __construct(
                    private readonly MiddlewareInterface $mw,
                    private readonly HandlerInterface $next
                ) {
                }

                public function handle(Request $r): Response
                {
                    return $this->mw->process($r, $this->next);
                }
            };
        }

        return $handler;
    }
}
