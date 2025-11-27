<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Http\Request;
use App\Http\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, Response $response, callable $next): void
    {
        $path   = $request->path();
        $method = $request->method();

        error_log(sprintf('[MIDDLEWARE] %s %s', $method, $path));

        $user = Auth::user();

        if ($user === null) {
            \header('Location: /login');
            exit;
        }

        $next();
    }
}
