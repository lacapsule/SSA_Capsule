<?php

declare(strict_types=1);

use CapsuleLib\Core\DIContainer;
use CapsuleLib\Routing\Router;

require_once dirname(__DIR__) . '/lib/Helper/html_secure.php';

/** 1) Container */
$container = require dirname(__DIR__) . '/config/container.php';
if (!$container instanceof DIContainer) {
    throw new RuntimeException('config/container.php must return a DIContainer instance.');
}

/** 2) Router */
$router = new Router();

/**
 * 3) Charger l’enregistreur de routes (callable)
 *    La fonction retournée doit avoir la signature:
 *      function (Router $router, DIContainer $c): void
 */
$registerRoutes = require dirname(__DIR__) . '/config/routes.php';
if (!is_callable($registerRoutes)) {
    throw new RuntimeException('config/routes.php must return a callable (Router, DIContainer) => void.');
}

/** 4) Enregistrer toutes les routes (avec groupes/middlewares/noms si besoin) */
$registerRoutes($router, $container);

/** 5) NotFound handler */
$router->setNotFoundHandler(function (): void {
    http_response_code(404);
    echo '404 Not Found';
});

/** 6) Retourner le router prêt à être dispatché par public/index.php */
return $router;
