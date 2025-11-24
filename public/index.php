<?php

declare(strict_types=1);

session_start();

// require_once es como 'require' pero verifica si ya ha sido incluido y si es el caso no lo incluye
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/autoload.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AboutController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

// Creamos el router del "framework"
$router = new Router();

$router->registerMiddleware('auth', new AuthMiddleware());
$router->registerMiddleware('guest', new GuestMiddleware());

// RUTAS GET
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);
$router->middleware('guest')->get('/login', [AuthController::class, 'get_login']);
$router->middleware('guest')->get('/register', [AuthController::class, 'get_register']);
$router->get('/logout', [AuthController::class, 'logout']);

// RUTAS POST
$router->middleware('guest')->post('/login', [AuthController::class, 'post_login']);
$router->middleware('guest')->post('/register', [AuthController::class, 'post_register']);

// RUTAS PROTEGIDAS
$router->middleware('auth')->get('/dashboard', [DashboardController::class, 'index']);

// Lanzamos el router
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
