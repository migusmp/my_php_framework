<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * ORM muy sencillo basado en PDO.
 *
 * No mapea aún a objetos concretos (User, Post, etc.),
 * sino que opera sobre:
 *  - nombre de tabla
 *  - clave primaria
 *  - arrays asociativos de datos
 *
 * Más adelante puedes construir una capa Model encima.
 */
final class ORM
{
    /**
     * Instancia única (singleton) para reutilizar la misma conexión.
     */
    private static ?self $instance = null;

    /**
     * Conexión PDO a la base de datos.
     */
    private PDO $pdo;

    /**
     * Constructor privado: obliga a usar ORM::getInstance().
     */
    private function __construct()
    {
        // Usamos tu clase Database que ya gestiona la conexión.
        $this->pdo = Database::getConnection();
    }

    /**
     * Obtiene la instancia global del ORM.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    // =========================================================
    // MÉTODOS BÁSICOS
    // =========================================================

    /**
     * Busca un registro por su ID.
     *
     * @param string          $table      Nombre de la tabla (ej: 'users')
     * @param string          $primaryKey Columna de clave primaria (ej: 'id')
     * @param int|string      $id         Valor de la clave primaria
     *
     * @return array<string,mixed>|null   Registro o null si no existe
     */
    public function find(string $table, string $primaryKey, int|string $id): ?array
    {
        $sql = "SELECT * FROM {$table} WHERE {$primaryKey} = :id LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Busca registros por criterios simples (AND).
     *
     * @param string                 $table    Tabla
     * @param array<string,mixed>    $criteria ['email' => 'foo@bar.com', 'status' => 'active']
     * @param int|null               $limit
     * @param int|null               $offset
     *
     * @return array<int, array<string,mixed>> Lista de filas
     */
    public function findBy(
        string $table,
        array $criteria = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        [$where, $params] = $this->buildWhere($criteria);

        $sql = "SELECT * FROM {$table} {$where}";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value);
        }

        $stmt->execute();

        /** @var array<int, array<string,mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * Inserta un registro y devuelve el ID generado (si lo hay).
     *
     * @param string               $table Tabla
     * @param array<string,mixed>  $data  ['name' => 'Miguel', 'email' => '...']
     *
     * @return int|string          ID insertado (según PDO::lastInsertId()).
     */
    public function insert(string $table, array $data): int|string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('No se pueden insertar datos vacíos.');
        }

        $columns = array_keys($data);

        // name, email, password
        $columnsSql = implode(', ', $columns);

        // :name, :email, :password
        $placeholders = implode(', ', array_map(
            static fn (string $col): string => ':' . $col,
            $columns
        ));

        $sql = "INSERT INTO {$table} ({$columnsSql}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $col => $value) {
            $stmt->bindValue(':' . $col, $value);
        }

        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un registro por ID.
     *
     * @param string               $table      Tabla
     * @param string               $primaryKey Clave primaria
     * @param int|string           $id         Valor de la PK
     * @param array<string,mixed>  $data       Datos a actualizar
     *
     * @return bool true si se actualizó (filas > 0)
     */
    public function update(string $table, string $primaryKey, int|string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // name = :name, email = :email
        $setParts = [];
        foreach ($data as $col => $value) {
            $setParts[] = "{$col} = :{$col}";
        }

        $setSql = implode(', ', $setParts);

        $sql = "UPDATE {$table} SET {$setSql} WHERE {$primaryKey} = :__id";

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $col => $value) {
            $stmt->bindValue(':' . $col, $value);
        }

        $stmt->bindValue(':__id', $id);

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina un registro por ID.
     */
    public function delete(string $table, string $primaryKey, int|string $id): bool
    {
        $sql  = "DELETE FROM {$table} WHERE {$primaryKey} = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // =========================================================
    // HELPERS INTERNOS
    // =========================================================

    /**
     * Construye el fragmento WHERE y los parámetros para una consulta.
     *
     * @param array<string,mixed> $criteria
     *
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildWhere(array $criteria): array
    {
        if (empty($criteria)) {
            return ['', []];
        }

        $parts  = [];
        $params = [];
        $i      = 0;

        foreach ($criteria as $column => $value) {
            // Generamos placeholder único: :w0, :w1, etc.
            $placeholder       = ':w' . $i++;
            $parts[]           = "{$column} = {$placeholder}";
            $params[$placeholder] = $value;
        }

        $whereSql = 'WHERE ' . implode(' AND ', $parts);

        return [$whereSql, $params];
    }

    // =========================================================
    // (Opcional) Acceso crudo a PDO para cosas avanzadas
    // =========================================================

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
