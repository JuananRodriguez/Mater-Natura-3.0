<?php

namespace MaterNatura\Contracts;

interface Security {
    public function generateCsrfToken(): string;
    public function validateCsrfToken(string $token): bool;
    public function getCsrfField(): string;
    public function checkRateLimit(string $ip, string $username): bool;
    public function registerFailedAttempt(string $ip, string $username): void;
    public function clearFailedAttempts(string $ip, string $username): void;
    public function getRemainingBlockTime(string $ip, string $username): int;
    public function escapeHtml(string $value): string;
    public function sanitizeFilename(string $filename): string;
    public function sanitizeSlug(string $slug): string;
    public function validateEmail(string $email): bool;
    public function validateImageMime(string $filePath): ?string;
    public function validatePasswordStrength(string $password): array;
    public function sendSecurityHeaders(): void;
    public function generateNonce(): string;
    public function isPathSafe(string $path, string $baseDir): bool;
    public function getReservedSlugs(): array;
    public function isReservedSlug(string $slug): bool;
}
