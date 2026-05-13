# Module Spec: Media (UploadHandler)

## Responsabilidad
Gestionar la subida, validación, procesamiento y servicio seguro de imágenes.

## Dependencias
- Security (validateImageMime, sanitizeFilename, isPathSafe)

## API pública

```php
class UploadHandler {
    public function __construct(string $uploadsDir, Security $security)

    // Subida
    public function upload(array $file, string $slug): UploadResult
    // ↑ $file = $_FILES['imagen']; $slug = slug del post/página
    // Devuelve la ruta relativa para guardar en BD

    // Servicio (para MediaController)
    public function serve(string $relativePath): void
    // ↑ Establece headers y sirve el archivo, o 404

    // Utilidades
    public function delete(string $relativePath): bool
    public function getImageUrl(string $relativePath): string
}

class UploadResult {
    public readonly bool $success;
    public readonly ?string $relativePath;       // Para guardar en image_url
    public readonly ?string $error;              // Mensaje de error
    public readonly ?int $width;
    public readonly ?int $height;
}
```

## Reglas de negocio
*(Derivadas de media.feature)*

1. **Formatos aceptados**: JPEG, PNG, WebP (MIME real verificado con `finfo`)
2. **Tamaño máximo**: 5 MB
3. **Redimensionado**: ancho máximo 1200px (manteniendo proporción), sin superar 1200px de alto
4. **Perfil de color**: convertir a sRGB (si es posible con GD/Imagick)
5. **Almacenamiento**: `uploads/images/YYYY/MM/{slug}.{ext}` — slug sanitizado, fecha de subida
6. **Seguridad**: el archivo se almacena fuera del document root (PARALELO a `public/`)
7. **Nomenclatura**: el nombre del archivo lo genera el sistema (slug + extensión original), nunca se usa el nombre del usuario
8. **Servicio**: via `MediaController` que lee de `uploads/` y sirve con headers adecuados
9. **Caché**: las imágenes servidas llevan `Cache-Control: public, max-age=86400` (1 día)
10. **Path traversal**: `realpath()` + validación de que el resolved path esté dentro de `uploads/`

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Imagen más pequeña que 1200px | No se redimensiona (se mantiene original) |
| Slug con espacios | El slug se sanitiza ANTES de usarse como nombre de archivo |
| El directorio YYYY/MM no existe | Se crea automáticamente |
| Sobrescritura de archivo | El slug del post es único, no debería ocurrir. Si ocurre, se sobreescribe |
| Imagen corrupta | GD/Imagick falla al procesar → UploadResult con error |
