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

    public static function group(string|array $prefixOrOptions, callable $callback, array $middleware = []): void
    {
        Router::getInstance()->group($prefixOrOptions, $callback, $middleware);
    }

    public static function groupFile(string|array $options, string $file): void
    {
        Router::getInstance()->groupFile($options, $file);
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

    public static function resource(string $name, string $controller): void
    {
        // index
        self::get("/{$name}", [$controller, 'index'])
            ->name("{$name}.index");

        // create
        self::get("/{$name}/create", [$controller, 'create'])
            ->name("{$name}.create");

        // store
        self::post("/{$name}", [$controller, 'store'])
            ->name("{$name}.store");

        // show
        self::get("/{$name}/{id}", [$controller, 'show'])
            ->name("{$name}.show");

        // edit
        self::get("/{$name}/{id}/edit", [$controller, 'edit'])
            ->name("{$name}.edit");

        // update
        self::put("/{$name}/{id}", [$controller, 'update'])
            ->name("{$name}.update");

        // destroy
        self::delete("/{$name}/{id}", [$controller, 'destroy'])
            ->name("{$name}.destroy");
    }

    public static function view(string $uri, string $view, array $data = []): RouteDefinition
    {
        return Router::getInstance()->get($uri, function () use ($view, $data) {
            \App\Core\View::render($view, $data);
        });
    }
}
