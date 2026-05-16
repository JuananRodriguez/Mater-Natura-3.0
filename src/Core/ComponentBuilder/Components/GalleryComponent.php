<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class GalleryComponent implements ComponentInterface
{
    public function getType(): string { return 'gallery'; }
    public function getLabel(): string { return 'Galería'; }
    public function getIcon(): string { return 'gallery'; }

    public function getDefaultSettings(): array
    {
        return [
            'images' => [],
            'columns' => 3,
            'gap' => 8,
            'lightbox' => true,
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $images = $settings['images'] ?? [];
        $columns = max(1, min(6, (int) ($settings['columns'] ?? 3)));
        $gap = (int) ($settings['gap'] ?? 8);
        $lightbox = !empty($settings['lightbox']);

        if (empty($images)) {
            return '';
        }

        $html = '<div class="component-gallery" style="display:grid;grid-template-columns:repeat(' . $columns . ',1fr);gap:' . $gap . 'px;">';
        foreach ($images as $img) {
            $src = htmlspecialchars($img['src'] ?? '', ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($img['alt'] ?? '', ENT_QUOTES, 'UTF-8');
            if (empty($src)) continue;

            $item = '<img src="/media/' . ltrim($src, '/') . '" alt="' . $alt . '" class="gallery-image" loading="lazy" style="width:100%;height:auto;display:block;border-radius:4px;">';
            if ($lightbox) {
                $item = '<a href="/media/' . ltrim($src, '/') . '" class="gallery-link" data-lightbox="gallery">' . $item . '</a>';
            }
            $html .= '<div class="gallery-item">' . $item . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderForm(array $settings): string
    {
        $columns = (int) ($settings['columns'] ?? 3);
        $gap = (int) ($settings['gap'] ?? 8);
        $lightbox = !empty($settings['lightbox']);
        $images = $settings['images'] ?? [];

        $imagesHtml = '';
        if (!empty($images)) {
            foreach ($images as $i => $img) {
                $src = htmlspecialchars($img['src'] ?? '', ENT_QUOTES, 'UTF-8');
                $alt = htmlspecialchars($img['alt'] ?? '', ENT_QUOTES, 'UTF-8');
                $imagesHtml .= '
                <div class="gallery-image-row" data-index="' . $i . '">
                    <input type="hidden" name="settings[images][' . $i . '][src]" value="' . $src . '" class="gallery-src">
                    <input type="text" name="settings[images][' . $i . '][alt]" value="' . $alt . '" class="builder-input" placeholder="Alt" style="width:100px;">
                    <span class="gallery-filename">' . basename($src) . '</span>
                    <button type="button" class="builder-btn builder-btn-secondary gallery-select-img">Seleccionar</button>
                    <button type="button" class="builder-btn builder-btn-secondary gallery-remove-img">✕</button>
                </div>';
            }
        }

        return '
        <div class="builder-field">
            <label class="builder-label">Columnas</label>
            <select class="builder-select" name="settings[columns]">
                <option value="1"' . ($columns === 1 ? ' selected' : '') . '>1</option>
                <option value="2"' . ($columns === 2 ? ' selected' : '') . '>2</option>
                <option value="3"' . ($columns === 3 ? ' selected' : '') . '>3</option>
                <option value="4"' . ($columns === 4 ? ' selected' : '') . '>4</option>
                <option value="5"' . ($columns === 5 ? ' selected' : '') . '>5</option>
                <option value="6"' . ($columns === 6 ? ' selected' : '') . '>6</option>
            </select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Espacio entre imágenes (px)</label>
            <input type="number" class="builder-input" name="settings[gap]" value="' . $gap . '" min="0" max="48">
        </div>
        <div class="builder-field">
            <label class="builder-label">
                <input type="checkbox" name="settings[lightbox]" value="1"' . ($lightbox ? ' checked' : '') . '> Lightbox
            </label>
        </div>
        <div class="builder-field">
            <label class="builder-label">Imágenes</label>
            <div class="gallery-images-list">' . ($imagesHtml ?: '<p class="builder-hint" style="font-size:12px;color:#999;">Aún no hay imágenes</p>') . '</div>
            <button type="button" class="builder-btn builder-btn-secondary gallery-add-img" style="margin-top:8px;">+ Añadir imagen</button>
        </div>';
    }

    public function validate(array $settings): array
    {
        return [];
    }
}
