<?php

use App\Core\Route;

Route::get('/', 'HomeController@index')
    ->name('index');

Route::get('/product/{id}', 'HomeController@show')
    ->name('product.show');

Route::get('/about', 'AboutController@index')
    ->name('about');

// GET /login → solo invitados
Route::get('/login', 'AuthController@get_login')
    ->middleware('guest')
    ->name('login');

// GET /register → solo invitados
Route::get('/register', 'AuthController@get_register')
    ->middleware('guest')
    ->name('register');

// GET /logout → solo autenticados
Route::get('/logout', 'AuthController@logout')
    ->middleware('auth')
    ->name('logout');

// POST /login → invitados + CSRF
Route::post('/login', 'AuthController@post_login')
    ->middleware(['guest', 'csrf']);

// POST /register → invitados + CSRF
Route::post('/register', 'AuthController@post_register')
    ->middleware(['guest', 'csrf']);

// GET /dashboard → solo autenticados
Route::get('/dashboard', 'DashboardController@index')
    ->middleware('auth')
    ->name('dashboard');
