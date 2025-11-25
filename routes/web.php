<?php

$router->get('/', 'HomeController@index');
$router->get('/product/{id}', 'HomeController@show'); // RUTA DE PRUEBA {}
$router->get('/about', 'AboutController@index');

$router->middleware('guest')->get('/login', 'AuthController@get_login');
$router->middleware('guest')->get('/register', 'AuthController@get_register');

$router->get('/logout', 'AuthController@logout');

$router->middleware('guest', 'csrf')->post('/login', 'AuthController@post_login');
$router->middleware('guest', 'csrf')->post('/register', 'AuthController@post_register');

$router->middleware('auth')->get('/dashboard', 'DashboardController@index');
