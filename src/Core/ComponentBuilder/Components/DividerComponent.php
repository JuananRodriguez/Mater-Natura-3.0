<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class DividerComponent implements ComponentInterface
{
    public function getType(): string { return 'divider'; }
    public function getLabel(): string { return 'Separador'; }
    public function getIcon(): string { return 'divider'; }

    public function getDefaultSettings(): array
    {
        return [
            'style' => 'solid',
            'width' => '100',
            'thickness' => 1,
            'color' => '#cccccc',
            'margin_top' => 24,
            'margin_bottom' => 24,
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $style = $settings['style'] ?? 'solid';
        $widthPct = min(100, max(10, (int) ($settings['width'] ?? 100)));
        $thickness = max(1, (int) ($settings['thickness'] ?? 1));
        $color = htmlspecialchars($settings['color'] ?? '#cccccc', ENT_QUOTES, 'UTF-8');
        $marginTop = (int) ($settings['margin_top'] ?? 24);
        $marginBottom = (int) ($settings['margin_bottom'] ?? 24);

        $borderStyle = match ($style) {
            'dashed' => 'dashed',
            'dotted' => 'dotted',
            'double' => 'double',
            default => 'solid',
        };

        return '<hr class="component-divider" style="border:none;border-top:' . $thickness . 'px ' . $borderStyle . ' ' . $color . ';width:' . $widthPct . '%;margin:' . $marginTop . 'px auto ' . $marginBottom . 'px;">';
    }

    public function renderForm(array $settings): string
    {
        $style = $settings['style'] ?? 'solid';
        $width = (int) ($settings['width'] ?? 100);
        $thickness = (int) ($settings['thickness'] ?? 1);
        $color = htmlspecialchars($settings['color'] ?? '#cccccc', ENT_QUOTES, 'UTF-8');
        $marginTop = (int) ($settings['margin_top'] ?? 24);
        $marginBottom = (int) ($settings['margin_bottom'] ?? 24);

        return '
        <div class="builder-field">
            <label class="builder-label">Estilo</label>
            <select class="builder-select" name="settings[style]">
                <option value="solid"' . ($style === 'solid' ? ' selected' : '') . '>Sólido</option>
                <option value="dashed"' . ($style === 'dashed' ? ' selected' : '') . '>Discontinuo</option>
                <option value="dotted"' . ($style === 'dotted' ? ' selected' : '') . '>Punteado</option>
                <option value="double"' . ($style === 'double' ? ' selected' : '') . '>Doble</option>
            </select>
        </div>
        <div class="builder-field-row">
            <div class="builder-field builder-field--half">
                <label class="builder-label">Ancho (%)</label>
                <input type="number" class="builder-input" name="settings[width]" value="' . $width . '" min="10" max="100">
            </div>
            <div class="builder-field builder-field--half">
                <label class="builder-label">Grosor (px)</label>
                <input type="number" class="builder-input" name="settings[thickness]" value="' . $thickness . '" min="1" max="20">
            </div>
        </div>
        <div class="builder-field">
            <label class="builder-label">Color</label>
            <div class="builder-field-row" style="gap:8px;align-items:center;">
                <input type="color" class="builder-input" name="settings[color]" value="' . $color . '" style="width:48px;padding:4px;flex:0 0 auto;">
                <span style="font-size:12px;color:#999;">' . $color . '</span>
            </div>
        </div>
        <div class="builder-field-row">
            <div class="builder-field builder-field--half">
                <label class="builder-label">Margen superior (px)</label>
                <input type="number" class="builder-input" name="settings[margin_top]" value="' . $marginTop . '" min="0" max="200">
            </div>
            <div class="builder-field builder-field--half">
                <label class="builder-label">Margen inferior (px)</label>
                <input type="number" class="builder-input" name="settings[margin_bottom]" value="' . $marginBottom . '" min="0" max="200">
            </div>
        </div>';
    }

    public function validate(array $settings): array
    {
        return [];
    }
}
