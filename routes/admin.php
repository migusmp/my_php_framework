<?php

use App\Controllers\AdminController;
use App\Core\Router;

$router->group('/admin', function (Router $r) {
    $r->get('/dashboard', [AdminController::class, 'dashboard']);
    $r->get('/users', [AdminController::class, 'users']);
    $r->post('/users/create', [AdminController::class, 'create_user']);

}, ['auth', 'admin']);
