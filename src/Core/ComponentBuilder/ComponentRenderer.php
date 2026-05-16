<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder;

/**
 * Renderiza un array de definiciones de componentes a HTML.
 */
class ComponentRenderer
{
    public function __construct(private ComponentManager $manager)
    {
    }

    /**
     * Renderiza una colección de componentes a HTML secuencial.
     *
     * @param array  $components Array de [['type' => 'hero', 'settings' => [...], 'id' => '...'], ...]
     * @param string $theme      'light' | 'dark'
     */
    public function render(array $components, string $theme = 'light'): string
    {
        $html = '';
        foreach ($components as $component) {
            $type = $component['type'] ?? '';
            $settings = $component['settings'] ?? [];
            $comp = $this->manager->getComponent($type);
            if ($comp !== null) {
                $html .= $comp->render($settings, $theme) . "\n";
            }
        }
        return $html;
    }

    /**
     * Renderiza un solo componente (para preview en admin).
     */
    public function renderOne(string $type, array $settings, string $theme = 'light'): string
    {
        $comp = $this->manager->getComponent($type);
        if ($comp === null) {
            return "<div class=\"builder-error\">Componente '{$type}' no encontrado.</div>";
        }
        return $comp->render($settings, $theme);
    }
}
