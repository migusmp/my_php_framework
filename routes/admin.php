<?php

use App\Core\Router;
use App\Controllers\AdminController;

$router->group('/admin', function (Router $router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
    $router->post('/users/create', [AdminController::class, 'create_user']);
}, ['auth', 'admin']);
