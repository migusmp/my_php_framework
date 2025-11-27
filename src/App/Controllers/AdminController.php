<?php

namespace App\Controllers;

use App\Core\View;

class AdminController
{
    public function dashboard()
    {
        View::render('admin/dashboard', [
            'title' => 'Admin dashboard',
            'styles' => ['/assets/css/admin/dashboard.css']
        ]);
    }
}
