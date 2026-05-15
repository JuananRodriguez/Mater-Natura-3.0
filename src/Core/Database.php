<?php

declare(strict_types=1);

namespace MaterNatura\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private PDO $pdo;

    public function __construct(
        private array $config,
    ) {
        $this->connect();
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'] ?? 3306,
            $this->config['dbname'],
            $this->config['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $params = []): int
    {
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$sets} WHERE {$where}";

        $stmt = $this->query($sql, array_merge(array_values($data), $params));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function runMigrations(): void
    {
        // Ensure migrations table exists
        $this->query(
            "CREATE TABLE IF NOT EXISTS migrations (
                version INT UNSIGNED PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $applied = $this->fetchAll("SELECT version FROM migrations ORDER BY version");
        $appliedVersions = array_column($applied, 'version');

        $migrationsDir = MATER_MIGRATIONS_DIR;
        if (!is_dir($migrationsDir)) {
            return;
        }

        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            preg_match('/(\d+)_/', basename($file), $matches);
            $version = (int) ($matches[1] ?? 0);

            if ($version === 0) {
                continue; // Saltar archivos sin número de versión
            }

            if (in_array($version, $appliedVersions, true)) {
                continue; // Ya aplicada
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                throw new \RuntimeException(
                    "Error ejecutando migración {$version}: " . $e->getMessage()
                );
            }

            // Registrar migración como aplicada
            $this->query(
                "INSERT INTO migrations (version, applied_at) VALUES (?, NOW())",
                [$version]
            );
        }
    }
}
