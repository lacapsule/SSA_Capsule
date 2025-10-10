<?php

declare(strict_types=1);

namespace Capsule\Contracts;

use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

/**
 * Interface Handler - Gestionnaire de requêtes HTTP
 *
 * Définit le contrat pour tous les composants capables de traiter une requête HTTP
 * et de retourner une réponse. Utilisé par le kernel, les middlewares et le router.
 *
 * @package Capsule\Contracts
 */
interface HandlerInterface
{
    /**
     * Traite une requête HTTP et retourne une réponse
     *
     * @param Request $req Requête HTTP à traiter
     * @return Response Réponse HTTP générée
     */
    public function handle(Request $req): Response;
}
