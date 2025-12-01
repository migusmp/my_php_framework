<?php

declare(strict_types=1);

use App\Core\Router;
use App\Http\RedirectResponse;
use App\Security\Csrf;

function csrf_field(): void
{
    echo '<input type="hidden" name="_token" value="'
        . htmlspecialchars(Csrf::getToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function redirect(string $url, int $status = 302): RedirectResponse
{
    return new RedirectResponse($url, $status);
}

/**
 * Genera la URL de una ruta por su nombre.
 *
 * Ejemplos:
 *   url('login')
 *   url('user.show', ['id' => 5])
 */
function url(string $name, array $params = []): string
{
    return Router::getInstance()->route($name, $params);
}

/**
 * Alias de url() por si te apetece usar route('nombre').
 */
function route(string $name, array $params = []): string
{
    return url($name, $params);
}

/**
 * Helper de configuración.
 *
 * Soporta:
 *   config('app.name')
 *   config('database.host')
 *   config('database')        // 🔥 devuelve todo el array de database.php
 */
function config(string $key, mixed $default = null): mixed
{
    static $configs = [];

    if ($configs === []) {
        // Buscar la carpeta /config subiendo directorios hasta encontrarla
        $dir = __DIR__; // donde está helpers.php

        while ($dir !== '/' && !\is_dir($dir . '/config')) {
            $dir = \dirname($dir);
        }

        if (!\is_dir($dir . '/config')) {
            throw new \RuntimeException('No se ha encontrado la carpeta /config');
        }

        $basePath = $dir . '/config';

        foreach (\glob($basePath . '/*.php') as $file) {
            $name = \basename($file, '.php'); // app.php → app, database.php → database
            $configs[$name] = require $file;
        }

    }

    // Si llaman a config('database') → devolver todo el array de ese archivo
    if (!\str_contains($key, '.')) {
        return $configs[$key] ?? $default;
    }

    // Soporta notación tipo: config('app.name')
    [$file, $item] = \explode('.', $key, 2);

    return $configs[$file][$item] ?? $default;
}

/**
 * Helper env() encima de $_ENV, con valor por defecto.
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}
