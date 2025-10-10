<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

/**
 * Middleware de débogage des en-têtes HTTP.
 *
 * Fournit un endpoint de débogage pour inspecter les en-têtes de requête
 * vus par PHP. Utile pour le développement et le diagnostic.
 *
 * @final
 */
final class DebugHeaders implements MiddlewareInterface
{
    /**
     * Constructeur du middleware de débogage.
     *
     * @param ResponseFactoryInterface $res Factory pour créer les réponses JSON
     * @param bool $enabled Active ou désactive le middleware (défaut: false)
     */
    public function __construct(
        private readonly ResponseFactoryInterface $res,
        private readonly bool $enabled = false
    ) {
    }

    /**
     * Traite la requête et fournit les informations de débogage si activé.
     *
     * @param Request $request Requête HTTP entrante
     * @param HandlerInterface $next Gestionnaire suivant dans le pipeline
     * @return Response Réponse HTTP avec informations de débogage ou continuation
     */
    public function process(Request $request, HandlerInterface $next): Response
    {
        if ($this->enabled && $request->path === '/__debug/headers') {
            // Utilise les superglobals pour voir ce que PHP voit
            // (Apache/Nginx/php-fpm peuvent déjà injecter des headers)
            $headers = [];
            foreach ($_SERVER as $k => $v) {
                if (str_starts_with($k, 'HTTP_')) {
                    $name = strtolower(str_replace('_', '-', substr($k, 5)));
                    $headers[$name] = $v;
                }
            }

            // Renvoie aussi ce que ta stack mettrait normalement (utile quand tu ajoutes SecurityHeaders plus loin).
            return $this->res->json([
                'server_vars' => $headers,
                'note' => 'Ce sont les headers de la requête vue côté PHP. 
                Les headers de réponse sont définis par tes middlewares "SecurityHeaders"/autres.',
            ], 200);
        }

        return $next->handle($request);
    }
}
