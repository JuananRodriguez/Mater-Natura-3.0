# Module Spec: Template

## Responsabilidad
Renderizar las vistas PHP con los datos proporcionados, aplicando el layout
(dark o light) y los partials comunes.

## Dependencias
- Security (para escapeHtml)

## API pública

```php
class Template {
    public function __construct(string $templatesDir, Security $security)

    // Renderizado
    public function render(string $view, array $data = [], string $layout = 'dark'): string
    public function renderPartial(string $partial, array $data = []): string
    public function setLayout(string $layout): void

    // Datos compartidos (window.__MATER_DATA__)
    public function exposeToJs(string $key, mixed $value): void
    public function getJsDataScript(): string             // Genera el <script>

    // SEO
    public function setMetaTitle(string $title): void
    public function setMetaDescription(string $description): void
    public function setOgImage(string $url): void
    public function setOgType(string $type): void         // article | website
    public function renderMetaTags(): string
}
```

## Reglas de negocio

1. **Layout**: dark o light. Cada layout incluye header, footer, meta tags, y Alpine.js
2. **SEO**: Cada página renderizada debe tener title, meta description, og:image (si aplica), og:type
3. **window.__MATER_DATA__**: Los datos expuestos via `exposeToJs()` se renderizan como JSON en un `<script>` al final del `<head>`, accesibles para Alpine.js
4. **Toda salida de datos del usuario** debe pasar por `escapeHtml()`
5. **Partials**: header, footer, head se incluyen automáticamente en el layout
6. **Vista 404**: layout normal con contenido de error, código HTTP 404

## Estructura de layouts

```
Templates/
├── layouts/
│   ├── dark.php       ← <body class="theme-dark">
│   └── light.php      ← <body class="theme-light">
├── partials/
│   ├── header.php     ← <nav> con logo + enlaces
│   ├── footer.php     ← <footer> con créditos
│   └── head.php       ← <head> completo con meta tags + CSS + JS
├── home.php
├── post-single.php
├── post-list.php
├── page.php
├── admin/
│   ├── layout.php     ← Layout del panel (con menú admin)
│   ├── dashboard.php
│   ├── post-form.php
│   ├── post-list.php
│   ├── page-form.php
│   ├── page-list.php
│   ├── plugins.php
│   └── login.php
└── 404.php
```
