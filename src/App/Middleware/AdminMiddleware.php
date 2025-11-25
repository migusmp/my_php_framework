<?php

declare(strict_types=1);

namespace App\Middleware;

/**
 * Middleware de autorización para rutas de administración.
 *
 * Requisitos:
 *  - El usuario debe estar logueado (comprobado por AuthMiddleware).
 *  - El usuario debe tener role = 'admin'.
 *
 * Si no cumple estas condiciones:
 *  - Si no está logueado, se le redirige a /login.
 *  - Si está logueado pero no es admin, se le devuelve 403 o se le redirige.
 */
class AdminMiddleware
{
    public function __invoke(callable $next): void
    {
        // Si por alguna razón no ha pasado por AuthMiddleware,
        // nos aseguramos aquí de que esté logueado.
        if (!isset($_SESSION['user'])) {
            \header('Location: /login');
            exit;
        }

        $role = $_SESSION['user']['role'] ?? 'user';

        // Si NO es admin, devolvemos 403 o redirigimos a otra página.
        if ($role !== 'admin') {
            // Opción 1: respuesta 403 sin redirección
            http_response_code(403);
            echo 'Acceso denegado: se requieren permisos de administrador.';
            exit;

            // Opción 2 (alternativa): redirigir a / (comenta la opción 1 si usas esta)
            /*
            \header('Location: /');
            exit;
            */
        }

        $next();
    }
}
