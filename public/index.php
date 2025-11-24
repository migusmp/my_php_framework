<?php

declare(strict_types=1);

// require_once es como 'require' pero verifica si ya ha sido incluido y si es el caso no lo incluye
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AboutController;

// Creamos el router del "framework"
$router = new Router();

// Definimos rutas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);

// Lanzamos el router
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
