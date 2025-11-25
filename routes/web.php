<?php

use App\Controllers\HomeController;
use App\Controllers\AboutController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;

// Rutas públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);

// Auth
$router->middleware('guest')->get('/login', [AuthController::class, 'get_login']);
$router->middleware('guest')->get('/register', [AuthController::class, 'get_register']);
$router->get('/logout', [AuthController::class, 'logout']);

// POST de auth
$router->middleware('guest', 'csrf')->post('/login', [AuthController::class, 'post_login']);
$router->middleware('guest', 'csrf')->post('/register', [AuthController::class, 'post_register']);

// Rutas protegidas
$router->middleware('auth')->get('/dashboard', [DashboardController::class, 'index']);
