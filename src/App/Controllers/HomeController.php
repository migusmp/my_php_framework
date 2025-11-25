<?php

namespace App\Controllers;

use App\Core\View;

class HomeController
{
    public function index(): void
    {
        $user = $_SESSION['user'] ?? null;

        View::render('home', [
            'title' => 'Inicio',
            'user'  => $user,
            'styles' => ['/assets/css/home.css'],
        ]);
    }

    public function show(string $id): void
    {
        echo $id;
    }
}
