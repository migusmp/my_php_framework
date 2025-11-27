<?php

use App\Core\Route;
use App\Controllers\AdminController;
use App\Controllers\AdminUserController;

Route::group([
    'prefix'     => 'admin',
    'middleware' => ['auth', 'admin'],
], function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])
        ->name('admin.dashboard');

    // CRUD completo de usuarios dentro de /admin/users
    Route::resource('users', AdminUserController::class);
});
