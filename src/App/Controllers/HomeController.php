<?php

namespace App\Controllers;

use App\Core\View;
use App\Http\Request;

class HomeController
{
    public function index(Request $request): void
    {
        $request->input('tipo');
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
