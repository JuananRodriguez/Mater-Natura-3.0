<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class QuoteComponent implements ComponentInterface
{
    public function getType(): string { return 'quote'; }
    public function getLabel(): string { return 'Cita'; }
    public function getIcon(): string { return 'quote'; }

    public function getDefaultSettings(): array
    {
        return [
            'text' => 'Texto de la cita...',
            'author' => '',
            'source' => '',
            'alignment' => 'center',
            'style' => 'default',
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $text = htmlspecialchars($settings['text'] ?? '', ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($settings['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $source = htmlspecialchars($settings['source'] ?? '', ENT_QUOTES, 'UTF-8');
        $alignment = $settings['alignment'] ?? 'center';
        $style = $settings['style'] ?? 'default';

        if (empty($text)) return '';

        $alignClass = match ($alignment) {
            'left' => 'quote-align-left',
            'right' => 'quote-align-right',
            default => 'quote-align-center',
        };

        $styleClass = $style === 'bordered' ? 'quote-bordered' : 'quote-default';

        $html = '<blockquote class="component-quote ' . $alignClass . ' ' . $styleClass . '">';
        $html .= '<p class="quote-text">' . $text . '</p>';
        if (!empty($author)) {
            $html .= '<footer class="quote-footer">';
            $html .= '<cite class="quote-author">' . $author . '</cite>';
            if (!empty($source)) {
                $html .= ', <span class="quote-source">' . $source . '</span>';
            }
            $html .= '</footer>';
        }
        $html .= '</blockquote>';
        return $html;
    }

    public function renderForm(array $settings): string
    {
        $text = htmlspecialchars($settings['text'] ?? '', ENT_QUOTES, 'UTF-8');
        $author = htmlspecialchars($settings['author'] ?? '', ENT_QUOTES, 'UTF-8');
        $source = htmlspecialchars($settings['source'] ?? '', ENT_QUOTES, 'UTF-8');
        $alignment = $settings['alignment'] ?? 'center';
        $style = $settings['style'] ?? 'default';

        return '
        <div class="builder-field">
            <label class="builder-label">Texto de la cita</label>
            <textarea class="builder-textarea" name="settings[text]" rows="4" placeholder="Cita...">' . $text . '</textarea>
        </div>
        <div class="builder-field">
            <label class="builder-label">Autor</label>
            <input type="text" class="builder-input" name="settings[author]" value="' . $author . '" placeholder="Nombre del autor">
        </div>
        <div class="builder-field">
            <label class="builder-label">Fuente (opcional)</label>
            <input type="text" class="builder-input" name="settings[source]" value="' . $source . '" placeholder="Título de la obra, URL...">
        </div>
        <div class="builder-field">
            <label class="builder-label">Alineación</label>
            <select class="builder-select" name="settings[alignment]">
                <option value="left"' . ($alignment === 'left' ? ' selected' : '') . '>Izquierda</option>
                <option value="center"' . ($alignment === 'center' ? ' selected' : '') . '>Centro</option>
                <option value="right"' . ($alignment === 'right' ? ' selected' : '') . '>Derecha</option>
            </select>
        </div>
        <div class="builder-field">
            <label class="builder-label">Estilo</label>
            <select class="builder-select" name="settings[style]">
                <option value="default"' . ($style === 'default' ? ' selected' : '') . '>Cursiva (clásica)</option>
                <option value="bordered"' . ($style === 'bordered' ? ' selected' : '') . '>Con borde lateral</option>
            </select>
        </div>';
    }

    public function validate(array $settings): array
    {
        $errors = [];
        if (empty($settings['text'])) {
            $errors['text'] = 'El texto de la cita es obligatorio.';
        }
        return $errors;
    }
}
