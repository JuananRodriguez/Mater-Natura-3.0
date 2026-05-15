-- Mater-Natura — Migración 002
-- Versión: 002
-- Descripción: Añade columnas SEO a posts y pages

ALTER TABLE posts
    ADD COLUMN meta_title VARCHAR(170) DEFAULT NULL AFTER image_url,
    ADD COLUMN meta_description VARCHAR(320) DEFAULT NULL AFTER meta_title;

ALTER TABLE pages
    ADD COLUMN meta_title VARCHAR(170) DEFAULT NULL AFTER content,
    ADD COLUMN meta_description VARCHAR(320) DEFAULT NULL AFTER meta_title;
