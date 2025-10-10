<?php

declare(strict_types=1);

namespace Capsule\Contracts;

use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

/**
 * Middleware single-pass.
 *
 * Contrats :
 * - Appeler $next($request) au plus une fois.
 * - Retourner une Response (toujours).
 *
 * @param callable(Request):Response $next
 */
interface MiddlewareInterface
{
    public function process(Request $req, HandlerInterface $next): Response;
}
