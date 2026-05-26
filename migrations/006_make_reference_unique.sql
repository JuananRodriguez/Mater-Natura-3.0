-- Mater-Natura — Migración 006
-- Versión: 006
-- Descripción: reference obligatorio y único en la tabla posts
--
-- Requisito: ejecutar primero scripts/assign-references.php para
-- asignar referencias a todos los posts sin ella.

-- Eliminar el índice regular (lo reemplazamos por UNIQUE)
ALTER TABLE posts
    DROP INDEX idx_reference;

-- Hacer NOT NULL y añadir UNIQUE
ALTER TABLE posts
    MODIFY COLUMN reference VARCHAR(50) NOT NULL,
    ADD UNIQUE INDEX idx_reference (reference);
