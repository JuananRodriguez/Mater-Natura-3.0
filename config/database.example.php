<?php
/**
 * Mater-Natura — Configuración de base de datos
 *
 * Copia este archivo como database.php y rellena tus credenciales.
 * database.php está en .gitignore (no se sube al repositorio).
 */

return [
    'host'     => getenv('MATER_DB_HOST') ?: 'localhost',
    'port'     => getenv('MATER_DB_PORT') ?: '3306',
    'dbname'   => getenv('MATER_DB_NAME') ?: 'mater_natura',
    'username' => getenv('MATER_DB_USER') ?: 'root',
    'password' => getenv('MATER_DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
