<?php

declare(strict_types=1);

namespace MaterNatura\Models;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $lastLoginAt = null,
        public readonly ?string $createdAt = null,
    ) {}

    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            username: $row['username'],
            email: $row['email'],
            role: $row['role'],
            lastLoginAt: $row['last_login_at'] ?? null,
            createdAt: $row['created_at'] ?? null,
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }
}
