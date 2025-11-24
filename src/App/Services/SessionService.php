<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use DateTimeImmutable;

class SessionService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Crea una sesión en BBDD y devuelve el token generado.
     */
    public function createSession(int $userId, ?string $userAgent, ?string $ipAddress, ?DateTimeImmutable $expiresAt = null): string
    {
        // Token aleatorio seguro
        $token = \bin2hex(\random_bytes(32));

        if ($expiresAt === null) {
            $expiresAt = new DateTimeImmutable('+7 days'); // por ejemplo, 7 días
        }

        $sql = 'INSERT INTO sessions (user_id, session_token, user_agent, ip_address, expires_at)
                VALUES (:user_id, :token, :user_agent, :ip_address, :expires_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':user_id'    => $userId,
            ':token'      => $token,
            ':user_agent' => $userAgent,
            ':ip_address' => $ipAddress,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /**
     * Devuelve el usuario asociado a un token de sesión, o null si no es válido.
     */
    public function findUserByToken(string $token): ?array
    {
        $sql = '
            SELECT u.*
            FROM sessions s
            JOIN users u ON u.id = s.user_id
            WHERE s.session_token = :token
              AND (s.expires_at IS NULL OR s.expires_at > NOW())
            LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Elimina una sesión concreta por token.
     */
    public function deleteSessionByToken(string $token): void
    {
        $sql = 'DELETE FROM sessions WHERE session_token = :token';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':token' => $token]);
    }

    /**
     * Opcional: eliminar todas las sesiones de un usuario (por ejemplo en logout global).
     */
    public function deleteSessionsByUser(int $userId): void
    {
        $sql = 'DELETE FROM sessions WHERE user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }
}
