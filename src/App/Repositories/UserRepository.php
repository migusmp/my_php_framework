<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\User;

/**
 * UserRepository
 *
 * Encapsula toda la lógica de acceso a datos relacionada con la tabla `users`.
 * No contiene reglas de negocio. Únicamente SQL + conversión a modelos.
 */
class UserRepository
{
    private PDO $pdo;

    /**
     * Inyectamos el PDO (Database::getConnection() en el UserService).
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los usuarios ordenados por ID descendente.
     *
     * @return User[]
     */
    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY id DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn ($row) => User::fromArray($row), $rows);
    }

    /**
     * Busca un usuario por su ID. Devuelve null si no existe.
     */
    public function find(int $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromArray($row) : null;
    }

    /**
     * Busca un usuario por su email. Devuelve null si no existe.
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? User::fromArray($row) : null;
    }

    /**
     * Crea un nuevo usuario en la base de datos.
     *
     * @param string $role Rol del usuario (user, admin, etc.)
     */
    public function create(
        string $name,
        string $email,
        string $hashedPassword,
        string $role = 'user'
    ): User {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, created_at, updated_at)
             VALUES (:name, :email, :password, :role, NOW(), NOW())'
        );

        $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $hashedPassword,
            'role'     => $role,
        ]);

        return $this->find((int)$this->pdo->lastInsertId());
    }

    /**
     * Actualiza la contraseña de un usuario.
     * También actualiza la fecha updated_at.
     */
    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password = :password,
                 updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'password' => $hashedPassword,
            'id'       => $id,
        ]);
    }

    /**
     * Actualiza el rol de un usuario.
     */
    public function updateRole(int $id, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET role = :role,
                 updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            'role' => $role,
            'id'   => $id,
        ]);
    }

    public function count(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) AS total FROM users');
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

}
