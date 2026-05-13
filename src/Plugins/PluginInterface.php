<?php

declare(strict_types=1);

namespace MaterNatura\Plugins;

interface PluginInterface
{
    /**
     * @return array{name: string, version: string, description: string, slug: string}
     */
    public function getMeta(): array;

    /**
     * @return array{hook: string, handler: string, priority?: int}[]
     */
    public function registerHooks(): array;

    public function onActivate(): void;
    public function onDeactivate(): void;
}
