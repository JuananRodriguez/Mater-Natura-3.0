-- Comments Plugin — Tabla de comentarios
-- Soporta respuestas anidadas (parent_id)

CREATE TABLE IF NOT EXISTS comments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id     INT UNSIGNED NOT NULL,
    parent_id   INT UNSIGNED DEFAULT NULL,
    author_name VARCHAR(100) NOT NULL DEFAULT 'Anónimo',
    author_email VARCHAR(100) DEFAULT NULL,
    author_website VARCHAR(255) DEFAULT NULL,
    content     TEXT NOT NULL,
    status      ENUM('pending', 'approved', 'spam') NOT NULL DEFAULT 'pending',
    ip_address  VARCHAR(45) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL,
    INDEX idx_post_status (post_id, status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
