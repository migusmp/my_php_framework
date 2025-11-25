<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private const VIEWS_PATH   = __DIR__ . '/../../Views/';
    private const LAYOUT_FILE  = 'layout.php';

    /**
     * Renderiza una vista dentro del layout principal.
     *
     * @param string $view  Ruta de la vista relativa a Views/ (sin .php)
     *                      Ej: 'home', 'dashboard', 'auth/login'
     * @param array  $data  Variables que quieres pasar a la vista
     */
    public static function render(string $view, array $data = []): void
    {
        // Las claves del array se convierten en variables ($title, $user, etc.)
        extract($data, EXTR_SKIP);

        // 1) Renderizamos la vista concreta a un buffer
        ob_start();
        require self::VIEWS_PATH . $view . '.php';
        $content = ob_get_clean(); // aquí queda el HTML de la vista

        // 2) Renderizamos el layout, que usará $content
        require self::VIEWS_PATH . self::LAYOUT_FILE;
    }
}
