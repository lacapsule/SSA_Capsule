<?php

declare(strict_types=1);

namespace Capsule\Contracts;

use Capsule\Http\Message\Request;

/**
 * Router minimal : résout un handler pour une requête.
 *
 * Contrats :
 * - Retourne un callable de type callable(Request):Response.
 * - Peut lever HttpException 404 (route absente) ou 405 (méthode non autorisée).
 *
 * @return callable(Request):Response
 * @throws \Capsule\Http\Exception\HttpException
 */
interface RouterInterface
{
    public function match(Request $request): callable;
}
