<?php

namespace MaterNatura\Contracts;

interface PostRepository {
    public function findBySlug(string $slug): ?Post;
    public function findById(int $id): ?Post;
    public function findAllPublished(int $page = 1, int $perPage = 10): array;
    public function countPublished(): int;
    public function findAllForAdmin(int $page = 1): array;
    public function countAll(): int;
    public function save(Post $post): Post;
    public function delete(int $id): void;
    public function findAllPublishedForSitemap(): array;
}
