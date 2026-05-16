<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class TextComponent implements ComponentInterface
{
    public function getType(): string
    {
        return 'text';
    }

    public function getLabel(): string
    {
        return 'Texto';
    }

    public function getIcon(): string
    {
        return 'pencil';
    }

    public function getDefaultSettings(): array
    {
        return [
            'content' => '<p>Escribe aquí tu texto...</p>',
            'size' => 'medium',
            'alignment' => 'left',
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $content = $settings['content'] ?? '';
        $size = $settings['size'] ?? 'medium';
        $alignment = $settings['alignment'] ?? 'left';

        $sizeClass = match ($size) {
            'small' => 'text-sm',
            'large' => 'text-lg',
            default => 'text-base',
        };

        $alignClass = match ($alignment) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        };

        $html = '<div class="component-text ' . $sizeClass . ' ' . $alignClass . '">';
        $html .= '<div class="component-text-content">';
        $html .= $content;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function renderForm(array $settings): string
    {
        $content = htmlspecialchars($settings['content'] ?? '', ENT_QUOTES, 'UTF-8');
        $size = $settings['size'] ?? 'medium';
        $alignment = $settings['alignment'] ?? 'left';

        $sizeOptions = '';
        foreach (['small' => 'Pequeño', 'medium' => 'Mediano', 'large' => 'Grande'] as $val => $label) {
            $sel = $val === $size ? 'selected' : '';
            $sizeOptions .= "<option value=\"{$val}\" {$sel}>{$label}</option>";
        }

        return '
        <div class="builder-field">
            <label class="builder-label">Contenido</label>
            <textarea class="builder-textarea builder-text-editor" name="settings[content]" rows="6">' . $content . '</textarea>
        </div>
        <div class="builder-field">
            <label class="builder-label">Tamaño</label>
            <select class="builder-select" name="settings[size]">' . $sizeOptions . '</select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Alineación</label>
            <select class="builder-select" name="settings[alignment]">
                <option value="left"' . ($alignment === 'left' ? ' selected' : '') . '>Izquierda</option>
                <option value="center"' . ($alignment === 'center' ? ' selected' : '') . '>Centro</option>
                <option value="right"' . ($alignment === 'right' ? ' selected' : '') . '>Derecha</option>
            </select>
        </div>';
    }

    public function validate(array $settings): array
    {
        $errors = [];
        if (empty($settings['content'])) {
            $errors['content'] = 'El contenido no puede estar vacío.';
        }
        return $errors;
    }
}
