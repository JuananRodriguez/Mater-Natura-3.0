<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class ImageComponent implements ComponentInterface
{
    public function getType(): string
    {
        return 'image';
    }

    public function getLabel(): string
    {
        return 'Imagen';
    }

    public function getIcon(): string
    {
        return 'image';
    }

    public function getDefaultSettings(): array
    {
        return [
            'src' => '',
            'alt' => '',
            'caption' => '',
            'link' => '',
            'alignment' => 'center',
            'width' => '',
            'height' => '',
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $src = $settings['src'] ?? '';
        $alt = htmlspecialchars($settings['alt'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = htmlspecialchars($settings['caption'] ?? '', ENT_QUOTES, 'UTF-8');
        $link = $settings['link'] ?? '';
        $alignment = $settings['alignment'] ?? 'center';
        $width = $settings['width'] ? ' width="' . (int) $settings['width'] . '"' : '';
        $height = $settings['height'] ? ' height="' . (int) $settings['height'] . '"' : '';

        if (empty($src)) {
            return '';
        }

        $alignClass = match ($alignment) {
            'left' => 'align-left',
            'right' => 'align-right',
            default => 'align-center',
        };

        $img = '<img src="/media/' . ltrim($src, '/') . '" alt="' . $alt . '"' . $width . $height . ' class="component-image-img" loading="lazy">';

        if (!empty($link)) {
            $img = '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" class="component-image-link">' . $img . '</a>';
        }

        $html = '<figure class="component-image ' . $alignClass . '">';
        $html .= $img;
        if (!empty($caption)) {
            $html .= '<figcaption class="component-image-caption">' . $caption . '</figcaption>';
        }
        $html .= '</figure>';

        return $html;
    }

    public function renderForm(array $settings): string
    {
        $src = htmlspecialchars($settings['src'] ?? '', ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($settings['alt'] ?? '', ENT_QUOTES, 'UTF-8');
        $caption = htmlspecialchars($settings['caption'] ?? '', ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars($settings['link'] ?? '', ENT_QUOTES, 'UTF-8');
        $alignment = $settings['alignment'] ?? 'center';
        $width = htmlspecialchars((string) ($settings['width'] ?? ''), ENT_QUOTES, 'UTF-8');
        $height = htmlspecialchars((string) ($settings['height'] ?? ''), ENT_QUOTES, 'UTF-8');

        return '
        <div class="builder-field">
            <label class="builder-label">Imagen</label>
            <div class="builder-image-picker">
                <input type="hidden" name="settings[src]" value="' . $src . '" class="builder-image-src">
                <button type="button" class="builder-btn builder-btn-secondary builder-select-image">Seleccionar imagen</button>
                <span class="builder-image-name">' . ($src ? basename($src) : 'Ninguna') . '</span>
            </div>
        </div>
        <div class="builder-field">
            <label class="builder-label">Texto alternativo (alt)</label>
            <input type="text" class="builder-input" name="settings[alt]" value="' . $alt . '" placeholder="Descripción de la imagen">
        </div>
        <div class="builder-field">
            <label class="builder-label">Pie de foto</label>
            <input type="text" class="builder-input" name="settings[caption]" value="' . $caption . '" placeholder="Texto bajo la imagen">
        </div>
        <div class="builder-field">
            <label class="builder-label">Enlace (opcional)</label>
            <input type="text" class="builder-input" name="settings[link]" value="' . $link . '" placeholder="https://...">
        </div>
        <div class="builder-field-row">
            <div class="builder-field builder-field--half">
                <label class="builder-label">Ancho (px)</label>
                <input type="number" class="builder-input" name="settings[width]" value="' . $width . '" placeholder="Auto">
            </div>
            <div class="builder-field builder-field--half">
                <label class="builder-label">Alto (px)</label>
                <input type="number" class="builder-input" name="settings[height]" value="' . $height . '" placeholder="Auto">
            </div>
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
        if (empty($settings['src'])) {
            $errors['src'] = 'Debes seleccionar una imagen.';
        }
        return $errors;
    }
}
