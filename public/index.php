<?php
/**
 * Mater-Natura — Front Controller
 *
 * Todas las peticiones HTTP entran aquí vía .htaccess (URL rewriting).
 */

declare(strict_types=1);

// ─── Autoload ───
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Ejecuta "composer install" primero.');
}
require $autoload;

// ─── Cargar configuración ───
$configDir = dirname(__DIR__) . '/config';

// Config de BD (puede no existir aún)
$dbConfigFile = $configDir . '/database.php';
if (!file_exists($dbConfigFile)) {
    die('Copia config/database.example.php a config/database.php y rellena las credenciales.');
}
$dbConfig = require $dbConfigFile;

// Config de app
$appConfigFile = $configDir . '/app.php';
$appConfig = [];
if (file_exists($appConfigFile)) {
    $appConfig = require $appConfigFile;
}

// ─── Constantes ───
define('MATER_ROOT_DIR', dirname(__DIR__));
define('MATER_PUBLIC_DIR', MATER_ROOT_DIR . '/public');
define('MATER_UPLOADS_DIR', MATER_ROOT_DIR . '/uploads');
define('MATER_TEMPLATES_DIR', MATER_ROOT_DIR . '/src/Templates');
define('MATER_PLUGINS_DIR', MATER_ROOT_DIR . '/src/Plugins');
define('MATER_CONFIG_DIR', MATER_ROOT_DIR . '/config');

// ─── Helpers globales ───
require MATER_ROOT_DIR . '/src/Core/helpers.php';
define('MATER_MIGRATIONS_DIR', MATER_ROOT_DIR . '/migrations');

// Valores por defecto
define('MATER_SITE_NAME', $appConfig['site_name'] ?? 'Mater-Natura');
define('MATER_SITE_DESCRIPTION', $appConfig['site_description'] ?? 'Donde los versos encuentran su hogar');
define('MATER_BASE_URL', $appConfig['base_url'] ?? 'http://localhost');
define('MATER_DEBUG', $appConfig['debug'] ?? false);
define('MATER_ENV', $appConfig['env'] ?? 'development');
define('MATER_SESSION_LIFETIME', $appConfig['session_lifetime'] ?? 1800);
define('MATER_RATE_LIMIT_ATTEMPTS', $appConfig['rate_limit_attempts'] ?? 5);
define('MATER_RATE_LIMIT_WINDOW', $appConfig['rate_limit_window'] ?? 900); // 15 min
define('MATER_SLUG_RESERVED', $appConfig['slug_reserved'] ?? [
    'post', 'admin', 'login', 'logout', 'sitemap.xml', 'media', 'page', 'home', 'rss.xml',
]);
define('MATER_IMAGE_MAX_SIZE', $appConfig['image_max_size'] ?? 5 * 1024 * 1024);
define('MATER_IMAGE_MAX_WIDTH', $appConfig['image_max_width'] ?? 1200);
define('MATER_IMAGE_MAX_HEIGHT', $appConfig['image_max_height'] ?? 1200);
define('MATER_IMAGE_QUALITY', $appConfig['image_quality'] ?? 80);
define('MATER_IMAGE_ALLOWED_TYPES', $appConfig['image_allowed_types'] ?? [
    'image/jpeg',
    'image/png',
    'image/webp',
]);
define('MATER_POSTS_PER_PAGE', $appConfig['posts_per_page'] ?? 10);

// ─── Iniciar aplicación ───
$app = new \MaterNatura\Core\App($appConfig, $dbConfig);
$app->run();
