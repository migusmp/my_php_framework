<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Builder de una ruta concreta:
 *
 * Route::get('/login', 'AuthController@get_login')
 *     ->middleware('guest')
 *     ->name('login');
 */
final class RouteDefinition
{
    private Router $router;
    private string $method;
    private string $path;

    public function __construct(Router $router, string $method, string $path)
    {
        $this->router = $router;
        $this->method = $method;
        $this->path   = $path;
    }

    public function middleware(string|array ...$names): self
    {
        $all = [];

        foreach ($names as $name) {
            $all = array_merge($all, (array) $name);
        }

        $this->router->appendMiddlewareToRoute($this->method, $this->path, $all);

        return $this;
    }

    public function name(string $name): self
    {
        $this->router->nameRoute($this->method, $this->path, $name);

        return $this;
    }
}
