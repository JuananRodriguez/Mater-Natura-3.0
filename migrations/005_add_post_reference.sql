-- Mater-Natura — Migración 005
-- Versión: 005
-- Descripción: Añade columna `reference` a la tabla posts para numeración de catálogo

ALTER TABLE posts
    ADD COLUMN reference VARCHAR(50) DEFAULT NULL COMMENT 'Referencia numérica para catálogo PDF'
    AFTER slug;

-- Index para filtrar por referencia
ALTER TABLE posts
    ADD INDEX idx_reference (reference);
