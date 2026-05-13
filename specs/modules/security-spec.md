# Module Spec: Security

## Responsabilidad
Proveer todas las medidas de ciberseguridad: tokens CSRF, rate limiting,
sanitización de salida, validación de entrada, cabeceras HTTP de seguridad.

## Dependencias
- Database (para rate limiting y failed_logins)

## API pública

```php
class Security {
    // CSRF
    public function generateCsrfToken(): string
    public function validateCsrfToken(string $token): bool
    public function getCsrfField(): string              // Devuelve <input hidden> listo

    // Rate limiting
    public function checkRateLimit(string $ip, string $username): bool
    public function registerFailedAttempt(string $ip, string $username): void
    public function clearFailedAttempts(string $ip, string $username): void
    public function getRemainingBlockTime(string $ip, string $username): int

    // Sanitización
    public function escapeHtml(string $value): string    // htmlspecialchars wrapper
    public function sanitizeFilename(string $filename): string
    public function sanitizeSlug(string $slug): string

    // Validación
    public function validateEmail(string $email): bool
    public function validateImageMime(string $filePath): ?string  // Devuelve MIME o null
    public function validatePasswordStrength(string $password): array  // ['valid' => bool, 'message' => string]

    // Headers de seguridad
    public function sendSecurityHeaders(): void
    public function generateNonce(): string              // Para CSP

    // Path traversal
    public function isPathSafe(string $path, string $baseDir): bool
    public function getReservedSlugs(): array
    public function isReservedSlug(string $slug): bool
}
```

## Tipos de datos

```php
class ValidationResult {
    public readonly bool $valid;
    public readonly array $errors;  // string[]
}
```

## Reglas de negocio

1. **CSRF**: Token generado con `random_bytes(32)` + hash, almacenado en sesión
2. **Rate limiting**: Máximo 5 intentos fallidos por (IP + username) en ventana de 15 minutos
3. **Escape HTML**: Toda salida de datos del usuario debe pasar por `escapeHtml()` (alias de `htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')`)
4. **Slugs reservados**: `admin`, `login`, `logout`, `post`, `sitemap.xml`, `media`, `page`
5. **Cabeceras de seguridad** en cada respuesta:
   - `Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;`
   - `X-Frame-Options: DENY`
   - `X-Content-Type-Options: nosniff`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: geolocation=(), microphone=(), camera=()`

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Token CSRF expirado (sesión caducada) | `validateCsrfToken()` devuelve `false` |
| Rate limit superado desde varias IPs | Cada IP es independiente (no comparten contador) |
| Slug con caracteres extraños | Se sanitiza: solo a-z, 0-9 y guiones |
| MIME de imagen manipulado | `finfo` detecta el tipo real, no confía en extensión |
| Path traversal en filename | `sanitizeFilename()` elimina `..` y `/` |
