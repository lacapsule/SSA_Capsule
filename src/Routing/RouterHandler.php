<?php

declare(strict_types=1);

namespace Capsule\Routing;

use Capsule\Contracts\HandlerInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Compiler\CompiledRoute;
use Capsule\Routing\Dispatch\ControllerInvoker;
use Capsule\Routing\Exception\MethodNotAllowed;
use Capsule\Routing\Exception\NotFound;

/**
 * RouterHandler
 *
 * Rôle : recevoir une Request, trouver la route correspondante, et déléguer
 * à l’invocation contrôleur (via ControllerInvoker).
 *
 * Contrats :
 *  - 404 si aucune route ne matche le **chemin**.
 *  - 405 si le chemin matche mais **pas la méthode** (header Allow agrégé).
 *  - HEAD implicite si GET est autorisé.
 *  - Ordre de résolution : première route enregistrée qui autorise la méthode.
 *  - Extraction des variables : strings uniquement (coercion faite plus tard).
 *
 * Invariants :
 *  - Aucune I/O (pas d’emit, pas de header()).
 *  - $routes est évalué séquentiellement (O(N)).
 *  - middlewares “par route” gérés dans ControllerInvoker.
 */
final class RouterHandler implements HandlerInterface
{
    /** @var list<CompiledRoute> */
    private array $routes = [];

    public function add(CompiledRoute $r): void
    {
        $this->routes[] = $r;
    }

    public function handle(Request $req): Response
    {
        $path = $req->path;                // supposé normalisé par ta Request
        $method = strtoupper($req->method);  // sécurité : uppercase

        // 1) On collecte toutes les routes qui matchent le CHEMIN (regex).
        //    On conservera les méthodes autorisées pour composer 'Allow'.
        $matched = [];
        $firstParams = null;

        foreach ($this->routes as $r) {
            if (!preg_match($r->regex, $path, $m)) {
                continue;
            }
            $firstParams ??= $this->extractParams($r, $m);
            $matched[] = $r;
        }

        if (!$matched) {
            throw new NotFound('Route not found');
        }

        // 2) Agréger l’ensemble des méthodes autorisées sur ce chemin.
        $allowedMap = [];
        foreach ($matched as $r) {
            foreach ($r->methods as $m) {
                $allowedMap[strtoupper($m)] = true;
            }
        }

        // HEAD implicite si GET
        if (isset($allowedMap['GET'])) {
            $allowedMap['HEAD'] = true;
        }

        $allowed = array_keys($allowedMap);

        // 3) Méthode non autorisée → 405
        if (!isset($allowedMap[$method])) {
            // Laissez l’ErrorBoundary fabriquer la Response 405 + header Allow
            throw new MethodNotAllowed($allowed);
        }

        // 4) Sélectionner la première route qui autorise la méthode
        foreach ($matched as $r) {
            $ok = in_array($method, $r->methods, true)
                || ($method === 'HEAD' && in_array('GET', $r->methods, true));

            if ($ok) {
                // Délégation à l’Invoker pour exécuter le contrôleur + MW de route.
                return ControllerInvoker::invoke($r, $req, $firstParams ?? []);
            }
        }

        // Par construction, on ne devrait pas arriver ici.
        throw new MethodNotAllowed($allowed);
    }

    /**
     * @param array<string,int|string> $match preg_match() result
     * @return array<string,string> params extraits
     */
    private function extractParams(CompiledRoute $r, array $match): array
    {
        $vars = [];
        foreach ($r->variables as $v) {
            $vars[$v] = (string)($match[$v] ?? '');
        }

        return $vars;
    }
}
