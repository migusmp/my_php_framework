<?php

namespace App\Controllers;

use App\Services\UserService;

class HomeController
{
    public function index(): void
    {
        $userService = new UserService();

        $saludo = "Hola desde " . APP_NAME;
        $edad   = 20;
        $mensajeEdad = $userService->mensajeEdad($edad);

        // Ruta a templates/home.php (3 niveles hacia arriba)
        require __DIR__ . '/../../../templates/home.php';
    }
}
