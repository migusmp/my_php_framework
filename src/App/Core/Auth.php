<?php

namespace App\Core;

use App\Services\SessionService;

class Auth
{
    /**
     * Devuelve el usuario autenticado (array) o null si no hay sesión válida.
     */
    public static function user(): ?array
    {
        // Si ya está en $_SESSION, lo usamos
        if (!empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Si no hay cookie con token, no hay sesión
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            return null;
        }

        // Buscar en la BBDD usando SessionService
        $sessionService = new SessionService();
        $user = $sessionService->findUserByToken($token);

        if (!$user) {
            return null;
        }

        // Cachear en $_SESSION para no ir a BBDD cada vez
        $_SESSION['user'] = [
            'id'         => (int) $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'created_at' => $user['created_at'],
        ];

        return $_SESSION['user'];
    }

    /**
     * Forzar que el usuario esté autenticado o redirigir al login.
     */
    public static function requireAuth(): void
    {
        if (self::user() === null) {
            \header('Location: /login');
            exit;
        }
    }

    public static function redirectIfAuthenticated(): void
    {
        if (self::user() !== null) {
            header('Location: /');
            exit;
        }
    }
}
