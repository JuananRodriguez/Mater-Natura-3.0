# Module Spec: Database

## Responsabilidad
Proveer acceso a MySQL vía PDO con prepared statements en absoluto todas las consultas.
Manejar la conexión, migraciones y el mapeo básico de resultados.

## Dependencias
- Extensión PHP `pdo_mysql`

## API pública

```php
class Database {
    public function __construct(array $config)
    public function query(string $sql, array $params = []): PDOStatement
    public function fetchOne(string $sql, array $params = []): ?array
    public function fetchAll(string $sql, array $params = []): array
    public function insert(string $table, array $data): int      // Returns lastInsertId
    public function update(string $table, array $data, string $where, array $params): int
    public function delete(string $table, string $where, array $params): int
    public function getPdo(): PDO
    public function runMigrations(): void
    public function getMigrationVersion(): int
}
```

## Reglas de negocio

1. Todas las consultas usan **prepared statements** (nunca concatenación de strings SQL)
2. Las migraciones se ejecutan en orden numérico (`001_`, `002_`, etc.)
3. Cada migración se registra en la tabla `migrations` después de ejecutarse
4. `runMigrations()` solo ejecuta migraciones no aplicadas aún
5. El charset de conexión es `utf8mb4`

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Conexión falla | Lanza `DatabaseException` con mensaje descriptivo |
| Migración falla a mitad | No se registra como ejecutada, se puede reintentar |
| Tabla migrations no existe | Se crea automáticamente en la primera migración |
| Consulta con 0 resultados | `fetchOne` devuelve `null`, `fetchAll` devuelve `[]` |
| Inyección SQL via params | Imposible por prepared statements (PDO) |
