<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Security
{
    private const CSRF_TOKEN_LENGTH = 32;
    private const SALT_LENGTH = 16;

    public function __construct(
        private Database $db,
    ) {
    }

    // ─── CSRF ───

    public function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Solo generar si no existe — así el token es estable durante la sesión
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $token = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
            $_SESSION['csrf_token'] = $token;
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        // Timing-safe comparison
        $valid = hash_equals($_SESSION['csrf_token'], $token);

        // Permitir reutilizar el token durante la sesión
        // (sin marcarlo como consumido, para evitar falsos CSRF con el server embebido de PHP)

        return $valid;
    }

    public function getCsrfField(): string
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . $this->escapeHtml($token) . '">';
    }

    // ─── Rate Limiting ───

    public function checkRateLimit(string $ip, string $username): bool
    {
        $window = MATER_RATE_LIMIT_WINDOW;
        $maxAttempts = MATER_RATE_LIMIT_ATTEMPTS;

        $cutoff = date('Y-m-d H:i:s', time() - $window);

        $count = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM failed_logins
             WHERE ip_address = ? AND username = ? AND attempted_at >= ?",
            [$ip, $username, $cutoff]
        );

        return ($count['cnt'] ?? 0) < $maxAttempts;
    }

    public function registerFailedAttempt(string $ip, string $username): void
    {
        $this->db->insert('failed_logins', [
            'ip_address'   => $ip,
            'username'     => $username,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function clearFailedAttempts(string $ip, string $username): void
    {
        $this->db->delete('failed_logins', 'ip_address = ? AND username = ?', [$ip, $username]);
    }

    public function getRemainingBlockTime(string $ip, string $username): int
    {
        $earliest = $this->db->fetchOne(
            "SELECT MIN(attempted_at) as first_attempt FROM failed_logins
             WHERE ip_address = ? AND username = ?
             ORDER BY attempted_at ASC
             LIMIT 1",
            [$ip, $username]
        );

        if (!$earliest || !$earliest['first_attempt']) {
            return 0;
        }

        $unlockTime = strtotime($earliest['first_attempt']) + MATER_RATE_LIMIT_WINDOW;
        $remaining = $unlockTime - time();

        return max(0, $remaining);
    }

    // ─── Sanitización ───

    public function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal
        $filename = preg_replace('/\.\./', '', $filename);
        $filename = preg_replace('#[/\\\\]#', '', $filename);

        // Keep only safe characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        return trim($filename, '_-.');
    }

    public function sanitizeSlug(string $slug): string
    {
        // Transliterate accented characters
        $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug) ?: $slug;

        // Lowercase, replace non-alphanumeric with hyphens
        $slug = mb_strtolower($slug, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Sanitiza texto genérico: elimina etiquetas HTML y recorta.
     */
    public function sanitizeString(string $input, int $maxLength = 2000): string
    {
        $clean = strip_tags(trim($input));
        $clean = htmlspecialchars_decode($clean, ENT_QUOTES | ENT_HTML5);
        return mb_substr($clean, 0, $maxLength);
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateImageMime(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        $allowed = MATER_IMAGE_ALLOWED_TYPES;

        return in_array($mime, $allowed, true) ? $mime : null;
    }

    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < 12) {
            $errors[] = 'La contraseña debe tener al menos 12 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Debe contener al menos una mayúscula.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Debe contener al menos una minúscula.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un número.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un carácter especial.';
        }

        if (empty($errors)) {
            return ['valid' => true, 'message' => ''];
        }
        return ['valid' => false, 'message' => implode(' ', $errors)];
    }

    // ─── Headers de seguridad ───

    public function sendSecurityHeaders(): void
    {
        $csp = "default-src 'self'; "
             . "style-src 'self' 'unsafe-inline'; "
             . "script-src 'self' 'unsafe-inline'; "
             . "img-src 'self' data:; "
             . "font-src 'self'; "
             . "form-action 'self'; "
             . "base-uri 'self'; "
             . "frame-ancestors 'none';";

        header("Content-Security-Policy: " . $csp);
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

        if (MATER_ENV === 'production') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }

    public function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    // ─── Path traversal ───

    public function isPathSafe(string $path, string $baseDir): bool
    {
        $realPath = realpath($path);
        $realBase = realpath($baseDir);

        if ($realPath === false || $realBase === false) {
            return false;
        }

        return str_starts_with($realPath, $realBase);
    }

    public function getReservedSlugs(): array
    {
        return MATER_SLUG_RESERVED;
    }

    public function isReservedSlug(string $slug): bool
    {
        return in_array(mb_strtolower(trim($slug)), $this->getReservedSlugs(), true);
    }
}
