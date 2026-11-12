<?php

declare(strict_types=1);

namespace Capsule\Http\Middleware;

use Capsule\Contracts\HandlerInterface;
use Capsule\Contracts\MiddlewareInterface;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Exception\HttpException;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Exception\MethodNotAllowed;
use Capsule\Routing\Exception\NotFound;

/**
 * Middleware de gestion centralisée des erreurs.
 *
 * Capture toutes les exceptions non attrapées et les transforme
 * en réponses JSON standardisées avec des informations de débogage.
 * Ajoute également un identifiant unique à chaque requête.
 *
 * @final
 */
final class ErrorBoundary implements MiddlewareInterface
{
    /**
     * Constructeur du middleware de gestion d'erreurs.
     *
     * @param ResponseFactoryInterface $res Factory pour créer les réponses d'erreur
     * @param bool $debug Active le mode débogage avec informations détaillées
     * @param string|null $appName Nom de l'application pour les logs
     */
    public function __construct(
        private readonly ResponseFactoryInterface $res,
        private readonly bool $debug = false,
        private readonly ?string $appName = null,
    ) {
    }

    /**
     * Traite la requête et capture toutes les exceptions.
     *
     * @param Request $request Requête HTTP entrante
     * @param HandlerInterface $next Gestionnaire suivant dans le pipeline
     * @return Response Réponse HTTP avec gestion d'erreur
     */
    public function process(Request $request, HandlerInterface $next): Response
    {
        $reqId = self::requestId();

        try {
            $resp = $next->handle($request);

            return $resp->withHeader('X-Request-Id', $reqId);
        } catch (MethodNotAllowed $e) {
            $payload = $this->basePayload($request, $reqId, 405, 'Method Not Allowed');
            if ($this->debug) {
                $payload['details'] = ['allowed' => $e->allowed];
            }
            $resp = $this->res->json($payload, 405)
                ->withHeader('X-Request-Id', $reqId)
                ->withHeader('Allow', implode(', ', $e->allowed));

            return $resp;
        } catch (NotFound $e) {
            // Navigateur (Accept: text/html, hors routes d'API) : page 404 au
            // gabarit du site. API/XHR : réponse JSON comme avant.
            $accept = $request->headers['Accept'] ?? '';
            $isApi = str_starts_with($request->path, '/api') || str_contains($request->path, '/api/');
            if (!$isApi && str_contains($accept, 'text/html')) {
                return $this->res->html(self::notFoundHtml(), 404)
                    ->withHeader('X-Request-Id', $reqId);
            }

            $payload = $this->basePayload($request, $reqId, 404, 'Not Found');

            return $this->res->json($payload, 404)
                ->withHeader('X-Request-Id', $reqId);
        } catch (HttpException $e) {
            $status = $e->status;
            $message = $e->getMessage() !== '' ? $e->getMessage() : ($status >= 500 ? 'Server Error' : 'HTTP Error');

            $payload = $this->basePayload($request, $reqId, $status, $message);
            if ($this->debug) {
                $payload['debug'] = $this->debugBlock($e);
            }

            $resp = $this->res->json($payload, $status)->withHeader('X-Request-Id', $reqId);

            return $this->applyHeaders($resp, $e->headers);
        } catch (\Throwable $e) {
            $payload = $this->basePayload($request, $reqId, 500, 'Server Error');
            if ($this->debug) {
                $payload['debug'] = $this->debugBlock($e);
            }

            return $this->res->json($payload, 500)
                ->withHeader('X-Request-Id', $reqId);
        }
    }

    /**
     * Crée la structure de base pour les réponses d'erreur.
     *
     * @param Request $r Requête originale
     * @param string $reqId Identifiant unique de la requête
     * @param int $status Code de statut HTTP
     * @param string $message Message d'erreur
     * @return array{
     *   app?:string,
     *   requestId:string,
     *   status:int,
     *   error:array{type:string,message:string},
     *   request:array{method:string,path:string}
     * }
     */
    private function basePayload(Request $r, string $reqId, int $status, string $message): array
    {
        $base = [
            'requestId' => $reqId,
            'status' => $status,
            'error' => [
                'type' => $this->statusToType($status),
                'message' => $message,
            ],
            'request' => [
                'method' => $r->method,
                'path' => $r->path,
            ],
        ];
        if ($this->appName) {
            $base['app'] = $this->appName;
        }

        return $base;
    }

    /**
     * Crée un bloc de débogage détaillé pour une exception.
     *
     * @param \Throwable $e Exception à déboguer
     * @return array<string,mixed> Informations de débogage
     */
    private function debugBlock(\Throwable $e): array
    {
        return [
            'class' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
            'causes' => $this->flattenCauses($e->getPrevious()),
        ];
    }

    /**
     * Aplatit la chaîne des causes d'une exception.
     *
     * @param \Throwable|null $e Exception précédente
     * @return list<array{class:string,message:string,file:string}> Liste des causes
     */
    private function flattenCauses(?\Throwable $e): array
    {
        $out = [];
        while ($e) {
            $out[] = [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ];
            $e = $e->getPrevious();
        }

        return $out;
    }

    /**
     * Convertit un code de statut HTTP en type d'erreur lisible.
     *
     * @param int $s Code de statut HTTP
     * @return string Type d'erreur
     */
    private static function statusToType(int $s): string
    {
        return match (true) {
            $s === 400 => 'bad_request',
            $s === 401 => 'unauthorized',
            $s === 403 => 'forbidden',
            $s === 404 => 'not_found',
            $s === 405 => 'method_not_allowed',
            $s === 429 => 'too_many_requests',
            $s >= 500 => 'server_error',
            default => 'http_error',
        };
    }

    /**
     * Applique des en-têtes à une réponse.
     *
     * @param Response $resp Réponse à modifier
     * @param array<string, list<string>> $headers En-têtes à appliquer
     * @return Response Nouvelle réponse avec les en-têtes
     */
    private function applyHeaders(Response $resp, array $headers): Response
    {
        foreach ($headers as $name => $values) {
            $first = true;
            foreach ($values as $v) {
                $resp = $first
                    ? $resp->withHeader($name, $v)
                    : $resp->withAddedHeader($name, $v);
                $first = false;
            }
        }

        return $resp;
    }

    /**
     * Page 404 HTML au gabarit du site (pour les navigateurs).
     *
     * @return string Document HTML complet
     */
    private static function notFoundHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page introuvable – Sécurité Sociale de l'Alimentation</title>
  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
  <style>
    .err404 { min-height: 100vh; min-height: 100svh; display: flex; flex-direction: column;
      align-items: center; justify-content: center; text-align: center; gap: 1rem; padding: 2rem 1.25rem; }
    .err404 img { width: 120px; height: auto; }
    .err404 h1 { font-size: clamp(3rem, 15vw, 6rem); color: var(--ssa-vert, #43c466); margin: 0; line-height: 1; }
    .err404 p { color: var(--ssa-txt-noir, #222); font-size: 1.1rem; margin: 0; max-width: 32rem; }
    .err404 a.home { display: inline-block; margin-top: 1rem; padding: 12px 28px;
      background: var(--ssa-vert, #43c466); color: #fff; border-radius: 5px; text-decoration: none; font-weight: 600; }
    .err404 a.home:hover { background: var(--ssa-hover-vert, #2fa34f); }
  </style>
</head>
<body>
  <main class="err404">
    <img src="/assets/img/logo.svg" alt="Sécurité Sociale de l'Alimentation — Pays de Morlaix">
    <h1>404</h1>
    <p>Désolé, cette page n'existe pas ou a été déplacée.</p>
    <a class="home" href="/">Retour à l'accueil</a>
  </main>
</body>
</html>
HTML;
    }

    /**
     * Génère un identifiant unique pour la requête.
     *
     * @return string UUID v4
     */
    private static function requestId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
