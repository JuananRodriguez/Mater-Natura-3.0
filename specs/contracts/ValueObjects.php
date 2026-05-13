<?php

namespace MaterNatura\Contracts;

// ─── Value Objects ───

class AuthResult {
    public function __construct(
        public readonly bool $success,
        public readonly ?User $user = null,
        public readonly ?string $error = null,
        public readonly bool $needsTwoFactor = false,
        public readonly ?string $redirect = null,
    ) {}
}

class User {
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly string $role,
        public readonly ?\DateTime $lastLoginAt = null,
    ) {}
}

class TwoFactorSetup {
    public function __construct(
        public readonly string $secret,
        public readonly string $qrCodeSvg,
    ) {}
}

class Post {
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $title = '',
        public string $slug = '',
        public string $description = '',
        public ?string $imageUrl = null,
        public string $template = 'dark',
        public string $status = 'draft',
        public ?\DateTime $publishedAt = null,
        public ?\DateTime $updatedAt = null,
        public ?\DateTime $createdAt = null,
    ) {}
}

class Page {
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $title = '',
        public string $slug = '',
        public string $content = '',
        public string $template = 'dark',
        public string $status = 'published',
        public bool $isHome = false,
        public ?\DateTime $publishedAt = null,
        public ?\DateTime $updatedAt = null,
        public ?\DateTime $createdAt = null,
    ) {}
}

class UploadResult {
    public function __construct(
        public readonly bool $success,
        public readonly ?string $relativePath = null,
        public readonly ?string $error = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {}
}

class PluginInfo {
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $description,
        public readonly bool $enabled,
        public readonly ?array $settings = null,
    ) {}
}
