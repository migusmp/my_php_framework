<?php

use App\Core\Route;

Route::get('/', 'HomeController@index')
    ->name('index');

Route::get('/product/{id}', 'HomeController@show')
    ->name('product.show');

// Página estática “about”
Route::view('/about', 'pages/about', ['title' => 'Sobre nosotros'])
    ->name('about');

// GET /login → solo invitados
Route::get('/login', 'AuthController@get_login')
    ->middleware('guest');

// GET /register → solo invitados
Route::get('/register', 'AuthController@get_register')
    ->middleware('guest');

// GET /logout → solo autenticados
Route::get('/logout', 'AuthController@logout')
    ->middleware('auth');

// POST /login → invitados + CSRF
Route::post('/login', 'AuthController@post_login')
    ->middleware(['guest', 'csrf']);

// POST /register → invitados + CSRF
Route::post('/register', 'AuthController@post_register')
    ->middleware(['guest', 'csrf']);

// GET /dashboard → solo autenticados
Route::get('/dashboard', 'DashboardController@index')
    ->middleware('auth');

Route::get('/curso/cliente', 'CursoController@index');
