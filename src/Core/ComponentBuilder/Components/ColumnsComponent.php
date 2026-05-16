<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class ColumnsComponent implements ComponentInterface
{
    public function getType(): string { return 'columns'; }
    public function getLabel(): string { return 'Columnas'; }
    public function getIcon(): string { return 'columns'; }

    public function getDefaultSettings(): array
    {
        return [
            'columns' => 2,
            'ratio' => '50-50',
            'left_content' => '',
            'right_content' => '',
            'center_content' => '',
            'gap' => 24,
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $numCols = max(1, min(3, (int) ($settings['columns'] ?? 2)));
        $ratio = $settings['ratio'] ?? '50-50';
        $gap = (int) ($settings['gap'] ?? 24);

        $left = htmlspecialchars($settings['left_content'] ?? '', ENT_QUOTES, 'UTF-8');
        $right = htmlspecialchars($settings['right_content'] ?? '', ENT_QUOTES, 'UTF-8');
        $center = htmlspecialchars($settings['center_content'] ?? '', ENT_QUOTES, 'UTF-8');

        // Convert ratio to flex basis
        $flexLeft = '1';
        $flexRight = '1';
        if ($numCols === 2) {
            if ($ratio === '33-67') { $flexLeft = '1'; $flexRight = '2'; }
            elseif ($ratio === '67-33') { $flexLeft = '2'; $flexRight = '1'; }
            else { $flexLeft = '1'; $flexRight = '1'; }
        }

        $style = ' style="display:flex;gap:' . $gap . 'px;flex-wrap:wrap;"';

        $html = '<div class="component-columns"' . $style . '>';
        if ($numCols === 1) {
            $html .= '<div class="col" style="flex:1;min-width:0;">' . nl2br($left) . '</div>';
        } elseif ($numCols === 2) {
            $html .= '<div class="col" style="flex:' . $flexLeft . ';min-width:200px;">' . nl2br($left) . '</div>';
            $html .= '<div class="col" style="flex:' . $flexRight . ';min-width:200px;">' . nl2br($right) . '</div>';
        } else {
            $html .= '<div class="col" style="flex:1;min-width:200px;">' . nl2br($left) . '</div>';
            $html .= '<div class="col" style="flex:1;min-width:200px;">' . nl2br($center) . '</div>';
            $html .= '<div class="col" style="flex:1;min-width:200px;">' . nl2br($right) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderForm(array $settings): string
    {
        $numCols = (int) ($settings['columns'] ?? 2);
        $ratio = $settings['ratio'] ?? '50-50';
        $gap = (int) ($settings['gap'] ?? 24);
        $left = htmlspecialchars($settings['left_content'] ?? '', ENT_QUOTES, 'UTF-8');
        $right = htmlspecialchars($settings['right_content'] ?? '', ENT_QUOTES, 'UTF-8');
        $center = htmlspecialchars($settings['center_content'] ?? '', ENT_QUOTES, 'UTF-8');

        $ratioOptions = '';
        $ratios = ['50-50' => '50% / 50%', '33-67' => '33% / 67%', '67-33' => '67% / 33%'];
        foreach ($ratios as $val => $label) {
            $sel = $val === $ratio ? 'selected' : '';
            $ratioOptions .= "<option value=\"{$val}\" {$sel}>{$label}</option>";
        }

        return '
        <div class="builder-field">
            <label class="builder-label">Número de columnas</label>
            <select class="builder-select" name="settings[columns]" data-toggle-ratio="1">
                <option value="1"' . ($numCols === 1 ? ' selected' : '') . '>1 columna</option>
                <option value="2"' . ($numCols === 2 ? ' selected' : '') . '>2 columnas</option>
                <option value="3"' . ($numCols === 3 ? ' selected' : '') . '>3 columnas</option>
            </select>
        </div>
        <div class="builder-field builder-ratio-field" style="' . ($numCols !== 2 ? 'display:none;' : '') . '">
            <label class="builder-label">Proporción</label>
            <select class="builder-select" name="settings[ratio]">' . $ratioOptions . '</select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Espacio entre columnas (px)</label>
            <input type="number" class="builder-input" name="settings[gap]" value="' . $gap . '" min="0" max="80">
        </div>
        <div class="builder-field">
            <label class="builder-label">Columna izquierda</label>
            <textarea class="builder-textarea" name="settings[left_content]" rows="4" placeholder="Contenido de la columna izquierda">' . $left . '</textarea>
        </div>
        <div class="builder-field builder-col-center" style="' . ($numCols < 3 ? 'display:none;' : '') . '">
            <label class="builder-label">Columna central</label>
            <textarea class="builder-textarea" name="settings[center_content]" rows="4" placeholder="Contenido de la columna central">' . $center . '</textarea>
        </div>
        <div class="builder-field">
            <label class="builder-label">Columna derecha</label>
            <textarea class="builder-textarea" name="settings[right_content]" rows="4" placeholder="Contenido de la columna derecha">' . $right . '</textarea>
        </div>';
    }

    public function validate(array $settings): array
    {
        return [];
    }
}
