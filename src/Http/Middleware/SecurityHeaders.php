<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

final class SecurityHeaders implements MiddlewareInterface
{
    public function __construct(
        private readonly bool $dev = true,   // <-- passe à false en prod
        private readonly bool $https = false // <-- true si tu es derrière HTTPS (pour HSTS)
    ) {
    }

    public function process(Request $request, HandlerInterface $next): Response
    {
        $res = $next->handle($request);

        $csp = $this->dev
            // DEV — permissif (pas d’inline JS, mais styles inline tolérés au besoin)
            ? "default-src 'self'; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; "
            . "img-src 'self' data:; "
            . "font-src 'self' data:; "
            . "connect-src 'self'; "
            . "form-action 'self'; "
            . "base-uri 'self'; "
            . "frame-ancestors 'none'"
            // PROD — plus strict (pas d'inline CSS/JS)
            : "default-src 'self'; "
            . "style-src 'self'; "
            . "script-src 'self'; "
            . "img-src 'self' data:; "
            . "font-src 'self' data:; "
            . "connect-src 'self'; "
            . "form-action 'self'; "
            . "base-uri 'self'; "
            . "frame-ancestors 'none'";

        // Idempotent: ne remplace pas si déjà défini par un proxy/serveur
        if (!$res->hasHeader('Content-Security-Policy')) {
            $res = $res->withHeader('Content-Security-Policy', $csp);
        }

        if (!$res->hasHeader('X-Content-Type-Options')) {
            $res = $res->withHeader('X-Content-Type-Options', 'nosniff');
        }
        if (!$res->hasHeader('Referrer-Policy')) {
            $res = $res->withHeader('Referrer-Policy', 'no-referrer');
        }
        if (!$res->hasHeader('X-Frame-Options')) {
            $res = $res->withHeader('X-Frame-Options', 'DENY');
        }

        if (!$this->dev && $this->https && !$res->hasHeader('Strict-Transport-Security')) {
            $res = $res->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $res;
    }
}
