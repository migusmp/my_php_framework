<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Http\Request;
use App\Http\Response;

class GuestMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next): void
    {
        $path   = $request->path();
        $method = $request->method();

        error_log(sprintf('[MIDDLEWARE] %s %s', $method, $path));

        // Si ya estás autenticado, no tiene sentido ir a /login o /register
        if (Auth::user() !== null) {
            \header('Location: /');
            exit;
        }

        $next();
    }
}
