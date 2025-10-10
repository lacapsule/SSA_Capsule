<?php

declare(strict_types=1);

use Capsule\Infrastructure\Container\DIContainer;
use Capsule\Routing\Discovery\RouteScanner;
use Capsule\Routing\RouterHandler;
use Capsule\Routing\Dispatch\ControllerInvoker;

require_once dirname(__DIR__) . '/src/Support/html_secure.php';

/** 1) Container */
$container = require dirname(__DIR__) . '/config/container.php';
if (!$container instanceof DIContainer) {
    throw new RuntimeException('config/container.php must return a DIContainer instance.');
}

/** 2) Router */
$router = new RouterHandler();

/** 3) Lier container à l’invoker */
ControllerInvoker::setContainer($container);

/** 4) Découverte auto */
$controllers = [];
$baseDir = dirname(__DIR__) . '/app/Controller';
$files = glob($baseDir . '/*Controller.php') ?: [];
foreach ($files as $file) {
    $fqcn = 'App\\Controller\\' . basename($file, '.php');
    if (!class_exists($fqcn)) {
        require_once $file;
    }
    if (class_exists($fqcn)) {
        $controllers[] = $fqcn;
    }
}

/** 5) Enregistrer les routes via attributs */
if ($controllers) {
    RouteScanner::register($controllers, $router);
}

/** 6) Retourner container + router */
return [$container, $router];
