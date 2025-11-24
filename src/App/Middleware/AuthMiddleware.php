<?php

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function __invoke(callable $next): void
    {
        // Si no hay usuario autenticado, redirigimos al login
        if (Auth::user() === null) {
            \header('Location: /login');
            exit;
        }

        // Si hay usuario, seguimos con la ejecución
        $next();
    }
}
