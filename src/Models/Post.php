<?php

declare(strict_types=1);

namespace MaterNatura\Models;

class Post
{
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $title = '',
        public string $slug = '',
        public string $reference = '',
        public string $description = '',
        public ?string $imageUrl = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public string $template = 'dark',
        public string $status = 'draft',
        public string $visibility = 'public',
        public ?string $visibilityPassword = null,
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
            reference: $row['reference'] ?? '',
            description: $row['description'],
            imageUrl: $row['image_url'],
            metaTitle: $row['meta_title'] ?? null,
            metaDescription: $row['meta_description'] ?? null,
            template: $row['template'],
            status: $row['status'],
            visibility: $row['visibility'] ?? 'public',
            visibilityPassword: $row['visibility_password'] ?? null,
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
            'reference' => $this->reference,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'template' => $this->template,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'visibility_password' => $this->visibilityPassword,
            'published_at' => $this->publishedAt,
        ];
    }

    public function getExcerpt(int $length = 160): string
    {
        $text = strip_tags($this->description);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '…';
    }
}
