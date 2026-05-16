-- Component Builder: columna JSON para contenido estructurado por componentes
-- Añade content_components y builder_version a pages y posts

ALTER TABLE pages
    ADD COLUMN content_components JSON DEFAULT NULL AFTER content,
    ADD COLUMN builder_version INT DEFAULT 1 AFTER content_components;

ALTER TABLE posts
    ADD COLUMN content_components JSON DEFAULT NULL AFTER description,
    ADD COLUMN builder_version INT DEFAULT 1 AFTER content_components;
