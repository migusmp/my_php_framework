<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestiona mensajes flash basados en sesión.
 *
 * Permite:
 *  - Guardar mensajes asociados a una clave (name) y un tipo (error, info, etc.)
 *  - Recuperar y consumir mensajes en la siguiente petición
 *  - Renderizar mensajes en HTML si se desea
 *
 * Requiere que la sesión esté iniciada (session_start()) antes de su uso.
 */
final class Flash
{
    /**
     * Clave principal en $_SESSION donde se guardan los mensajes.
     */
    private const SESSION_KEY = 'FLASH_MESSAGES';

    /**
     * Tipos de mensaje disponibles.
     */
    public const TYPE_ERROR   = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO    = 'info';
    public const TYPE_SUCCESS = 'success';

    /**
     * Constructor privado para evitar instanciación.
     */
    private function __construct()
    {
    }

    // ================================================================
    //                      API PRINCIPAL (ARRAY)
    // ================================================================

    /**
     * Crea o reemplaza un mensaje flash.
     *
     * @param string $name    Identificador del mensaje (ej: 'login_error')
     * @param string $message Texto del mensaje
     * @param string $type    Tipo de mensaje (error, warning, info, success)
     */
    public static function add(string $name, string $message, string $type = self::TYPE_INFO): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$name] = [
            'message' => $message,
            'type'    => $type,
        ];
    }

    /**
     * Obtiene un mensaje flash y lo elimina de la sesión.
     *
     * @return array|null ['message' => string, 'type' => string] o null si no existe
     */
    public static function consume(string $name): ?array
    {
        if (
            !isset($_SESSION[self::SESSION_KEY]) ||
            !isset($_SESSION[self::SESSION_KEY][$name])
        ) {
            return null;
        }

        $flash = $_SESSION[self::SESSION_KEY][$name];
        unset($_SESSION[self::SESSION_KEY][$name]);

        // Si ya no quedan mensajes, limpiamos la clave principal
        if (empty($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }

        return $flash;
    }

    /**
     * Devuelve true si existe un mensaje flash con ese nombre.
     */
    public static function has(string $name): bool
    {
        return isset($_SESSION[self::SESSION_KEY][$name]);
    }

    /**
     * Obtiene todos los mensajes flash y los elimina de la sesión.
     *
     * @return array<string, array{message:string,type:string}>
     */
    public static function consumeAll(): array
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return [];
        }

        $all = $_SESSION[self::SESSION_KEY];
        unset($_SESSION[self::SESSION_KEY]);

        return $all;
    }

    // ================================================================
    //                    ATAJOS STRING SIMPLES
    // ================================================================

    /**
     * Guarda un mensaje flash simple (sin preocuparse por el tipo).
     * Útil para casos rápidos donde solo importa el texto.
     *
     * @param string $key
     * @param string $message
     */
    public static function set(string $key, string $message): void
    {
        self::add($key, $message, self::TYPE_INFO);
    }

    /**
     * Devuelve SOLO el texto del mensaje (ignorando el tipo) y lo consume.
     *
     * @return string|null
     */
    public static function get(string $key): ?string
    {
        $flash = self::consume($key);

        return $flash['message'] ?? null;
    }

    // ================================================================
    //                    RENDERIZADO EN HTML (OPCIONAL)
    // ================================================================

    /**
     * Genera el HTML para un mensaje flash con estilos según el tipo.
     *
     * @param array{message:string,type:string} $flash
     */
    private static function format(array $flash): string
    {
        $type    = \htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $message = \htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

        // Mapeo: tipo de mensaje → clase CSS concreta
        $classes = [
            'success' => 'flash flash-success',
            'error'   => 'flash flash-error',
            'warning' => 'flash flash-warning',
            'info'    => 'flash flash-info',
        ];

        // Si el tipo no existe, usar info por defecto
        $class = $classes[$type] ?? $classes['info'];

        return \sprintf(
            '<div class="%s">%s</div>',
            $class,
            $message
        );
    }

    /**
     * Consume y renderiza un mensaje flash concreto.
     *
     * Devuelve el HTML (no hace echo).
     */
    public static function render(string $name): string
    {
        $flash = self::consume($name);

        if ($flash === null) {
            return '';
        }

        return self::format($flash);
    }

    /**
     * Consume y renderiza todos los mensajes flash disponibles.
     *
     * Devuelve el HTML concatenado (no hace echo).
     */
    public static function renderAll(): string
    {
        $all = self::consumeAll();

        if (empty($all)) {
            return '';
        }

        $html = '';

        foreach ($all as $flash) {
            $html .= self::format($flash) . PHP_EOL;
        }

        return $html;
    }
}
