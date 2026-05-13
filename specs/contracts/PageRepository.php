<?php

namespace MaterNatura\Contracts;

interface PageRepository {
    public function findBySlug(string $slug): ?Page;
    public function findById(int $id): ?Page;
    public function findHomePage(): ?Page;
    public function findAllPublished(): array;
    public function findAllForAdmin(): array;
    public function save(Page $page): Page;
    public function delete(int $id): void;
    public function isHomePage(int $id): bool;
    public function setHomePage(int $id): void;
    public function findAllPublishedForSitemap(): array;
}
