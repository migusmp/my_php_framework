<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\Response;

final class View
{
    /**
     * Ruta base de las vistas.
     *
     * Estructura asumida:
     *  - src/
     *    - App/
     *      - Core/
     *      - Views/
     *
     * Vistas:
     *  - Views/layout.php
     *  - Views/home.php
     *  - Views/auth/login.php
     */
    private const VIEWS_PATH  = __DIR__ . '/../../Views/';
    private const LAYOUT_FILE = 'layout.php';

    /**
     * Renderiza una vista dentro del layout principal y la imprime (echo).
     *
     * @param string $view Ruta relativa a Views/ sin .php (ej: 'home', 'auth/login')
     * @param array  $data Datos que quieres pasar a la vista
     */
    public static function render(string $view, array $data = []): void
    {
        echo self::renderToString($view, $data);
    }

    /**
     * Renderiza una vista dentro del layout principal y devuelve
     * el HTML resultante como string (sin hacer echo).
     *
     * Útil para integrarse con Response:
     *
     *   $html = View::renderToString('home', [...]);
     *   $response->setContent($html);
     */
    public static function renderToString(string $view, array $data = []): string
    {
        // Comprobamos que exista la vista
        $viewFile   = self::VIEWS_PATH . $view . '.php';
        $layoutFile = self::VIEWS_PATH . self::LAYOUT_FILE;

        if (!\file_exists($viewFile)) {
            throw new \RuntimeException("La vista '{$view}' no existe en {$viewFile}");
        }

        if (!\file_exists($layoutFile)) {
            throw new \RuntimeException("El layout principal no existe en {$layoutFile}");
        }

        // Las claves del array se convierten en variables ($title, $user, etc.)
        if (!empty($data)) {
            \extract($data, EXTR_SKIP);
        }

        // Iniciamos un buffer "externo" para capturar el layout completo
        \ob_start();

        try {
            // 1) Renderizamos la vista concreta a un buffer interno
            \ob_start();
            require $viewFile;
            $content = \ob_get_clean(); // aquí queda el HTML de la vista

            // 2) Renderizamos el layout, que usará $content + variables extraídas
            require $layoutFile;

            // 3) Devolvemos todo lo que haya generado el layout
            $output = \ob_get_clean();
        } catch (\Throwable $e) {
            // En caso de error, limpiamos el buffer externo y relanzamos
            \ob_end_clean();
            throw $e;
        }

        return $output === false ? '' : $output;
    }

    /**
     * Renderiza una vista dentro del layout y la vuelca en un Response.
     *
     * Atajo para:
     *   $html = View::renderToString(...);
     *   $response->setStatus($statusCode)->setContent($html);
     */
    public static function renderToResponse(
        string $view,
        array $data,
        Response $response,
        int $statusCode = 200
    ): void {
        $html = self::renderToString($view, $data);

        $response
            ->setStatus($statusCode)
            ->setContent($html);
    }
}
