-- Mater-Natura — Migración Inicial
-- Versión: 001
-- Descripción: Tablas base del sistema

-- Tabla de migraciones (auto-creada por Database.php si no existe)
CREATE TABLE IF NOT EXISTS migrations (
    version INT UNSIGNED PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Usuarios
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50) NOT NULL UNIQUE,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin', 'editor') NOT NULL DEFAULT 'editor',
    mfa_secret      VARCHAR(32) DEFAULT NULL,
    last_login_ip   VARCHAR(45) DEFAULT NULL,
    last_login_at   DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Intentos de login fallidos (para rate limiting)
CREATE TABLE IF NOT EXISTS failed_logins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45) NOT NULL,
    username    VARCHAR(50) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_ip_time (ip_address, attempted_at),
    INDEX idx_username_ip (username, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts (entradas del blog)
CREATE TABLE IF NOT EXISTS posts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL COMMENT 'Texto poético de la entrada',
    image_url   VARCHAR(500) DEFAULT NULL COMMENT 'Ruta relativa desde /media/',
    template    ENUM('dark', 'light') NOT NULL DEFAULT 'dark',
    status      ENUM('published', 'draft') NOT NULL DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status_published (status, published_at),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Páginas estáticas
CREATE TABLE IF NOT EXISTS pages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    content     TEXT NOT NULL COMMENT 'Contenido HTML editable',
    template    ENUM('dark', 'light') NOT NULL DEFAULT 'dark',
    status      ENUM('published', 'draft') NOT NULL DEFAULT 'published',
    is_home     BOOLEAN NOT NULL DEFAULT FALSE,
    published_at DATETIME DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_is_home (is_home)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Plugins registrados
CREATE TABLE IF NOT EXISTS plugins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    version     VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    description TEXT DEFAULT NULL,
    enabled     BOOLEAN NOT NULL DEFAULT FALSE,
    settings    JSON DEFAULT NULL,
    installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hooks registrados por plugins
CREATE TABLE IF NOT EXISTS plugin_hooks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plugin_id   INT UNSIGNED NOT NULL,
    hook_name   VARCHAR(100) NOT NULL,
    priority    INT NOT NULL DEFAULT 10,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    INDEX idx_hook_name (hook_name),
    INDEX idx_plugin_hook (plugin_id, hook_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cola de regeneración del sitemap
CREATE TABLE IF NOT EXISTS sitemap_queue (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('post', 'page') NOT NULL,
    entry_id    INT UNSIGNED NOT NULL,
    action      ENUM('create', 'update', 'delete') NOT NULL,
    processed   BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processed (processed),
    INDEX idx_type_entry (type, entry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
