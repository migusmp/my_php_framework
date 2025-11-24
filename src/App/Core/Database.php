<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase responsable de gestionar la conexión a la base de datos.
 *
 * Implementa el patrón Singleton para asegurarse de que solo exista
 * una única conexión PDO durante toda la petición.
 */
class Database
{
    /** @var PDO|null */
    private static ?PDO $connection = null;

    /**
     * Devuelve una instancia única de PDO.
     *
     * Uso típico:
     *   $pdo = Database::getConnection();
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        // Si no se ha creado aún, la creamos
        if (self::$connection === null) {

            // Construimos el DSN de MySQL
            $dsn = 'mysql:host=' . \DB_HOST .
                   ';port=' . \DB_PORT .
                   ';dbname=' . \DB_NAME .
                   ';charset=utf8mb4';

            try {
                // Creamos la instancia PDO
                self::$connection = new PDO($dsn, \DB_USER, \DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Excepciones en errores
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch por defecto
                    PDO::ATTR_EMULATE_PREPARES   => false,                  // Prepared reales
                ]);

            } catch (PDOException $e) {

                // ❗ En producción NO muestres esto al usuario ❗
                die('❌ Error de conexión a la base de datos: ' . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
