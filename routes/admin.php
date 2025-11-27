<?php

use App\Core\Route;
use App\Controllers\AdminController;

Route::group([
    'prefix'     => 'admin',
    'middleware' => ['auth', 'admin'],
], function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users/create', [AdminController::class, 'create_user'])->name('admin.users.create');

});
