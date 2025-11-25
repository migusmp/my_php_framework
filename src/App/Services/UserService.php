<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\User;
use App\Repositories\UserRepository;
use PDO;

/**
 * UserService
 *
 * Capa de lógica de negocio relacionada con usuarios.
 * Aquí no hay SQL directo: se delega el acceso a datos al UserRepository.
 */
class UserService
{
    private PDO $pdo;

    /**
     * Repositorio encargado de hablar con la tabla `users`.
     */
    private UserRepository $users;

    public function __construct()
    {
        // Obtenemos la conexión solo una vez
        $this->pdo   = Database::getConnection();
        $this->users = new UserRepository($this->pdo);
    }

    /* =========================================================
     *   Operaciones de usuarios
     * ========================================================= */

    /**
     * Devuelve el número total de usuarios.
     */
    public function countUsers(): int
    {
        return $this->users->count();
    }

    /**
     * Devuelve todos los usuarios.
     *
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->users->all();
    }

    /**
     * Busca un usuario por ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->users->find($id);
    }

    /**
     * Busca un usuario por email.
     */
    public function findUserByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    /**
     * Crea un nuevo usuario aplicando la lógica de negocio:
     *  - Comprobar que el email no exista
     *  - Hashear la contraseña
     *  - Asignar rol por defecto (user)
     *
     * Devuelve la entidad User recién creada.
     */
    public function createUser(
        string $name,
        string $email,
        string $plainPassword,
        string $role = 'user'
    ): User {
        // ¿Ya existe un usuario con ese email?
        $existing = $this->users->findByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('Ya existe un usuario con ese email.');
        }

        // Hasheamos la contraseña
        $hashedPassword = \password_hash($plainPassword, PASSWORD_BCRYPT);

        // Creamos el usuario en BBDD mediante el repositorio
        return $this->users->create($name, $email, $hashedPassword, $role);
    }

    /**
     * Lógica de login:
     *  - Buscar usuario por email
     *  - Verificar contraseña
     * Devuelve User si las credenciales son correctas, o null si no.
     */
    public function login(string $email, string $plainPassword): ?User
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!\password_verify($plainPassword, $user->password)) {
            return null;
        }

        return $user;
    }

    /**
     * Actualiza la contraseña de un usuario.
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $hashedPassword = \password_hash($plainPassword, PASSWORD_BCRYPT);

        return $this->users->updatePassword($id, $hashedPassword);
    }

    /**
     * Actualiza el rol de un usuario (ej: user → admin).
     */
    public function updateRole(int $id, string $role): bool
    {
        return $this->users->updateRole($id, $role);
    }
}
