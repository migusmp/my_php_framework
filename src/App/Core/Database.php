<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    /**
     * Devuelve una única instancia de PDO (singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host    = config('database.host');
        $port    = config('database.port');
        $dbname  = config('database.name');
        $user    = config('database.user');
        $pass    = config('database.password');
        $charset = config('database.charset', 'utf8mb4');

        // DEBUG:
        /* var_dump([ */
        /*     'host'    => $host, */
        /*     'port'    => $port, */
        /*     'dbname'  => $dbname, */
        /*     'user'    => $user, */
        /*     'pass'    => $pass, */
        /*     'dsn'     => "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}", */
        /* ]); */
        /* die(); */

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            return self::$connection;

        } catch (PDOException $e) {
            throw new RuntimeException(
                '❌ Error de conexión a la base de datos: ' . $e->getMessage()
            );
        }
    }
}
