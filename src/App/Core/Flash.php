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
    private const SESSION_KEY = 'FLASH_MESSAGES';

    public const TYPE_ERROR   = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO    = 'info';
    public const TYPE_SUCCESS = 'success';

    private function __construct()
    {
    }

    public static function add(string $name, string $message, string $type = self::TYPE_INFO): void
    {
        error_log("[FLASH] ADD key={$name}, type={$type}, message=" . var_export($message, true));

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
            error_log('[FLASH] SESSION_KEY creado');
        }

        $_SESSION[self::SESSION_KEY][$name] = [
            'message' => $message,
            'type'    => $type,
        ];

        error_log('[FLASH] Estado de $_SESSION[FLASH_MESSAGES]=' . print_r($_SESSION[self::SESSION_KEY], true));
    }

    public static function consume(string $name): ?array
    {
        error_log("[FLASH] CONSUME key={$name}");

        if (
            !isset($_SESSION[self::SESSION_KEY]) ||
            !isset($_SESSION[self::SESSION_KEY][$name])
        ) {
            error_log("[FLASH] CONSUME key={$name} NO ENCONTRADO");
            return null;
        }

        $flash = $_SESSION[self::SESSION_KEY][$name];
        unset($_SESSION[self::SESSION_KEY][$name]);

        error_log("[FLASH] CONSUME key={$name} => " . print_r($flash, true));

        if (empty($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
            error_log('[FLASH] SESSION_KEY vacío, eliminado');
        }

        return $flash;
    }

    public static function has(string $name): bool
    {
        $has = isset($_SESSION[self::SESSION_KEY][$name]);
        error_log("[FLASH] HAS key={$name}? " . ($has ? 'YES' : 'NO'));
        return $has;
    }

    public static function consumeAll(): array
    {
        error_log('[FLASH] CONSUME_ALL llamado');

        if (!isset($_SESSION[self::SESSION_KEY])) {
            error_log('[FLASH] CONSUME_ALL: no hay mensajes');
            return [];
        }

        $all = $_SESSION[self::SESSION_KEY];
        unset($_SESSION[self::SESSION_KEY]);

        error_log('[FLASH] CONSUME_ALL => ' . print_r($all, true));

        return $all;
    }

    public static function set(string $key, string $message): void
    {
        error_log("[FLASH] SET key={$key}, message=" . var_export($message, true));
        self::add($key, $message, self::TYPE_INFO);
    }

    public static function get(string $key): ?string
    {
        error_log("[FLASH] GET key={$key}");
        $flash = self::consume($key);

        if ($flash === null) {
            error_log("[FLASH] GET key={$key} => null");
            return null;
        }

        error_log("[FLASH] GET key={$key} => " . var_export($flash['message'], true));
        return $flash['message'] ?? null;
    }

    public static function put(string $key, mixed $value): void
    {
        error_log("[FLASH] PUT key={$key}, value=" . var_export($value, true));

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $_SESSION[self::SESSION_KEY][$key] = $value;

        error_log('[FLASH] Estado de $_SESSION[FLASH_MESSAGES]=' . print_r($_SESSION[self::SESSION_KEY], true));
    }

    private static function format(array $flash): string
    {
        error_log('[FLASH] FORMAT => ' . print_r($flash, true));

        $type    = \htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $message = \htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

        $classes = [
            'success' => 'flash flash-success',
            'error'   => 'flash flash-error',
            'warning' => 'flash flash-warning',
            'info'    => 'flash flash-info',
        ];

        $class = $classes[$type] ?? $classes['info'];

        return \sprintf(
            '<div class="%s">%s</div>',
            $class,
            $message
        );
    }

    public static function render(string $name): string
    {
        error_log("[FLASH] RENDER key={$name}");
        $flash = self::consume($name);

        if ($flash === null) {
            error_log("[FLASH] RENDER key={$name} => no hay mensaje");
            return '';
        }

        $html = self::format($flash);
        error_log("[FLASH] RENDER key={$name} => HTML generado");

        return $html;
    }

    public static function renderAll(): string
    {
        error_log('[FLASH] RENDER_ALL llamado');
        $all = self::consumeAll();

        if (empty($all)) {
            error_log('[FLASH] RENDER_ALL => no hay mensajes');
            return '';
        }

        $html = '';

        foreach ($all as $flash) {
            $html .= self::format($flash) . PHP_EOL;
        }

        error_log('[FLASH] RENDER_ALL => HTML generado');

        return $html;
    }
}
