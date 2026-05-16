<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class SpacerComponent implements ComponentInterface
{
    public function getType(): string { return 'spacer'; }
    public function getLabel(): string { return 'Espaciador'; }
    public function getIcon(): string { return 'spacer'; }

    public function getDefaultSettings(): array
    {
        return [
            'height' => 40,
            'show_grid' => false,
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $height = max(4, (int) ($settings['height'] ?? 40));
        $showGrid = !empty($settings['show_grid']);

        $class = 'component-spacer' . ($showGrid ? ' spacer-grid' : '');
        return '<div class="' . $class . '" style="height:' . $height . 'px;' . ($showGrid ? 'background:repeating-linear-gradient(90deg,transparent,transparent 10px,#f0f0f0 10px,#f0f0f0 11px);' : '') . '" aria-hidden="true"></div>';
    }

    public function renderForm(array $settings): string
    {
        $height = (int) ($settings['height'] ?? 40);
        $showGrid = !empty($settings['show_grid']);

        return '
        <div class="builder-field">
            <label class="builder-label">Altura (px)</label>
            <div class="builder-range-wrapper">
                <input type="range" class="builder-range" name="settings[height]" min="4" max="300" value="' . $height . '">
                <span class="builder-range-value">' . $height . 'px</span>
            </div>
        </div>
        <div class="builder-field">
            <label class="builder-label">
                <input type="checkbox" name="settings[show_grid]" value="1"' . ($showGrid ? ' checked' : '') . '> Mostrar guía visual
            </label>
            <p style="font-size:11px;color:#999;margin-top:2px;">Solo visible en el editor, no en el frontend.</p>
        </div>';
    }

    public function validate(array $settings): array
    {
        return [];
    }
}
