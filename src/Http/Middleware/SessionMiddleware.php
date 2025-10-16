<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Http\Support\SessionManager;

/**
 * SessionMiddleware - Démarre automatiquement la session
 *
 * Responsabilités :
 * - Garantir que la session est active pour toute requête
 * - Appeler SessionManager::start() de façon idempotente
 *
 * Position dans le pipeline :
 * - Après ErrorBoundary, SecurityHeaders
 * - Avant LangMiddleware, AuthRequiredMiddleware
 *
 * Avantages :
 * - Plus besoin de session_start() manuel
 * - FlashBag, FormState, Auth fonctionnent out-of-the-box
 * - Centralisation de la logique session
 */
final class SessionMiddleware implements MiddlewareInterface
{
    public function process(Request $req, HandlerInterface $next): Response
    {
        // Démarre la session (idempotent)
        SessionManager::start();

        // Traite la requête
        $response = $next->handle($req);

        // Optionnel : commit automatique pour libérer le verrou
        // SessionManager::commit();

        return $response;
    }
}
