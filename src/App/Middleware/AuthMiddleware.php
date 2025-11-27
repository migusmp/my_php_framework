<?php

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function __invoke(callable $next): void
    {
        error_log('===== [MIDDLEWARE] AuthMiddleware ENTER =====');
        error_log('[MIDDLEWARE][Auth] SESSION=' . print_r($_SESSION, true));

        $user = Auth::user();
        error_log('[MIDDLEWARE][Auth] Auth::user() => ' . var_export($user, true));

        if ($user === null) {
            error_log('[MIDDLEWARE][Auth] Usuario NO autenticado, redirect /login');
            \header('Location: /login');
            exit;
        }

        error_log('[MIDDLEWARE][Auth] Usuario autenticado, continuando...');
        $next();
        error_log('===== [MIDDLEWARE] AuthMiddleware EXIT =====');
    }
}
