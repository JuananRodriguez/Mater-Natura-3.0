<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class HeroComponent implements ComponentInterface
{
    public function getType(): string
    {
        return 'hero';
    }

    public function getLabel(): string
    {
        return 'Hero';
    }

    public function getIcon(): string
    {
        return 'media-play';
    }

    public function getDefaultSettings(): array
    {
        return [
            'title' => 'Bienvenido',
            'subtitle' => '',
            'background_image' => '',
            'overlay_opacity' => 40,
            'alignment' => 'center',
            'text_color' => 'light',
            'cta_text' => '',
            'cta_link' => '',
            'height' => 'medium',
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $title = htmlspecialchars($settings['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $subtitle = htmlspecialchars($settings['subtitle'] ?? '', ENT_QUOTES, 'UTF-8');
        $bgImage = $settings['background_image'] ?? '';
        $overlay = min(100, max(0, (int) ($settings['overlay_opacity'] ?? 40)));
        $alignment = $settings['alignment'] ?? 'center';
        $textColor = $settings['text_color'] ?? 'light';
        $ctaText = htmlspecialchars($settings['cta_text'] ?? '', ENT_QUOTES, 'UTF-8');
        $ctaLink = htmlspecialchars($settings['cta_link'] ?? '', ENT_QUOTES, 'UTF-8');
        $height = $settings['height'] ?? 'medium';

        $heightClass = match ($height) {
            'small' => 'hero-height-sm',
            'large' => 'hero-height-lg',
            'full' => 'hero-height-full',
            default => 'hero-height-md',
        };

        $alignClass = match ($alignment) {
            'left' => 'hero-align-left',
            'right' => 'hero-align-right',
            default => 'hero-align-center',
        };

        $textColorClass = $textColor === 'dark' ? 'hero-text-dark' : 'hero-text-light';

        $style = '';
        if (!empty($bgImage)) {
            $style = ' style="background-image: url(/media/' . ltrim($bgImage, '/') . ');"';
        }

        $html = '<section class="component-hero ' . $heightClass . ' ' . $alignClass . ' ' . $textColorClass . '"' . $style . '>';
        if (!empty($bgImage) && $overlay > 0) {
            $html .= '<div class="hero-overlay" style="opacity: ' . ($overlay / 100) . ';"></div>';
        }
        $html .= '<div class="hero-content">';
        if (!empty($title)) {
            $html .= '<h1 class="hero-title">' . $title . '</h1>';
        }
        if (!empty($subtitle)) {
            $html .= '<p class="hero-subtitle">' . $subtitle . '</p>';
        }
        if (!empty($ctaText) && !empty($ctaLink)) {
            $html .= '<a href="' . $ctaLink . '" class="hero-cta">' . $ctaText . '</a>';
        }
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    public function renderForm(array $settings): string
    {
        $title = htmlspecialchars($settings['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $subtitle = htmlspecialchars($settings['subtitle'] ?? '', ENT_QUOTES, 'UTF-8');
        $bgImage = htmlspecialchars($settings['background_image'] ?? '', ENT_QUOTES, 'UTF-8');
        $overlay = $settings['overlay_opacity'] ?? 40;
        $alignment = $settings['alignment'] ?? 'center';
        $textColor = $settings['text_color'] ?? 'light';
        $ctaText = htmlspecialchars($settings['cta_text'] ?? '', ENT_QUOTES, 'UTF-8');
        $ctaLink = htmlspecialchars($settings['cta_link'] ?? '', ENT_QUOTES, 'UTF-8');
        $height = $settings['height'] ?? 'medium';

        $heightOpts = '';
        foreach (['small' => 'Pequeño', 'medium' => 'Mediano', 'large' => 'Grande', 'full' => 'Pantalla completa'] as $val => $label) {
            $sel = $val === $height ? 'selected' : '';
            $heightOpts .= "<option value=\"{$val}\" {$sel}>{$label}</option>";
        }

        return '
        <div class="builder-field">
            <label class="builder-label">Título</label>
            <input type="text" class="builder-input" name="settings[title]" value="' . $title . '" placeholder="Título principal">
        </div>
        <div class="builder-field">
            <label class="builder-label">Subtítulo</label>
            <textarea class="builder-textarea" name="settings[subtitle]" rows="3" placeholder="Subtítulo o descripción">' . $subtitle . '</textarea>
        </div>
        <div class="builder-field">
            <label class="builder-label">Imagen de fondo</label>
            <div class="builder-image-picker">
                <input type="hidden" name="settings[background_image]" value="' . $bgImage . '" class="builder-image-src">
                <button type="button" class="builder-btn builder-btn-secondary builder-select-image">Seleccionar imagen</button>
                <span class="builder-image-name">' . ($bgImage ? basename($bgImage) : 'Ninguna') . '</span>
            </div>
        </div>
        <div class="builder-field">
            <label class="builder-label">Opacidad de la superposición</label>
            <div class="builder-range-wrapper">
                <input type="range" class="builder-range" name="settings[overlay_opacity]" min="0" max="100" value="' . $overlay . '">
                <span class="builder-range-value">' . $overlay . '%</span>
            </div>
        </div>
        <div class="builder-field">
            <label class="builder-label">Altura</label>
            <select class="builder-select" name="settings[height]">' . $heightOpts . '</select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Alineación del contenido</label>
            <select class="builder-select" name="settings[alignment]">
                <option value="left"' . ($alignment === 'left' ? ' selected' : '') . '>Izquierda</option>
                <option value="center"' . ($alignment === 'center' ? ' selected' : '') . '>Centro</option>
                <option value="right"' . ($alignment === 'right' ? ' selected' : '') . '>Derecha</option>
            </select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Color del texto</label>
            <select class="builder-select" name="settings[text_color]">
                <option value="light"' . ($textColor === 'light' ? ' selected' : '') . '>Claro</option>
                <option value="dark"' . ($textColor === 'dark' ? ' selected' : '') . '>Oscuro</option>
            </select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Texto del botón CTA (opcional)</label>
            <input type="text" class="builder-input" name="settings[cta_text]" value="' . $ctaText . '" placeholder="Saber más">
        </div>
        <div class="builder-field">
            <label class="builder-label">Enlace del botón CTA</label>
            <input type="text" class="builder-input" name="settings[cta_link]" value="' . $ctaLink . '" placeholder="https://... o /post">
        </div>';
    }

    public function validate(array $settings): array
    {
        $errors = [];
        if (empty($settings['title'])) {
            $errors['title'] = 'El título del Hero es obligatorio.';
        }
        return $errors;
    }
}
