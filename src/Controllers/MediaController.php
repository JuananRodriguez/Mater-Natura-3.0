<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Security;

class MediaController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Sirve imágenes desde uploads/ de forma segura.
     * Se llama cuando la ruta coincide con /media/{path}
     */
    public function serve(string $path): void
    {
        // Normalizar y validar path
        $path = ltrim($path, '/');
        $fullPath = MATER_UPLOADS_DIR . '/' . $path;

        // Validar que el path está dentro de uploads/
        if (!$this->security->isPathSafe($fullPath, MATER_UPLOADS_DIR)) {
            $this->notFound();
            return;
        }

        if (!file_exists($fullPath) || is_dir($fullPath)) {
            $this->notFound();
            return;
        }

        // Detectar MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fullPath);

        $allowedMimes = MATER_IMAGE_ALLOWED_TYPES;
        if (!in_array($mime, $allowedMimes, true)) {
            $this->notFound();
            return;
        }

        // Cabeceras de caché
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=86400');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
        header('X-Content-Type-Options: nosniff');

        readfile($fullPath);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);

        // Intentar servir imagen por defecto
        $default = MATER_PUBLIC_DIR . '/assets/images/no-disponible.jpg';
        if (file_exists($default)) {
            header('Content-Type: image/jpeg');
            readfile($default);
        }
        exit;
    }
}
