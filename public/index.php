<?php

declare(strict_types=1);

session_start();

// require_once es como 'require' pero verifica si ya ha sido incluido y si es el caso no lo incluye
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

// Creamos el router del "framework"
$router = new Router();

$router->registerMiddleware('admin', new AdminMiddleware());
$router->registerMiddleware('auth', new AuthMiddleware());
$router->registerMiddleware('guest', new GuestMiddleware());
$router->registerMiddleware('csrf', new CsrfMiddleware());

// Cargamos todas las rutas de /routes/*.php
foreach (glob(__DIR__ . '/../routes/*.php') as $routeFile) {
    require $routeFile;
}

// Lanzamos el router
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
