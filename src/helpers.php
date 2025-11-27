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
