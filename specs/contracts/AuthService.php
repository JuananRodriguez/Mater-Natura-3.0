<?php

namespace MaterNatura\Contracts;

interface AuthService {
    public function login(string $usernameOrEmail, string $password, string $ip): AuthResult;
    public function logout(): void;
    public function isAuthenticated(): bool;
    public function getCurrentUser(): ?User;
    public function requireAuth(): void;
    public function hasTwoFactorEnabled(): bool;
    public function setupTwoFactor(): TwoFactorSetup;
    public function verifyTwoFactorCode(string $code): bool;
    public function completeTwoFactorSetup(string $code): bool;
    public function refreshSession(): void;
    public function checkSessionExpiry(): void;
}
