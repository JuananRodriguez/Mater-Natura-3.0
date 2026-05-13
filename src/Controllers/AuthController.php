<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Auth;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class AuthController
{
    private Auth $auth;
    private Security $security;

    public function __construct(Auth $auth, Security $security)
    {
        $this->auth = $auth;
        $this->security = $security;
    }

    /**
     * GET /login — Mostrar formulario de login
     */
    public function loginForm(): string
    {
        // Si ya está autenticado, redirigir al admin
        if ($this->auth->isAuthenticated()) {
            header('Location: /admin');
            exit;
        }

        $template = new Template($this->security);
        $template->setMetaTitle('Iniciar sesión — ' . MATER_SITE_NAME);

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $expired = $_SESSION['expired'] ?? false;
        unset($_SESSION['expired']);

        // Renderizar login como standalone (sin layout)
        $meta = $template->getMeta();
        $jsDataScript = $template->getJsDataScript();
        $csrfField = $template->csrfField();
        $escape = [$template, 'escapeHtml'];

        ob_start();
        require MATER_TEMPLATES_DIR . '/admin/login.php';
        return ob_get_clean();
    }

    /**
     * POST /login — Procesar login
     */
    public function login(): ?string
    {
        // Validar CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!$this->security->validateCsrfToken($csrfToken)) {
            $_SESSION['login_error'] = 'Token de seguridad inválido. Inténtalo de nuevo.';
            header('Location: /login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Todos los campos son obligatorios.';
            header('Location: /login');
            exit;
        }

        $result = $this->auth->login($username, $password, $ip);

        if (!$result['success']) {
            $_SESSION['login_error'] = $result['error'];
            header('Location: /login');
            exit;
        }

        // Redirigir
        $redirect = $_SESSION['redirect_after_login'] ?? $result['redirect'] ?? '/admin';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    }

    /**
     * GET /logout — Cerrar sesión
     */
    public function logout(): void
    {
        $this->auth->logout();
        header('Location: /');
        exit;
    }
}
