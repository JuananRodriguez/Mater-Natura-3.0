<?php

declare(strict_types=1);

namespace MaterNatura\Models;

class Page
{
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $title = '',
        public string $slug = '',
        public string $content = '',
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public string $template = 'dark',
        public string $status = 'published',
        public bool $isHome = false,
        public ?string $publishedAt = null,
        public ?string $updatedAt = null,
        public ?string $createdAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            title: $row['title'],
            slug: $row['slug'],
            content: $row['content'],
            metaTitle: $row['meta_title'] ?? null,
            metaDescription: $row['meta_description'] ?? null,
            template: $row['template'],
            status: $row['status'],
            isHome: (bool) ($row['is_home'] ?? false),
            publishedAt: $row['published_at'],
            updatedAt: $row['updated_at'],
            createdAt: $row['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'template' => $this->template,
            'status' => $this->status,
            'is_home' => $this->isHome,
            'published_at' => $this->publishedAt,
        ];
    }
}
