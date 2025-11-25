<?php

namespace App\Controllers;

use App\Core\View;

class DashboardController
{
    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;

        View::render('dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'styles' => ['/assets/css/dashboard.css'],
        ]);
    }
}
