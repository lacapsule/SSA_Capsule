<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Domain\Repository\PartnerSectionRepository;

/**
 * Vérifie que les tables critiques existent avant d'exécuter la pile.
 * En production, renvoie une page 503 neutre si la base n'est pas prête.
 */
final class HealthCheckMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PartnerSectionRepository $sections,
        private ResponseFactoryInterface $responses,
        private bool $dev = true,
    ) {
    }

    public function process(Request $request, HandlerInterface $next): Response
    {
        if (!$this->isDatabaseReady()) {
            if ($this->dev) {
                throw new \RuntimeException('Base de données incomplète : exécutez les migrations.');
            }

            return $this->responses->html(
                '<h1>Maintenance en cours</h1><p>Merci de revenir ultérieurement.</p>',
                503
            );
        }

        return $next->handle($request);
    }

    private function isDatabaseReady(): bool
    {
        try {
            $this->sections->findAllOrdered();
            return true;
        } catch (\PDOException $e) {
            // On ne masque que les erreurs "table inexistante".
            if (str_contains($e->getMessage(), 'no such table')
                || str_contains($e->getMessage(), 'Base table or view not found')) {
                return false;
            }

            throw $e;
        }
    }
}

