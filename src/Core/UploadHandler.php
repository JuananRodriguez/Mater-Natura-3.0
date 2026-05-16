<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class UploadHandler
{
    public function __construct(
        private Security $security,
    ) {
    }

    /**
     * Subir una imagen y devolver la ruta relativa.
     * Genera un nombre de archivo único para evitar conflictos de caché.
     *
     * @param array $file $_FILES['image']
     * @param string $slug Slug del post/página (se usa como prefijo del nombre)
     * @return array{success: bool, relativePath: ?string, error: ?string, width: ?int, height: ?int}
     */
    public function upload(array $file, string $slug): array
    {
        // 1. Validar que no hubo error de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo de 5 MB.',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ninguna imagen.',
                default => 'Error al subir la imagen.',
            };
            return ['success' => false, 'relativePath' => null, 'error' => $errorMessage, 'width' => null, 'height' => null];
        }

        // 2. Validar tamaño
        if ($file['size'] > MATER_IMAGE_MAX_SIZE) {
            return ['success' => false, 'relativePath' => null, 'error' => 'La imagen supera el tamaño máximo de 5 MB.', 'width' => null, 'height' => null];
        }

        // 3. Validar MIME real
        $mime = $this->security->validateImageMime($file['tmp_name']);
        if ($mime === null) {
            return ['success' => false, 'relativePath' => null, 'error' => 'Formato de imagen no permitido. Usa JPEG, PNG o WebP.', 'width' => null, 'height' => null];
        }

        // 4. Todo se convierte a WebP para optimizar peso
        $extension = 'webp';

        // 5. Crear directorio YYYY/MM
        $yearMonth = date('Y/m');
        $uploadDir = MATER_UPLOADS_DIR . '/images/' . $yearMonth;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                return ['success' => false, 'relativePath' => null, 'error' => 'Error al crear el directorio de subida.', 'width' => null, 'height' => null];
            }
        }

        // 6. Nombre de archivo único: slug + timestamp hex + sufijo aleatorio
        //    Esto asegura que cada subida tenga una URL distinta,
        //    evitando problemas de caché del navegador al actualizar la imagen.
        $safeSlug = $this->security->sanitizeSlug($slug);
        $uniqueSuffix = dechex(time()) . '-' . bin2hex(random_bytes(4));
        $filename = $safeSlug . '-' . $uniqueSuffix . '.' . $extension;
        $destPath = $uploadDir . '/' . $filename;

        // 7. Procesar imagen (redimensionar si es necesario)
        $result = $this->processImage($file['tmp_name'], $destPath, $mime);

        if (!$result['success']) {
            return $result;
        }

        // 8. Ruta relativa para guardar en BD
        $relativePath = 'images/' . $yearMonth . '/' . $filename;

        return [
            'success' => true,
            'relativePath' => $relativePath,
            'error' => null,
            'width' => $result['width'],
            'height' => $result['height'],
        ];
    }

    /**
     * Servir una imagen (delegado en MediaController)
     */
    public function serve(string $relativePath): void
    {
        $fullPath = MATER_UPLOADS_DIR . '/' . ltrim($relativePath, '/');

        if (!file_exists($fullPath)) {
            http_response_code(404);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($fullPath);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=86400');

        readfile($fullPath);
    }

    /**
     * Eliminar una imagen
     */
    public function delete(string $relativePath): bool
    {
        $fullPath = MATER_UPLOADS_DIR . '/' . ltrim($relativePath, '/');

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true; // Ya no existe
    }

    /**
     * Obtener URL pública de una imagen
     */
    public function getImageUrl(string $relativePath): string
    {
        return MATER_BASE_URL . '/media/' . ltrim($relativePath, '/');
    }

    /**
     * Redimensionar y convertir a WebP con calidad optimizada.
     * Siempre re-encodifica para maximizar compresión y limpiar metadatos EXIF.
     */
    private function processImage(string $sourcePath, string $destPath, string $mime): array
    {
        [$origWidth, $origHeight] = getimagesize($sourcePath);

        if (!$origWidth || !$origHeight) {
            return ['success' => false, 'error' => 'No se pudieron leer las dimensiones de la imagen.', 'width' => null, 'height' => null];
        }

        $maxWidth = MATER_IMAGE_MAX_WIDTH;
        $maxHeight = MATER_IMAGE_MAX_HEIGHT;

        // Calcular nuevas dimensiones (siempre re-encodificamos)
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $newWidth = (int) round($origWidth * $ratio);
            $newHeight = (int) round($origHeight * $ratio);
        }

        // Crear imagen desde origen
        $srcImage = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$srcImage) {
            return ['success' => false, 'error' => 'No se pudo procesar la imagen.', 'width' => null, 'height' => null];
        }

        // Redimensionar
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia (fuentes PNG/WebP con canal alfa)
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Siempre guardar como WebP con calidad optimizada
        // La conversión a WebP elimina metadatos EXIF automáticamente
        $saved = imagewebp($dstImage, $destPath, MATER_IMAGE_QUALITY);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (!$saved) {
            return ['success' => false, 'error' => 'Error al guardar la imagen procesada.', 'width' => null, 'height' => null];
        }

        return ['success' => true, 'width' => $newWidth, 'height' => $newHeight];
    }
}
