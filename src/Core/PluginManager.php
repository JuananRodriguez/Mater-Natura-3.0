<?php

declare(strict_types=1);

namespace MaterNatura\Core;

use MaterNatura\Plugins\PluginInterface as PluginContract;

class PluginManager
{
    private string $pluginsDir;
    private array $activePlugins = [];

    public function __construct(
        private Database $db,
        private Security $security,
        private ?Auth $auth = null,
        ?string $pluginsDir = null,
    ) {
        $this->pluginsDir = $pluginsDir ?? MATER_PLUGINS_DIR;
    }

    public function discoverPlugins(): array
    {
        $plugins = [];

        if (!is_dir($this->pluginsDir)) {
            return $plugins;
        }

        $directories = glob($this->pluginsDir . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $pluginFile = $dir . '/Plugin.php';
            if (!file_exists($pluginFile)) {
                continue;
            }

            try {
                require_once $pluginFile;

                $className = $this->getPluginClassName($dir);
                if ($className === null || !class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                if (!$reflection->implementsInterface(PluginContract::class)) {
                    continue;
                }

                $instance = $reflection->newInstance();
                $this->injectDependencies($instance);
                $plugins[] = $instance;

            } catch (\Throwable $e) {
                error_log("Plugin error en {$dir}: " . $e->getMessage());
            }
        }

        return $plugins;
    }

    public function loadActivePlugins(): array
    {
        $activeRows = $this->db->fetchAll(
            "SELECT slug FROM plugins WHERE enabled = TRUE"
        );

        $activeSlugs = array_column($activeRows, 'slug');
        $allPlugins = $this->discoverPlugins();
        $active = [];

        foreach ($allPlugins as $plugin) {
            $meta = $plugin->getMeta();
            $slug = $meta['slug'] ?? '';

            if (in_array($slug, $activeSlugs, true)) {
                $active[] = $plugin;
            }
        }

        $this->activePlugins = $active;
        return $active;
    }

    public function executeHook(string $hookName, array $context = []): array
    {
        $results = [];

        $handlers = $this->db->fetchAll(
            "SELECT ph.*, p.slug as plugin_slug
             FROM plugin_hooks ph
             JOIN plugins p ON ph.plugin_id = p.id
             WHERE ph.hook_name = ? AND p.enabled = TRUE
             ORDER BY ph.priority ASC, p.slug ASC",
            [$hookName]
        );

        foreach ($handlers as $handler) {
            try {
                $plugin = $this->getPluginInstance($handler['plugin_slug']);
                if ($plugin === null) {
                    continue;
                }

                $method = $handler['handler_method'] ?? $this->getHandlerMethod($handler);
                if ($method && method_exists($plugin, $method)) {
                    $results[] = $plugin->$method($context);
                }
            } catch (\Throwable $e) {
                error_log("Hook '{$hookName}' error en plugin '{$handler['plugin_slug']}': " . $e->getMessage());
            }
        }

        return $results;
    }

    public function activate(string $slug): bool
    {
        $plugin = $this->getPluginInstance($slug);
        if ($plugin === null) {
            return false;
        }

        try {
            $plugin->onActivate();
        } catch (\Throwable $e) {
            error_log("Error al activar plugin '{$slug}': " . $e->getMessage());
            return false;
        }

        $meta = $plugin->getMeta();
        $existing = $this->db->fetchOne("SELECT id FROM plugins WHERE slug = ?", [$slug]);

        if ($existing) {
            $this->db->update('plugins', ['enabled' => 1], 'slug = ?', [$slug]);
        } else {
            $this->db->insert('plugins', [
                'name' => $meta['name'] ?? $slug,
                'slug' => $slug,
                'version' => $meta['version'] ?? '1.0.0',
                'description' => $meta['description'] ?? '',
                'enabled' => 1,
            ]);
        }

        $pluginRecord = $this->db->fetchOne("SELECT id FROM plugins WHERE slug = ?", [$slug]);
        if ($pluginRecord) {
            $pluginId = (int) $pluginRecord['id'];
            // Solo insertar hooks si no existen ya (ej. reactivación tras deactivación conservadora)
            $existingHooks = $this->db->fetchAll(
                "SELECT hook_name FROM plugin_hooks WHERE plugin_id = ?",
                [$pluginId]
            );
            $existingNames = array_column($existingHooks, 'hook_name');
            $hooks = $plugin->registerHooks();
            foreach ($hooks as $hook) {
                if (!in_array($hook['hook'], $existingNames, true)) {
                    $this->db->insert('plugin_hooks', [
                        'plugin_id' => $pluginId,
                        'hook_name' => $hook['hook'],
                        'priority' => $hook['priority'] ?? 10,
                    ]);
                }
            }
        }

        return true;
    }

    public function deactivate(string $slug): bool
    {
        $plugin = $this->getPluginInstance($slug);
        if ($plugin === null) {
            return false;
        }

        try {
            $plugin->onDeactivate();
        } catch (\Throwable $e) {
            error_log("Error al desactivar plugin '{$slug}': " . $e->getMessage());
        }

        $record = $this->db->fetchOne("SELECT id FROM plugins WHERE slug = ?", [$slug]);
        if ($record) {
            $this->db->update('plugins', ['enabled' => 0], 'id = ?', [(int) $record['id']]);
            // Los hooks se conservan en la BD — executeHook() filtra por p.enabled = TRUE
        }

        return true;
    }

    public function isActive(string $slug): bool
    {
        $record = $this->db->fetchOne(
            "SELECT enabled FROM plugins WHERE slug = ?",
            [$slug]
        );
        return $record && (bool) $record['enabled'];
    }

    public function getPlugin(string $slug): ?PluginContract
    {
        return $this->getPluginInstance($slug);
    }

    /**
     * Inyecta dependencias comunes en un plugin, si las acepta.
     */
    public function injectDependencies(PluginContract $plugin): void
    {
        if (method_exists($plugin, 'setDatabase')) {
            $plugin->setDatabase($this->db);
        }
        if (method_exists($plugin, 'setSecurity')) {
            $plugin->setSecurity($this->security);
        }
        if (method_exists($plugin, 'setAuth') && $this->auth !== null) {
            $plugin->setAuth($this->auth);
        }
    }

    public function getAllPlugins(): array
    {
        return $this->db->fetchAll("SELECT * FROM plugins ORDER BY name ASC");
    }

    // ─── Privados ───

    private function getPluginClassName(string $dir): ?string
    {
        $dirName = basename($dir);
        $pluginFile = $dir . '/Plugin.php';

        if (!file_exists($pluginFile)) {
            return null;
        }

        $content = file_get_contents($pluginFile);
        if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
            $namespace = $nsMatch[1];
            return $namespace . '\\Plugin';
        }

        return $dirName . '\\Plugin';
    }

    private function getPluginInstance(string $slug): ?PluginContract
    {
        $dir = $this->pluginsDir . '/' . ucfirst($slug);

        if (!is_dir($dir)) {
            return null;
        }

        $pluginFile = $dir . '/Plugin.php';
        if (!file_exists($pluginFile)) {
            return null;
        }

        require_once $pluginFile;

        $className = $this->getPluginClassName($dir);
        if ($className === null || !class_exists($className)) {
            return null;
        }

        try {
            $instance = new $className();
            if ($instance instanceof PluginContract) {
                $this->injectDependencies($instance);
                return $instance;
            }
        } catch (\Throwable $e) {
            error_log("Error al instanciar plugin '{$slug}': " . $e->getMessage());
        }

        return null;
    }

    private function getHandlerMethod(array $handler): string
    {
        // Por defecto, el método handler se deriva del hook
        // 'entry.render.after' → 'onEntryRenderAfter'
        return 'on' . str_replace(' ', '', ucwords(str_replace('.', ' ', $handler['hook_name'])));
    }
}
