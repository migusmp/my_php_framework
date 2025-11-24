<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class UserService
{
    private PDO $pdo;

    public function __construct()
    {
        // Obtenemos la conexión solo una vez
        $this->pdo = Database::getConnection();
    }

    public function isMayorEdad(int $age): bool
    {
        return $age >= 18;
    }

    public function mensajeEdad(int $age): string
    {
        return $this->isMayorEdad($age)
            ? 'Eres mayor de edad'
            : 'Eres menor de edad';
    }

    /**
    * Devuelve el número total de usuarios.
    */
    public function countUsers(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM users");
        $row = $stmt->fetch();
        return $row['total'] ?? 0;
    }

    public function findUserByEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        // Devolvemos un array asociativo con el usuario o false si no existe
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function createUser(string $name, string $email, string $hashedPassword): int
    {
        $sql = 'INSERT INTO users (name, email, password)
            VALUES (:name, :email, :password)';

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':name'     => $name,
            ':email'    => $email,
            ':password' => $hashedPassword,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
