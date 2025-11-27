<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Fachada estática para el Router, al estilo Laravel.
 */
final class Route
{
    public static function middleware(string|array ...$names): Router
    {
        return Router::getInstance()->middleware(...$names);
    }

    public static function prefix(string $prefix): Router
    {
        return Router::getInstance()->prefix($prefix);
    }

    public static function group(string $prefix, callable $callback, array $middleware = []): void
    {
        Router::getInstance()->group($prefix, $callback, $middleware);
    }

    public static function get(string $path, callable|array|string $handler): RouteDefinition
    {
        return Router::getInstance()->get($path, $handler);
    }

    public static function post(string $path, callable|array|string $handler): RouteDefinition
    {
        return Router::getInstance()->post($path, $handler);
    }

    public static function put(string $path, callable|array|string $handler): RouteDefinition
    {
        return Router::getInstance()->put($path, $handler);
    }

    public static function patch(string $path, callable|array|string $handler): RouteDefinition
    {
        return Router::getInstance()->patch($path, $handler);
    }

    public static function delete(string $path, callable|array|string $handler): RouteDefinition
    {
        return Router::getInstance()->delete($path, $handler);
    }
}
