<?php

declare(strict_types=1);

namespace MaterNatura\Core\ComponentBuilder;

/**
 * Gestiona el registro, descubrimiento y acceso a componentes del builder.
 */
class ComponentManager
{
    /** @var array<string, ComponentInterface> */
    private array $components = [];

    /**
     * Registra un componente en el manager.
     */
    public function register(ComponentInterface $component): void
    {
        $this->components[$component->getType()] = $component;
    }

    /**
     * Obtiene un componente por su tipo.
     */
    public function getComponent(string $type): ?ComponentInterface
    {
        return $this->components[$type] ?? null;
    }

    /**
     * Devuelve todos los componentes registrados.
     * @return array<string, ComponentInterface>
     */
    public function getAll(): array
    {
        return $this->components;
    }

    /**
     * Valida los settings de un componente.
     * @return array<string, string>
     */
    public function validate(string $type, array $settings): array
    {
        $component = $this->getComponent($type);
        if ($component === null) {
            return ['_type' => "Tipo de componente '{$type}' no encontrado."];
        }
        return $component->validate($settings);
    }
}
