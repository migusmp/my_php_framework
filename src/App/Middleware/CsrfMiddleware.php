<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Security\Csrf;

/**
 * Middleware de protección CSRF inspirado en VerifyCsrfToken de Laravel.
 *
 * - Se aplica a métodos "no seguros" (POST, PUT, PATCH, DELETE).
 * - Espera el token en:
 *      - $_POST['_token'] (formularios HTML)
 *      - o cabecera HTTP_X_CSRF_TOKEN
 *      - o combinación X-XSRF-TOKEN + cookie XSRF-TOKEN (para AJAX)
 *
 * No rota el token; solo valida. La rotación se hace en puntos sensibles
 * como login/logout llamando a Csrf::regenerateToken().
 */
class CsrfMiddleware
{
    public function __invoke(callable $next): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Métodos "seguros" → no requieren CSRF, pero seguimos la cadena
        if (\in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $next();
            return;
        }

        // 1) Token enviado en el formulario (campo hidden _token)
        $token = $_POST['_token'] ?? null;

        // 2) Token enviado en cabecera X-CSRF-TOKEN (peticiones AJAX)
        if ($token === null && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // 3) X-XSRF-TOKEN + cookie XSRF-TOKEN (patrón similar al de Laravel)
        if ($token === null && isset($_SERVER['HTTP_X_XSRF_TOKEN'], $_COOKIE['XSRF-TOKEN'])) {
            // En Laravel aquí se desencripta la cookie; en este mini-framework
            // asumimos que el valor de la cabecera coincide con el token real.
            $token = $_SERVER['HTTP_X_XSRF_TOKEN'];
        }

        // Validación del token frente al almacenado en sesión
        if (!Csrf::validate($token)) {
            \http_response_code(419); // "Page Expired" como en Laravel
            echo 'CSRF token inválido o ausente.';
            return; // no llamamos a $next(): cortamos la cadena
        }

        // Todo OK → continuar con el siguiente middleware / handler
        $next();
    }
}
