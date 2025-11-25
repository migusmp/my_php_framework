<?php

use App\Core\Router;

$router->group('/admin', function (Router $router) {
    $router->get('/dashboard', 'AdminController@dashboard');
    $router->get('/users', 'AdminController@users');
    $router->post('/users/create', 'AdminController@create_user');
}, ['auth', 'admin']);
