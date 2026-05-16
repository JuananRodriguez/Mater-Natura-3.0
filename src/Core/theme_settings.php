<?php
/**
 * Mater-Natura — Theme Settings
 *
 * Carga y cachea los ajustes del theme desde la base de datos.
 * Se inicializa desde App.php con set_theme_db().
 */

declare(strict_types=1);

use MaterNatura\Core\Database;

/**
 * Almacena la referencia a la base de datos para carga de settings del theme.
 */
function set_theme_db(Database $db): void
{
    $GLOBALS['_theme_db'] = $db;
}

/**
 * Obtiene un ajuste del theme por clave.
 *
 * @param string $key Nombre del setting (theme_logo, theme_nav, theme_social, theme_footer)
 * @return mixed Valor del setting (array o null si no existe)
 */
function theme_setting(string $key): mixed
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        $db = $GLOBALS['_theme_db'] ?? null;
        if ($db === null) {
            return null;
        }
        $rows = $db->fetchAll("SELECT `key`, `value` FROM settings");
        foreach ($rows as $row) {
            $cache[$row['key']] = json_decode($row['value'], true);
        }
    }

    return $cache[$key] ?? null;
}

/**
 * Obtiene la referencia a la base de datos del theme.
 */
function get_theme_db(): ?Database
{
    return $GLOBALS['_theme_db'] ?? null;
}

/**
 * Renderiza un icono SVG para una red social.
 * Usado en header/footer del frontend.
 */
function social_svg(string $platform): string
{
    $icons = [
        'instagram' => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><rect height="20" rx="5" ry="5" width="20" x="2" y="2"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>',
        'twitter'   => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M4 4l11.733 16h4.267l-11.733 -16zM4 20l6.768 -6.768M20 4l-6.768 6.768"/></svg>',
        'facebook'  => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M18 2h-3a5 5 0 0 0 -5 5v3H7v4h3v8h4v-8h3l1 -4h-4V7a1 1 0 0 1 1 -1h3z"/></svg>',
        'youtube'   => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><rect height="14" rx="4" width="20" x="2" y="5"/><path d="M10 9l5 3l-5 3z"/></svg>',
        'tiktok'    => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2V3h4.034A6.963 6.963 0 0 0 21 7.917z"/></svg>',
        'github'    => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0 -.94 -2.61c3.14 -.35 6.44 -1.54 6.44 -7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73 .65 16 2.48a13.38 13.38 0 0 0 -7 0C6.27 .65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0 -1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>',
        'linkedin'  => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><rect height="14" rx="2" width="4" x="4" y="9"/><rect height="7" rx="2" width="4" x="16" y="11"/><circle cx="9" cy="7" r="2"/><path d="M4 9h4v4H4z"/></svg>',
        'pinterest' => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="16" y2="12"/><line x1="8" x2="16" y1="12" y2="12"/></svg>',
        'bluesky'   => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M12 13c-1.5 -1.5 -4 -3 -7 -3c-2 0 -3 1.5 -3 3c0 4 3 5 7 5c4 0 7 -1 7 -5c0 -1.5 -1 -3 -3 -3c-3 0 -5.5 1.5 -7 3z"/><path d="M12 13c1.5 -1.5 4 -3 7 -3c2 0 3 1.5 3 3c0 4 -3 5 -7 5c-4 0 -7 -1 -7 -5c0 -1.5 1 -3 3 -3c3 0 5.5 1.5 7 3z"/></svg>',
        'mastodon'  => '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M18.648 15.254c-1.816 1.473 -6.648 1.746 -6.648 1.746s-4.832 -.273 -6.648 -1.746c-1.422 -1.154 -1.352 -2.564 -1.352 -3.754c0 -.065 .003 -.128 .008 -.19c.058 -1.33 .472 -5.31 1.224 -6.432c1.164 -1.71 2.768 -2.332 4.768 -2.332c0 0 2.588 0 4 .5l.175 .5c-1.82 .44 -3.5 1.21 -3.5 3.5c0 6 4.5 6 6 6s3 -1 3 -2c0 -3 -2 -4 -2 -7c0 -3 -2 -4 -5 -4c-2 0 -3 1 -3 1s0 -1 1 -2c2 -1 5 -1 7 0c2 1 3 3 3 7c0 3 -.5 5 -2.5 6.5z"/></svg>',
    ];

    if (isset($icons[$platform])) {
        return $icons[$platform];
    }

    // Fallback: icono genérico de enlace
    return '<svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M10 13a5 5 0 0 0 7.54 .54l3 -3a5 5 0 0 0 -7.07 -7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0 -7.54 -.54l-3 3a5 5 0 0 0 7.07 7.07l1.71 -1.71"/></svg>';
}

/**
 * Guarda un ajuste del theme en la base de datos.
 */
function save_theme_setting(Database $db, string $key, mixed $value): void
{
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $db->query(
        "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
        [$key, $json]
    );
}
