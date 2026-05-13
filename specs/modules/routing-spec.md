# Module Spec: Router

## Responsabilidad
Enrutar todas las peticiones HTTP al controlador y método adecuados.
Gestionar la resolución de slugs compartidos entre posts y páginas.

## Dependencias
- Ninguna (es el primer punto de entrada)

## API pública

```php
class Router {
    public function dispatch(string $method, string $uri): void
    public function get(string $path, callable $handler): void
    public function post(string $path, callable $handler): void
    public function addSlugRoute(callable $slugHandler): void
}
```

## Reglas de negocio

1. **Resolución de slugs compartidos**: Cuando la URI es `/{slug}`, el Router debe:
   a. Comprobar si el slug coincide con una ruta registrada (admin, login, post, etc.)
   b. Si no, delegar en el SlugHandler que busca primero en `posts` y luego en `pages`
   c. Si no encuentra en ninguna, devolver 404

2. **Rutas reservadas** (no pueden usarse como slug de post/page):
   `admin`, `login`, `logout`, `post`, `sitemap.xml`, `media`, `page`

3. **Método HTTP**: GET para visualización, POST para acciones con estado

4. **URI siempre termina sin slash**: `/post` sí, `/post/` no (301 redirect)

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Slug de post y página iguales | Post tiene prioridad (se muestra el post) |
| URI con `/../` intentando path traversal | Rechazar con 400 |
| URI con caracteres no ASCII | Normalizar a slug limpio |
| `/post` como slug de contenido | Rechazado por ser ruta reservada |
| Doble slash `//post` | Normalizar y redirect 301 |
