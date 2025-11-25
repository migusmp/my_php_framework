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
    public function users()
    {
        View::render('admin/users', [
            'title' => 'Admin users',
            'styles' => ['/assets/css/admin/users.css']
        ]);
    }

    public function create_user()
    {
        // POST
    }
}
