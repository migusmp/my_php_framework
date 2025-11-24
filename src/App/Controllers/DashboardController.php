<?php

namespace App\Controllers;

class DashboardController
{
    public function index(): void
    {
        require __DIR__ . '/../../../templates/dashboard.php';
    }
}
