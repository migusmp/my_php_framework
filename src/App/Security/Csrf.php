<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Utilidad para generación y validación de tokens CSRF,
 * inspirada en el comportamiento de Laravel.
 *
 * Usa la sesión nativa como almacenamiento de servidor.
 */
final class Csrf
{
    private const SESSION_KEY = '_token';

    /**
     * Devuelve el token CSRF actual.
     * Si no existe, lo genera (equivalente a session()->token()).
     */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            self::regenerateToken();
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Alias por si prefieres Csrf::getToken().
     */
    public static function getToken(): string
    {
        return self::token();
    }

    /**
     * Regenera el token CSRF (equivalente a session()->regenerateToken()).
     *
     * Úsalo en flujos sensibles como login, logout o registro.
     */
    public static function regenerateToken(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(\random_bytes(32));
    }

    /**
     * Valida el token recibido contra el token guardado en sesión.
     */
    public static function validate(?string $token): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || $token === null) {
            return false;
        }

        return \hash_equals($_SESSION[self::SESSION_KEY], $token);
    }
}
