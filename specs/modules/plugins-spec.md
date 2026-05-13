# Module Spec: Plugin System

## Responsabilidad
Proveer un sistema de plugins extensible que permite añadir funcionalidad
al núcleo sin modificarlo, mediante hooks en puntos estratégicos.

## Dependencias
- Database

## API pública

```php
// ─── Interfaz que deben implementar todos los plugins ───

interface PluginInterface {
    public function getMeta(): array;
    // ['name' => string, 'version' => string, 'description' => string]

    public function registerHooks(): array;
    // [['hook' => 'entry.render.after', 'handler' => 'methodName', 'priority' => 10], ...]

    public function onActivate(): void;
    public function onDeactivate(): void;
}
```

```php
// ─── Gestor de plugins ───

class PluginManager {
    public function __construct(Database $db, string $pluginsDir)

    // Descubrimiento y carga
    public function discoverPlugins(): array               // PluginInterface[] (escanea directorio)
    public function loadActivePlugins(): array              // Solo los activados en BD
    public function getPlugin(string $slug): ?PluginInterface

    // Ciclo de vida
    public function activate(string $slug): bool
    public function deactivate(string $slug): bool
    public function isActive(string $slug): bool

    // Hooks
    public function executeHook(string $hookName, array $context = []): array
    // ↑ Ejecuta todos los handlers registrados para ese hook
    // Devuelve array de resultados (para que el núcleo los procese)

    // Admin
    public function getAllPlugins(): array                  // PluginInfo[] (para el panel)
}

class PluginInfo {
    public readonly string $name;
    public readonly string $slug;
    public readonly string $version;
    public readonly string $description;
    public readonly bool $enabled;
    public readonly ?array $settings;
}
```

## Hooks del núcleo

| Hook | Contexto | Momento |
|------|----------|---------|
| `entry.render.before` | `['post' => Post]` | Antes de renderizar el post en público |
| `entry.render.after` | `['post' => Post, 'html' => string]` | Después del contenido del post |
| `page.render.before` | `['page' => Page]` | Antes de renderizar la página |
| `page.render.after` | `['page' => Page, 'html' => string]` | Después del contenido de la página |
| `admin.menu.add` | `[]` | Construcción del menú de admin |
| `admin.entry.form` | `['post' => ?Post]` | Renderizado del formulario de post |
| `admin.settings.page` | `[]` | Página de configuración de plugins |
| `styles.enqueue` | `[]` | Para que plugins añadan sus CSS |
| `scripts.enqueue` | `[]` | Para que plugins añadan sus JS |
| `sitemap.generate` | `['entries' => &array]` | Durante la generación del sitemap |

## Reglas de negocio
*(Derivadas de plugins.feature)*

1. **Descubrimiento**: PluginManager escanea `src/Plugins/*/Plugin.php`. Cada plugin debe tener su propio directorio con una clase que implemente `PluginInterface`.
2. **Validación**: solo se cargan clases que implementan `PluginInterface`. Directorios sin la interfaz se ignoran silenciosamente.
3. **Ciclo de vida**: `onActivate()` se ejecuta al activar (crear tablas, etc.), `onDeactivate()` al desactivar (limpiar).
4. **Activación**: al activar, se registran los hooks en `plugin_hooks` y se marca `enabled = TRUE`.
5. **Desactivación**: al desactivar, se eliminan los hooks del plugin y se marca `enabled = FALSE`.
6. **Ejecución de hooks**: los handlers se ejecutan en orden de prioridad (menor número = primero). Si dos plugins tienen la misma prioridad, se ejecutan en orden alfabético.
7. **Aislamiento**: un error en un hook NO debe romper el sistema. Se captura la excepción, se loguea, y se continúa con el siguiente handler.
8. **Seguridad**: los plugins NO pueden modificar archivos del núcleo. Solo pueden actuar a través de hooks.
9. **Metadatos mínimos**: si `getMeta()` devuelve array incompleto, se muestran valores por defecto con advertencia visual.

## Estructura de un plugin

```
src/Plugins/MiPlugin/
├── Plugin.php              ← class MiPlugin implements PluginInterface
└── assets/
    ├── style.css           ← CSS propio (opcional)
    └── script.js           ← JS propio (opcional)
```

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Plugin depende de otro plugin | No soportado (cada plugin es independiente) |
| Plugin lanza excepción en onActivate() | Se captura, se muestra error, plugin no se activa |
| Dos plugins registran mismo hook con misma prioridad | Orden alfabético por slug del plugin |
| Plugin eliminado manualmente del directorio | Al escanear, desaparece. Si está activo en BD, se muestra como "Plugin no encontrado" |
| Directorio de plugin existe pero clase no implementa PluginInterface | Ignorado silenciosamente |
