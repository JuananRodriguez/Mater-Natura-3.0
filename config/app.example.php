<?php
/**
 * Mater-Natura — Configuración de la aplicación
 *
 * Copia este archivo como app.php en config/ y personaliza los valores.
 * app.php está en .gitignore (no se sube al repositorio).
 */

return [
    'site_name' => 'Mater-Natura',
    'site_description' => 'Donde los versos encuentran su hogar',
    'base_url' => getenv('MATER_BASE_URL') ?: 'http://localhost:8080',

    // Entorno
    'debug' => getenv('MATER_DEBUG') === 'true',
    'env' => getenv('MATER_ENV') ?: 'development',

    // Sesión
    'session_lifetime' => 1800, // 30 min

    // Seguridad
    'rate_limit_attempts' => 5,
    'rate_limit_window' => 900, // 15 min

    // Imágenes
    'image_max_size' => 5 * 1024 * 1024,  // 5 MB
    'image_max_width' => 1200,
    'image_max_height' => 1200,
    'image_allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],

    // Blog
    'posts_per_page' => 10,

    // Slugs reservados
    'slug_reserved' => [
        'post', 'admin', 'login', 'logout', 'sitemap.xml',
        'media', 'page', 'home', 'index', 'rss.xml',
    ],
];
