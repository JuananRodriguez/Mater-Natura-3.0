# Module Spec: Posts

## Responsabilidad
Gestionar el ciclo de vida completo de los posts: creación, edición, publicación,
listado y visualización pública.

## Dependencias
- Database
- Security (escapeHtml, sanitizeSlug, isReservedSlug)
- Template
- UploadHandler (para la imagen)

## API Pública — Controlador

```php
class PostController {
    public function __construct(Database $db, Security $security, Template $template, UploadHandler $upload)

    // Públicas
    public function index(int $page = 1): string           // GET /post (listado)
    public function show(string $slug): string              // GET /{slug} (post individual)
    public function showNotFound(): string                  // 404

    // Admin (requieren autenticación)
    public function adminList(): string                     // GET /admin/posts
    public function adminForm(?int $id = null): string      // GET /admin/posts/editar
    public function adminSave(array $postData): Redirect     // POST /admin/posts/editar
    public function adminDelete(int $id): Redirect           // POST /admin/posts/eliminar
}
```

## API Pública — Repositorio

```php
interface PostRepository {
    public function findBySlug(string $slug): ?Post
    public function findById(int $id): ?Post
    public function findAllPublished(int $page = 1, int $perPage = 10): array  // Post[]
    public function countPublished(): int
    public function findAllForAdmin(int $page = 1): array   // Todos, incluidos borradores
    public function countAll(): int
    public function save(Post $post): Post                  // Create or update
    public function delete(int $id): void
    public function findAllPublishedForSitemap(): array     // Para el sitemap
}
```

## Modelo

```php
class Post {
    public ?int $id;
    public int $userId;
    public string $title;
    public string $slug;
    public string $description;         // El poema
    public ?string $imageUrl;
    public string $template;            // 'dark' | 'light'
    public string $status;              // 'published' | 'draft'
    public ?DateTime $publishedAt;
    public ?DateTime $updatedAt;
}
```

## Reglas de negocio
*(Derivadas de posts.feature)*

1. **Slug**: auto-generado desde el título (lowercase, guiones, sin acentos), editable manualmente
2. **Slug duplicado**: añadir sufijo numérico (`-1`, `-2`, etc.), excepción si es reservado
3. **Slugs reservados**: no se permiten como slug de post
4. **Imagen**: obligatoria, se procesa via UploadHandler antes de guardar
5. **Template**: cada post elige dark o light, se aplica al layout al renderizar
6. **Borradores**: no accesibles públicamente (404), visibles solo en admin
7. **Paginación**: 10 posts por página en /post
8. **SEO**: cada post genera meta title, meta description, og:image, og:type=article
9. **Publicación**: fecha de publicación se establece al cambiar de draft a published
10. **Sitemap**: al crear/actualizar/eliminar, se encola regeneración

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Slug cambiado en edición | La URL antigua deja de funcionar (no hay redirect automático) |
| Post publicado se vuelve draft | Desaparece del listado público, pero la URL da 404 |
| Listado sin posts publicados | Mensaje "Todavía no hay poemas publicados" |
| Página de listado más allá del total | Redirigir a última página válida |
