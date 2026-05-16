<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder;

/**
 * Interfaz que todo componente del builder debe implementar.
 */
interface ComponentInterface
{
    /**
     * Identificador único del tipo de componente (ej. 'hero', 'text').
     */
    public function getType(): string;

    /**
     * Nombre legible para la UI del admin (ej. 'Hero', 'Texto').
     */
    public function getLabel(): string;

    /**
     * Icono CoreUI Free (sin prefijo cil-).
     */
    public function getIcon(): string;

    /**
     * Valores por defecto para los settings del componente.
     */
    public function getDefaultSettings(): array;

    /**
     * Renderiza el componente para el frontend.
     */
    public function render(array $settings, string $theme = 'light'): string;

    /**
     * Renderiza el formulario de configuración del componente para el admin.
     */
    public function renderForm(array $settings): string;

    /**
     * Valida los settings del componente.
     * @return array<string, string> Mapa campo => mensaje de error
     */
    public function validate(array $settings): array;
}
