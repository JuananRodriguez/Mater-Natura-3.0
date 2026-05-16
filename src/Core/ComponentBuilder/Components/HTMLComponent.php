<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder\Components;

use MaterNatura\Core\ComponentBuilder\ComponentInterface;

class HTMLComponent implements ComponentInterface
{
    public function getType(): string { return 'html'; }
    public function getLabel(): string { return 'HTML'; }
    public function getIcon(): string { return 'html'; }

    public function getDefaultSettings(): array
    {
        return [
            'html' => '<p>Escribe tu HTML aquí...</p>',
        ];
    }

    public function render(array $settings, string $theme = 'light'): string
    {
        $html = $settings['html'] ?? '';
        return '<div class="component-html">' . $html . '</div>';
    }

    public function renderForm(array $settings): string
    {
        $html = htmlspecialchars($settings['html'] ?? '', ENT_QUOTES, 'UTF-8');
        return '
        <div class="builder-field">
            <label class="builder-label">HTML / Código</label>
            <textarea class="builder-textarea builder-text-editor" name="settings[html]" rows="12" placeholder="Introduce HTML, scripts, embeds...">' . $html . '</textarea>
        </div>
        <div class="builder-field">
            <label class="builder-label" style="color:#999;font-weight:400;text-transform:none;font-size:12px;">
            ⚠️ El HTML se renderiza sin escapado. Úsalo solo con contenido de confianza.
            </label>
        </div>';
    }

    public function validate(array $settings): array
    {
        return [];
    }
}
