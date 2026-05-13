<?php

namespace MaterNatura\Contracts;

interface PluginInterface {
    public function getMeta(): array;
    public function registerHooks(): array;
    public function onActivate(): void;
    public function onDeactivate(): void;
}

interface PluginRegistry {
    public function discoverPlugins(): array;
    public function loadActivePlugins(): array;
    public function getPlugin(string $slug): ?PluginInterface;
    public function activate(string $slug): bool;
    public function deactivate(string $slug): bool;
    public function isActive(string $slug): bool;
    public function executeHook(string $hookName, array $context = []): array;
    public function getAllPlugins(): array;
}
