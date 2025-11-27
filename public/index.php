<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');     // Para ver errores en el navegador
ini_set('log_errors', '1');         // Para que error_log() funcione
ini_set('error_log', __DIR__ . '/../var/log/app.log'); // Fichero de log

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
$router->setControllerNamespace('App\\Controllers');

$router->registerMiddleware('admin', new AdminMiddleware());
$router->registerMiddleware('auth', new AuthMiddleware());
$router->registerMiddleware('guest', new GuestMiddleware());
$router->registerMiddleware('csrf', new CsrfMiddleware());

// Lo marcamos como global para usarlo desde helpers (url())
$router->setAsGlobal();

// Cargamos todas las rutas de /routes/*.php
foreach (glob(__DIR__ . '/../routes/*.php') as $routeFile) {
    require $routeFile;
}


// Aquí ya registras tus rutas...
// $router->get('/login', 'AuthController@get_login')->name('login');
// ...

// Al final, haces dispatch:
$uri    = $_SERVER['REQUEST_URI']  ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

error_log(sprintf('[FRONT] %s %s', $method, $uri));

$router->dispatch($uri, $method);
