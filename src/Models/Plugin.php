<?php

declare(strict_types=1);

namespace MaterNatura\Models;

class Plugin
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $description,
        public readonly bool $enabled,
        public readonly ?array $settings = null,
        public readonly ?string $installedAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: $row['name'],
            slug: $row['slug'],
            version: $row['version'],
            description: $row['description'] ?? '',
            enabled: (bool) $row['enabled'],
            settings: isset($row['settings']) ? json_decode($row['settings'], true) : null,
            installedAt: $row['installed_at'] ?? null,
        );
    }
}
