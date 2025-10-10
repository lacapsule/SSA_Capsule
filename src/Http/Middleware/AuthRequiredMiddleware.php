<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\SessionReader;
use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Contracts\ResponseFactoryInterface;

/**
 * Middleware d'authentification par rôle.
 *
 * Vérifie que l'utilisateur a le rôle requis pour accéder aux routes protégées.
 * Redirige vers la page de connexion si l'utilisateur n'est pas authentifié
 * ou n'a pas le rôle requis.
 *
 * @final
 */
final class AuthRequiredMiddleware implements MiddlewareInterface
{
    /**
     * Constructeur du middleware d'authentification.
     *
     * @param SessionReader $session Lecteur de session pour vérifier l'authentification
     * @param ResponseFactoryInterface $res Factory pour créer les réponses de redirection
     * @param string $requiredRole Rôle requis pour accéder aux routes protégées
     * @param string $protectedPrefix Préfixe des routes à protéger (défaut: '/dashboard')
     * @param list<string> $whitelist Liste des routes exemptées de la protection
     * @param string $redirectTo URL de redirection en cas d'échec d'authentification
     * @param string $sessionKey Clé de session pour les données utilisateur
     * @param string $roleKey Clé dans les données utilisateur pour le rôle
     */
    public function __construct(
        private readonly SessionReader $session,
        private readonly ResponseFactoryInterface $res,
        private readonly string $requiredRole,
        private readonly string $protectedPrefix = '/dashboard',
        /** @var list<string> */
        private readonly array $whitelist = ['/login','/logout'],
        private readonly string $redirectTo = '/login',
        private readonly string $sessionKey = 'admin',
        private readonly string $roleKey = 'role',
    ) {
    }

    /**
     * Traite la requête et vérifie l'authentification.
     *
     * @param Request $request Requête HTTP entrante
     * @param HandlerInterface $next Gestionnaire suivant dans le pipeline
     * @return Response Réponse HTTP
     */
    public function process(Request $request, HandlerInterface $next): Response
    {
        $path = $request->path;

        if (!str_starts_with($path, $this->protectedPrefix)) {
            return $next->handle($request);
        }
        if (in_array($path, $this->whitelist, true)) {
            return $next->handle($request);
        }

        $user = $this->session->get($this->sessionKey);
        if (!$user || !\is_array($user) || ($user[$this->roleKey] ?? null) !== $this->requiredRole) {
            // Redirection header-only
            return $this->res->redirect($this->redirectTo, 302);
        }

        return $next->handle($request);
    }
}
