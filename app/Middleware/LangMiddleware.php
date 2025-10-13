<?php

declare(strict_types=1);

namespace App\Middleware;

use Capsule\Contracts\MiddlewareInterface;
use Capsule\Contracts\HandlerInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

/**
 * LangMiddleware — détection & préparation i18n.
 *
 * Invariants :
 * - Langue ∈ whitelist ; fallback $default.
 * - Persistance en session + (option) cookie.
 * - Pré-chargement des chaînes pour le reste de la requête.
 */
final class LangMiddleware implements MiddlewareInterface
{
    private string $default;

    public function __construct(string $default = 'fr')
    {
        $this->default = $default;
    }

    public function process(Request $request, HandlerInterface $next): Response
    {

        // Une seule ligne : charge et déclenche la détection via Translate
        \App\Lang\TranslationLoader::load($this->default);

        return $next->handle($request);
    }
}
