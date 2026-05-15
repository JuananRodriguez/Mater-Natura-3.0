<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Auth
{
    private const SESSION_USER_KEY = 'mn_user';

    public function __construct(
        private Database $db,
        private Security $security,
    ) {
    }

    /**
     * @param string $usernameOrEmail
     * @param string $password
     * @param string $ip
     * @return array{success: bool, user: ?array, error: ?string, needsTwoFactor: bool, redirect: ?string}
     */
    public function login(string $usernameOrEmail, string $password, string $ip): array
    {
        // 1. Rate limiting check
        if (!$this->security->checkRateLimit($ip, $usernameOrEmail)) {
            $remaining = $this->security->getRemainingBlockTime($ip, $usernameOrEmail);
            return [
                'success' => false,
                'user' => null,
                'error' => sprintf(
                    'Demasiados intentos. Inténtalo de nuevo en %d minutos.',
                    ceil($remaining / 60)
                ),
                'needsTwoFactor' => false,
                'redirect' => null,
            ];
        }

        // 2. Buscar usuario por username o email
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1",
            [$usernameOrEmail, $usernameOrEmail]
        );

        if (!$user) {
            // No revelar si el usuario existe o no
            $this->security->registerFailedAttempt($ip, $usernameOrEmail);
            return [
                'success' => false,
                'user' => null,
                'error' => 'Credenciales inválidas.',
                'needsTwoFactor' => false,
                'redirect' => null,
            ];
        }

        // 3. Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            $this->security->registerFailedAttempt($ip, $usernameOrEmail);
            return [
                'success' => false,
                'user' => null,
                'error' => 'Credenciales inválidas.',
                'needsTwoFactor' => false,
                'redirect' => null,
            ];
        }

        // 4. Verificar si necesita rehash
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $this->db->update('users', ['password_hash' => $newHash], 'id = ?', [$user['id']]);
        }

        // 5. Comprobar 2FA
        if ($user['mfa_secret'] !== null) {
            $_SESSION['2fa_pending_user_id'] = $user['id'];
            return [
                'success' => true,
                'user' => null,
                'error' => null,
                'needsTwoFactor' => true,
                'redirect' => '/login/2fa',
            ];
        }

        // 6. Login exitoso — establecer sesión
        $this->establishSession($user, $ip);

        return [
            'success' => true,
            'user' => $this->sanitizeUser($user),
            'error' => null,
            'needsTwoFactor' => false,
            'redirect' => '/admin',
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }

        session_destroy();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_USER_KEY]);
    }

    public function getCurrentUser(): ?array
    {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();

        $user = $this->getCurrentUser();
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso denegado</title>';
            echo '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f9f9f9}';
            echo '.card{text-align:center;padding:3rem;background:#fff;border:1px solid #000;max-width:400px}';
            echo 'h1{font-size:1.5rem;margin-bottom:.5rem}p{color:#666}</style></head>';
            echo '<body><div class="card"><h1>Acceso denegado</h1>';
            echo '<p>Solo los administradores pueden acceder a esta sección.</p>';
            echo '<p><a href="/admin" style="color:#000">Volver al panel</a></p></div></body></html>';
            exit;
        }
    }

    // ─── 2FA (TOTP) ───

    public function hasTwoFactorEnabled(): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        $dbUser = $this->db->fetchOne("SELECT mfa_secret FROM users WHERE id = ?", [$user['id']]);
        return $dbUser && $dbUser['mfa_secret'] !== null;
    }

    public function setupTwoFactor(): array
    {
        // Placeholder — implementar con spomky-labs/otphp
        return [
            'secret' => 'TODO',
            'qrCodeSvg' => '<!-- TODO: generar QR con el secreto -->',
        ];
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        // Placeholder — implementar con spomky-labs/otphp
        return false;
    }

    public function completeTwoFactorSetup(string $code): bool
    {
        // Placeholder
        return false;
    }

    public function refreshSession(): void
    {
        session_regenerate_id(true);
    }

    public function checkSessionExpiry(): void
    {
        $lifetime = MATER_SESSION_LIFETIME;

        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $lifetime)) {
            $this->logout();
            session_start();
            $_SESSION['expired'] = true;
        }

        $_SESSION['_last_activity'] = time();
    }

    // ─── Privados ───

    private function establishSession(array $user, string $ip): void
    {
        // Regenerar ID de sesión (anti session fixation)
        session_regenerate_id(true);

        $_SESSION[self::SESSION_USER_KEY] = $this->sanitizeUser($user);
        $_SESSION['_last_activity'] = time();

        // Actualizar últimos datos de acceso
        $this->db->update('users', [
            'last_login_ip' => $ip,
            'last_login_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $user['id']]);

        // Limpiar intentos fallidos
        $this->security->clearFailedAttempts($ip, $user['username']);
    }

    private function sanitizeUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }
}
