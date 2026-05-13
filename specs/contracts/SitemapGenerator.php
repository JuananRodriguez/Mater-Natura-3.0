<?php

namespace MaterNatura\Contracts;

interface SitemapGenerator {
    public function generate(): string;
    public function write(): bool;
    public function queueRegeneration(string $type, int $entryId, string $action): void;
    public function processQueue(): int;
    public function hasPendingChanges(): bool;
}
