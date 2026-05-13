# Module Spec: Sitemap

## Responsabilidad
Generar y mantener el sitemap.xml del sitio, actualizándolo automáticamente
ante cualquier cambio en posts o páginas.

## Dependencias
- Database (para consultar posts/pages publicados, cola de regeneración)
- PostRepository
- PageRepository

## API pública

```php
class SitemapGenerator {
    public function __construct(Database $db, PostRepository $posts, PageRepository $pages, string $baseUrl)

    // Generación
    public function generate(): string                     // Devuelve XML string
    public function write(): bool                          // Escribe public/sitemap.xml
    public function queueRegeneration(string $type, int $entryId, string $action): void
    // ↑ type: 'post'|'page', action: 'create'|'update'|'delete'

    // Consola/Admin
    public function processQueue(): int                    // Procesa cola pendiente
    public function hasPendingChanges(): bool              // Si hay cola pendiente
}

class SitemapEntry {
    public readonly string $loc;           // URL absoluta
    public readonly string $lastmod;       // Fecha ISO 8601
    public readonly string $priority;      // 0.0 - 1.0
}
```

## Reglas de negocio
*(Derivadas de sitemap.feature)*

1. **Contenido del sitemap**:
   - Home `/` → priority 1.0
   - Cada página publicada → priority 0.8
   - Cada post publicado → priority 0.6
   - Listado `/post` → priority 0.5

2. **Exclusiones**: posts/páginas en estado 'draft' NO aparecen en el sitemap
3. **Regeneración automática**: al crear, actualizar o eliminar un post/página, se añade una entrada a `sitemap_queue`
4. **Regeneración bajo demanda**: desde `/admin/ajustes` con un botón "Regenerar sitemap"
5. **Frecuencia**: la regeneración ocurre al finalizar la petición que provocó el cambio (no en background)
6. **Formato**: XML estándar de sitemaps con `xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"`
7. **Última modificación**: cada entrada incluye `<lastmod>` con la fecha ISO 8601 del post/página

## Estructura XML generada

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://maternatura.com/</loc>
    <lastmod>2025-05-12</lastmod>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://maternatura.com/luna-de-abril</loc>
    <lastmod>2025-05-11</lastmod>
    <priority>0.6</priority>
  </url>
</urlset>
```
