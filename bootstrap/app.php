<?php

declare(strict_types=1);

use App\Modules\Home\HomeController;
use App\Modules\Article\ArticleController;
use App\Modules\Dashboard\DashboardController;
use App\Modules\Login\LoginController;
use App\Modules\Galerie\GalerieController;
use App\Modules\Projet\ProjetController;
use App\Modules\User\UserController;
use App\Modules\Agenda\AgendaController;
use Capsule\Infrastructure\Container\DIContainer;
use Capsule\Routing\Discovery\RouteScanner;
use Capsule\Routing\RouterHandler;
use Capsule\Routing\Dispatch\ControllerInvoker;
use App\Modules\Galerie\GalerieService;
use App\Modules\Galerie\GalerieRepository;

$container = require dirname(__DIR__) . '/config/container.php';

$container->set(GalerieService::class, function() use ($container) {
    return new GalerieService(
        $container->get(GalerieRepository::class),
        // autres dépendances si besoin
    );
});

if (!$container instanceof DIContainer) {
    throw new RuntimeException('config/container.php must return a DIContainer instance.');
}

/** 2) Router */
$router = new RouterHandler();

/** 3) Lier container à l'invoker */
ControllerInvoker::setContainer($container);

/** 4) Liste explicite des contrôleurs */
$controllers = [
    HomeController::class,
    ArticleController::class,
    DashboardController::class,
    LoginController::class,
    GalerieController::class,
    ProjetController::class,
    UserController::class,
    AgendaController::class,
];

/** 5) Enregistrer les routes via attributs */
RouteScanner::register($controllers, $router);

/** 6) Retourner container + router */
return [$container, $router];
