<?php
/**
 * Router para el servidor de desarrollo PHP built-in.
 * Emula las reglas de reescritura de .htaccess.
 * Sirve archivos estáticos desde public/ con tipos MIME correctos.
 *
 * Uso: php -S localhost:8080 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicDir = __DIR__ . '/public';
$filePath = $publicDir . $uri;

// Mapeo de extensiones a MIME types
$mimeTypes = [
    'gif'  => 'image/gif',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'json' => 'application/json',
    'woff' => 'font/woff',
    'woff2'=> 'font/woff2',
    'ttf'  => 'font/ttf',
    'pdf'  => 'application/pdf',
    'zip'  => 'application/zip',
    'xml'  => 'application/xml',
];

// Si el archivo existe en public/, servirlo directamente
if ($uri !== '/' && file_exists($filePath) && is_file($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    } else {
        // Detección automática para tipos no listados
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . finfo_file($finfo, $filePath));
        finfo_close($finfo);
    }
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400');
    readfile($filePath);
    return true;
}

// Todo lo demás va a index.php
require $publicDir . '/index.php';
