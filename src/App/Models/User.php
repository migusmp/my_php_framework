<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Entidad simple User.
 *
 * Representa una fila de la tabla `users`.
 */
class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $password;

    /**
     * Rol del usuario (ej: 'user', 'admin').
     */
    public string $role;

    /**
     * Fecha de creación (timestamp de BBDD).
     */
    public string $created_at;

    /**
     * Fecha de última actualización (puede ser null).
     */
    public ?string $updated_at = null;

    /**
     * Construye un User a partir de un array asociativo devuelto por PDO.
     */
    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->id         = (int) ($row['id'] ?? 0);
        $user->name       = (string) ($row['name'] ?? '');
        $user->email      = (string) ($row['email'] ?? '');
        $user->password   = (string) ($row['password'] ?? '');

        // Nuevos campos
        $user->role       = (string) ($row['role'] ?? 'user');
        $user->created_at = (string) ($row['created_at'] ?? '');
        $user->updated_at = isset($row['updated_at'])
            ? (string) $row['updated_at']
            : null;

        return $user;
    }

    /**
     * Serializa de vuelta a array (útil para vistas/JSON).
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'password'   => $this->password,
            'role'       => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
