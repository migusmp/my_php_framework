<?php

namespace App\Middleware;

use App\Core\Auth;

class GuestMiddleware
{
    public function __invoke(callable $next): void
    {
        // Si ya estás autenticado, no tiene sentido ir a /login o /register
        if (Auth::user() !== null) {
            \header('Location: /');
            exit;
        }

        $next();
    }
}
