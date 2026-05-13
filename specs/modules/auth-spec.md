# Module Spec: Auth

## Responsabilidad
Gestionar la autenticación de usuarios: login, logout, sesión, verificación 2FA.

## Dependencias
- Database (para consultar users, failed_logins)
- Security (para CSRF, rate limiting, cabeceras)

## API pública

```php
class Auth {
    public function __construct(Database $db, Security $security)

    // Autenticación
    public function login(string $usernameOrEmail, string $password, string $ip): AuthResult
    public function logout(): void
    public function isAuthenticated(): bool
    public function getCurrentUser(): ?User
    public function requireAuth(): void                    // Redirect a /login si no auth

    // 2FA
    public function hasTwoFactorEnabled(): bool
    public function setupTwoFactor(): TwoFactorSetup       // Genera secreto + QR
    public function verifyTwoFactorCode(string $code): bool
    public function completeTwoFactorSetup(string $code): bool

    // Sesión
    public function refreshSession(): void                 // Regenera ID
    public function checkSessionExpiry(): void             // 30 min inactividad
}

class AuthResult {
    public readonly bool $success;
    public readonly ?User $user;
    public readonly ?string $error;         // Mensaje para el usuario
    public readonly bool $needsTwoFactor;   // Si requiere segundo factor
    public readonly ?string $redirect;      // URL a redirigir
}

class User {
    public readonly int $id;
    public readonly string $username;
    public readonly string $email;
    public readonly string $role;           // 'admin' | 'editor'
    public readonly ?DateTime $lastLoginAt;
}

class TwoFactorSetup {
    public readonly string $secret;
    public readonly string $qrCodeSvg;      // Para mostrar en el navegador
}
```

## Reglas de negocio
*(Derivadas directamente de auth.feature)*

1. **Login exitoso**: buscar por username o email, verificar Argon2id, regenerar session_id, limpiar failed_logins, registrar last_login
2. **Contraseña incorrecta**: no revelar si el usuario existe o no (mensaje genérico "Credenciales inválidas")
3. **Rate limiting**: 5 fallos por IP+username → bloqueo 15 min (sin verificar contraseña, para evitar timing attacks)
4. **Sesión**: expira tras 30 min de inactividad, SameSite=Strict, HttpOnly, Secure en producción
5. **Regeneración de session_id**: en cada login exitoso (anti session fixation)
6. **2FA** (post-MVP): TOTP con 30s ventana, secreto cifrado en BD
7. **Roles**: 'admin' tiene acceso completo, 'editor' no ve gestión de usuarios ni plugins

## Edge cases

| Caso | Comportamiento |
|------|---------------|
| Username no existe | Mismo mensaje que contraseña incorrecta (no revelar existencia) |
| 2FA activo pero código incorrecto | Reintentar, sin límite específico (el rate limiting cubre login) |
| Sesión expira justo al enviar un formulario | Redirigir a login con mensaje, preservar datos POST |
|Usuario bloqueado intenta login con otro username desde misma IP| El bloqueo es por IP (cualquier intento desde IP bloqueada se rechaza) |
| Login concurrente desde dos dispositivos | Ambos funcionan (sesiones independientes) |
