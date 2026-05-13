# Module Spec: Pages

## Responsabilidad
Gestionar el ciclo de vida de las páginas estáticas, incluyendo la protección
de la página de inicio.

## Dependencias
- Database
- Security
- Template

## API Pública — Controlador

```php
class PageController {
    public function __construct(Database $db, Security $security, Template $template)

    // Públicas
    public function show(string $slug): string           // GET /{slug} (page individual)
    public function showHome(): string                    // GET /

    // Admin (requieren autenticación)
    public function adminList(): string
    public function adminForm(?int $id = null): string
    public function adminSave(array $pageData): Redirect
    public function adminDelete(int $id): Redirect
    public function settings(): string                    // GET /admin/ajustes
    public function saveSettings(array $settings): Redirect
}
```

## API Pública — Repositorio

```php
interface PageRepository {
    public function findBySlug(string $slug): ?Page
    public function findById(int $id): ?Page
    public function findHomePage(): ?Page                 // Página configurada como home
    public function findAllPublished(): array             // Page[]
    public function findAllForAdmin(): array
    public function save(Page $page): Page
    public function delete(int $id): void
    public function isHomePage(int $id): bool
    public function setHomePage(int $id): void
    public function findAllPublishedForSitemap(): array
}
```

## Modelo

```php
class Page {
    public ?int $id;
    public int $userId;
    public string $title;
    public string $slug;
    public string $content;             // HTML editable
    public string $template;            // 'dark' | 'light'
    public string $status;              // 'published' | 'draft'
    public bool $isHome;                // Si es la página de inicio
    public ?DateTime $publishedAt;
    public ?DateTime $updatedAt;
}
```

## Reglas de negocio
*(Derivadas de pages.feature)*

1. **Home**: una página marcada como `isHome` se muestra en `/`. Solo una página puede ser home.
2. **Home protegida**: no se puede eliminar la página marcada como home. El botón de eliminar no aparece en el admin.
3. **Slugs reservados**: mismos que posts. Además, las páginas no pueden usar slugs existentes en posts (y viceversa).
4. **Contenido**: las páginas tienen contenido HTML (texto enriquecido), a diferencia de los posts que tienen descripción textual.
5. **Borradores**: inaccesibles públicamente (404).
6. **SEO**: og:type = 'website' para páginas.

## Configuración global

```php
class SiteSettings {
    public int $homePageId;             // ID de la página de inicio
    public string $siteName;            // Nombre del blog
    public string $siteDescription;     // Descripción para SEO
    public ?string $logoUrl;
    public ?string $faviconUrl;
    public string $defaultTemplate;     // 'dark' | 'light'
}
```
