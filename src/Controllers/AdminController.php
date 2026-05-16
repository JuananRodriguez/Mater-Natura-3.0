<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Auth;
use MaterNatura\Core\Database;
use MaterNatura\Core\PluginManager;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class AdminController
{
    public function __construct(private Database $db, private Security $security, private Auth $auth, private ?PluginManager $pluginManager = null)
    {
    }

    // ─── Dashboard ───

    public function dashboard(): string
    {
        $postCount = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM posts")['cnt'] ?? 0;
        $pageCount = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM pages")['cnt'] ?? 0;
        $draftCount = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM posts WHERE status = 'draft'"
        )['cnt'] ?? 0;

        // Recolectar widgets de plugins
        $dashboardWidgets = [];
        if ($this->pluginManager) {
            $widgetResults = $this->pluginManager->executeHook('admin.dashboard.widgets', []);
            foreach ($widgetResults as $result) {
                if (is_array($result) && isset($result['title'])) {
                    $dashboardWidgets[] = $result;
                }
            }
        }

        return $this->renderAdmin('dashboard', [
            'postCount' => $postCount,
            'pageCount' => $pageCount,
            'draftCount' => $draftCount,
            'dashboardWidgets' => $dashboardWidgets,
            'currentNav' => 'dashboard',
        ]);
    }

    // ─── Posts CRUD ───

    public function postList(): string
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $total = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM posts")['cnt'] ?? 0;
        $totalPages = max(1, (int) ceil($total / $perPage));

        return $this->renderAdmin('post-list', [
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'currentNav' => 'posts',
        ]);
    }

    public function postForm(?int $id = null): string
    {
        $post = null;
        if ($id) {
            $row = $this->db->fetchOne("SELECT * FROM posts WHERE id = ?", [$id]);
            $post = $row ? \MaterNatura\Models\Post::fromRow($row) : null;
        }

        return $this->renderAdmin('post-form', [
            'post' => $post,
            'contentFullWidth' => true,
            'currentNav' => 'posts',
        ]);
    }

    public function postSave(array $data): void
    {
        // Validar CSRF
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido. Inténtalo de nuevo.';
            $_SESSION['admin_error_type'] = 'csrf';
            header('Location: /admin/posts/editar' . (!empty($data['id']) ? '?id=' . (int)$data['id'] : ''));
            exit;
        }

        $id = !empty($data['id']) ? (int) $data['id'] : null;
        $title = trim($data['title'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $description = trim($data['description'] ?? '');
        $template = $data['template'] ?? 'dark';
        $status = ($data['action'] ?? '') === 'publish' ? 'published' : 'draft';
        $visibility = $data['visibility'] ?? 'public';
        $visibilityPassword = null;

        if ($visibility === 'password' && !empty($data['visibility_password'])) {
            $visibilityPassword = password_hash($data['visibility_password'], PASSWORD_ARGON2ID);
        } elseif ($visibility !== 'password' && $id) {
            // Si cambia a otro tipo de visibilidad, limpiar la contraseña
            $visibilityPassword = null;
        } elseif ($visibility === 'password' && empty($data['visibility_password']) && $id) {
            // Mantener la contraseña existente si no se proporciona una nueva
            $current = $this->db->fetchOne("SELECT visibility_password FROM posts WHERE id = ?", [$id]);
            $visibilityPassword = $current['visibility_password'] ?? null;
        }

        // Validar campos requeridos
        $errors = [];
        if ($title === '') $errors[] = 'El título es obligatorio.';
        if ($description === '') $errors[] = 'La descripción es obligatoria.';

        // Generar slug si está vacío
        if ($slug === '') {
            $slug = $this->security->sanitizeSlug($title);
        } else {
            $slug = $this->security->sanitizeSlug($slug);
        }

        // Validar slug reservado
        if ($this->security->isReservedSlug($slug)) {
            $errors[] = "'{$slug}' es un slug reservado por el sistema.";
        }

        // Validar slug único (excluyendo el propio post si es edición)
        $existing = $this->db->fetchOne(
            "SELECT id FROM posts WHERE slug = ? AND id != ? LIMIT 1",
            $id ? [$slug, $id] : [$slug, 0]
        );
        if ($existing) {
            // Añadir sufijo numérico
            $base = $slug;
            $counter = 1;
            while ($existing) {
                $slug = $base . '-' . $counter;
                $existing = $this->db->fetchOne(
                    "SELECT id FROM posts WHERE slug = ? LIMIT 1", [$slug]
                );
                $counter++;
            }
        }

        if (!empty($errors)) {
            $_SESSION['admin_error'] = implode(' ', $errors);
            $_SESSION['admin_form_data'] = $data;
            header('Location: /admin/posts/editar' . ($id ? '?id=' . $id : ''));
            exit;
        }

        // Procesar imagen si se subió una
        $imageUrl = null;
        $oldImageUrl = null;

        // Si estamos editando, recuperar la imagen anterior por si hay que borrarla
        if ($id) {
            $current = $this->db->fetchOne("SELECT image_url FROM posts WHERE id = ?", [$id]);
            $oldImageUrl = $current['image_url'] ?? null;
        }

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = new \MaterNatura\Core\UploadHandler($this->security);
            $result = $upload->upload($_FILES['image'], $slug);
            if ($result['success']) {
                $imageUrl = $result['relativePath'];

                // Eliminar la imagen anterior si existe
                if ($oldImageUrl) {
                    $upload->delete($oldImageUrl);
                }
            } else {
                $_SESSION['admin_error'] = $result['error'];
                header('Location: /admin/posts/editar' . ($id ? '?id=' . $id : ''));
                exit;
            }
        } elseif ($id) {
            // Mantener la imagen existente
            $imageUrl = $oldImageUrl;
        }

        // Meta SEO
        $metaTitle = trim($data['meta_title'] ?? '');
        $metaDescription = trim($data['meta_description'] ?? '');

        $postData = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'template' => $template,
            'status' => $status,
            'visibility' => $visibility,
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        ];

        if ($visibilityPassword !== null) {
            $postData['visibility_password'] = $visibilityPassword;
        } elseif ($id && $visibility !== 'password') {
            $postData['visibility_password'] = null;
        }

        if ($imageUrl !== null) {
            $postData['image_url'] = $imageUrl;
        }

        if ($id) {
            $this->db->update('posts', $postData, 'id = ?', [$id]);
        } else {
            $postData['user_id'] = $this->auth->getCurrentUser()['id'];
            if ($status === 'published') {
                $postData['published_at'] = date('Y-m-d H:i:s');
            }
            $this->db->insert('posts', $postData);
        }

        // Cola de regeneración del sitemap
        $this->db->insert('sitemap_queue', [
            'type' => 'post',
            'entry_id' => $id ?? (int) $this->db->getPdo()->lastInsertId(),
            'action' => $id ? 'update' : 'create',
        ]);

        $_SESSION['admin_success'] = 'Post guardado correctamente.';
        header('Location: /admin/posts');
        exit;
    }

    public function postDelete(int $id): void
    {
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/posts');
            exit;
        }

        // Cola de regeneración del sitemap
        $this->db->insert('sitemap_queue', [
            'type' => 'post',
            'entry_id' => $id,
            'action' => 'delete',
        ]);

        $this->db->delete('posts', 'id = ?', [$id]);

        $_SESSION['admin_success'] = 'Post eliminado correctamente.';
        header('Location: /admin/posts');
        exit;
    }

    // ─── Pages CRUD ───

    public function pageList(): string
    {
        $pages = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM pages p
             JOIN users u ON p.user_id = u.id
             ORDER BY p.created_at DESC"
        );

        return $this->renderAdmin('page-list', [
            'pages' => $pages,
            'currentNav' => 'pages',
        ]);
    }

    public function pageForm(?int $id = null): string
    {
        $page = null;
        if ($id) {
            $row = $this->db->fetchOne("SELECT * FROM pages WHERE id = ?", [$id]);
            $page = $row ? \MaterNatura\Models\Page::fromRow($row) : null;
        }

        return $this->renderAdmin('page-form', [
            'page' => $page,
            'isHome' => $page ? $this->db->fetchOne("SELECT id FROM pages WHERE is_home = 1") : null,
            'currentNav' => 'pages',
            'contentFullWidth' => true,
        ]);
    }

    public function pageSave(array $data): void
    {
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/pages');
            exit;
        }

        $id = !empty($data['id']) ? (int) $data['id'] : null;
        $title = trim($data['title'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $content = $data['content'] ?? '';
        $template = $data['template'] ?? 'dark';
        $status = $data['status'] ?? 'published';

        $errors = [];
        if ($title === '') $errors[] = 'El título es obligatorio.';
        if ($content === '') $errors[] = 'El contenido es obligatorio.';

        if ($slug === '') {
            $slug = $this->security->sanitizeSlug($title);
        } else {
            $slug = $this->security->sanitizeSlug($slug);
        }

        // Solo comprobar slug reservado si es nuevo o ha cambiado
        if ($this->security->isReservedSlug($slug)) {
            if (!$id) {
                $errors[] = "'{$slug}' es un slug reservado por el sistema.";
            } else {
                $current = $this->db->fetchOne("SELECT slug FROM pages WHERE id = ?", [$id]);
                if (!$current || $current['slug'] !== $slug) {
                    $errors[] = "'{$slug}' es un slug reservado por el sistema.";
                }
            }
        }

        // Slug único entre pages
        $existing = $this->db->fetchOne(
            "SELECT id FROM pages WHERE slug = ? AND id != ? LIMIT 1",
            $id ? [$slug, $id] : [$slug, 0]
        );
        if ($existing) {
            $errors[] = "El slug '{$slug}' ya está en uso.";
        }

        if (!empty($errors)) {
            $_SESSION['admin_error'] = implode(' ', $errors);
            header('Location: /admin/pages/editar' . ($id ? '?id=' . $id : ''));
            exit;
        }

        // Meta SEO
        $metaTitle = trim($data['meta_title'] ?? '');
        $metaDescription = trim($data['meta_description'] ?? '');

        $pageData = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'template' => $template,
            'status' => $status,
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        ];

        if ($id) {
            $this->db->update('pages', $pageData, 'id = ?', [$id]);
        } else {
            $pageData['user_id'] = $this->auth->getCurrentUser()['id'];
            $pageData['published_at'] = date('Y-m-d H:i:s');
            $id = $this->db->insert('pages', $pageData);
        }

        // Cola de regeneración del sitemap
        $this->db->insert('sitemap_queue', [
            'type' => 'page',
            'entry_id' => $id,
            'action' => 'update',
        ]);

        $_SESSION['admin_success'] = 'Página guardada correctamente.';
        header('Location: /admin/pages');
        exit;
    }

    public function pageDelete(int $id): void
    {
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/pages');
            exit;
        }

        // No permitir eliminar la home
        $page = $this->db->fetchOne("SELECT is_home FROM pages WHERE id = ?", [$id]);
        if ($page && $page['is_home']) {
            $_SESSION['admin_error'] = 'No puedes eliminar la página de inicio.';
            header('Location: /admin/pages');
            exit;
        }

        // Cola de regeneración del sitemap
        $this->db->insert('sitemap_queue', [
            'type' => 'page',
            'entry_id' => $id,
            'action' => 'delete',
        ]);

        $this->db->delete('pages', 'id = ?', [$id]);

        $_SESSION['admin_success'] = 'Página eliminada correctamente.';
        header('Location: /admin/pages');
        exit;
    }

    // ─── Plugins ───

    public function plugins(): string
    {
        $plugins = $this->db->fetchAll("SELECT * FROM plugins ORDER BY name ASC");

        return $this->renderAdmin('plugins', [
            'plugins' => $plugins,
            'currentNav' => 'plugins',
        ]);
    }

    /**
     * Activa un plugin por slug.
     */
    public function pluginActivate(string $slug): void
    {
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/plugins');
            exit;
        }

        if ($this->pluginManager === null) {
            $_SESSION['admin_error'] = 'El sistema de plugins no está disponible.';
            header('Location: /admin/plugins');
            exit;
        }

        $success = $this->pluginManager->activate($slug);
        if ($success) {
            $_SESSION['admin_success'] = 'Plugin activado correctamente.';
        } else {
            $_SESSION['admin_error'] = 'No se pudo activar el plugin.';
        }

        header('Location: /admin/plugins');
        exit;
    }

    /**
     * Desactiva un plugin por slug.
     */
    public function pluginDeactivate(string $slug): void
    {
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/plugins');
            exit;
        }

        if ($this->pluginManager === null) {
            $_SESSION['admin_error'] = 'El sistema de plugins no está disponible.';
            header('Location: /admin/plugins');
            exit;
        }

        $success = $this->pluginManager->deactivate($slug);
        if ($success) {
            $_SESSION['admin_success'] = 'Plugin desactivado correctamente.';
        } else {
            $_SESSION['admin_error'] = 'No se pudo desactivar el plugin.';
        }

        header('Location: /admin/plugins');
        exit;
    }

    // ─── Settings ───

    public function settings(): string
    {
        $pages = $this->db->fetchAll("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title");
        $currentHome = $this->db->fetchOne("SELECT id, title FROM pages WHERE is_home = 1");

        return $this->renderAdmin('settings', [
            'pages' => $pages,
            'currentHome' => $currentHome,
            'currentNav' => 'settings',
        ]);
    }

    public function saveSettings(array $data): void
    {
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/ajustes');
            exit;
        }

        // Cambiar página de inicio
        $homeId = (int) ($data['home_page_id'] ?? 0);
        if ($homeId > 0) {
            $this->db->update('pages', ['is_home' => 0], 'is_home = 1');
            $this->db->update('pages', ['is_home' => 1], 'id = ?', [$homeId]);
        }

        // Regenerar sitemap si se solicita
        if (isset($data['regenerate_sitemap'])) {
            $sitemap = new \MaterNatura\Controllers\SitemapController($this->db);
            $sitemap->regenerate();
            $_SESSION['admin_success'] = 'Sitemap regenerado correctamente.';
        } else {
            $_SESSION['admin_success'] = 'Ajustes guardados correctamente.';
        }

        header('Location: /admin/ajustes');
        exit;
    }

    // ─── AJAX Upload para el editor ───

    /**
     * POST /admin/upload-image — Subida AJAX de imagenes para el editor WYSIWYG
     */
    public function uploadImage(): void
    {
        header('Content-Type: application/json');

        if (empty($_FILES['image'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No se recibió ninguna imagen.']);
            exit;
        }

        $upload = new \MaterNatura\Core\UploadHandler($this->security);
        // Generate a unique slug for the editor image
        $slug = 'editor-' . bin2hex(random_bytes(6));
        $result = $upload->upload($_FILES['image'], $slug);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode(['error' => $result['error'] ?? 'Error al subir la imagen.']);
            exit;
        }

        echo json_encode([
            'url' => '/media/' . $result['relativePath'],
        ]);
        exit;
    }

    // ─── Usuarios CRUD ───

    public function userList(): string
    {
        $users = $this->db->fetchAll(
            "SELECT id, username, email, role, last_login_at, created_at
             FROM users
             ORDER BY created_at DESC"
        );

        return $this->renderAdmin('user-list', [
            'users' => $users,
            'currentNav' => 'users',
        ]);
    }

    public function userForm(?int $id = null): string
    {
        $user = null;
        if ($id) {
            $row = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
            if (!$row) {
                $_SESSION['admin_error'] = 'Usuario no encontrado.';
                header('Location: /admin/usuarios');
                exit;
            }
            $user = $row;
        }

        return $this->renderAdmin('user-form', [
            'user' => $user,
            'currentNav' => 'users',
        ]);
    }

    public function userSave(array $data): void
    {
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/usuarios');
            exit;
        }

        $id = !empty($data['id']) ? (int) $data['id'] : null;
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $role = $data['role'] ?? 'editor';
        $password = $data['password'] ?? '';

        $errors = [];

        // Validaciones comunes
        if ($username === '') {
            $errors[] = 'El nombre de usuario es obligatorio.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $errors[] = 'El nombre de usuario debe tener entre 3 y 50 caracteres (solo letras, números y guiones bajos).';
        }

        if ($email === '' || !$this->security->validateEmail($email)) {
            $errors[] = 'El email no es válido.';
        }

        if (!in_array($role, ['admin', 'editor'], true)) {
            $errors[] = 'El rol no es válido.';
        }

        if ($id) {
            // Edición: comprobar unicidad de username y email (excluyendo al propio usuario)
            $existingUsername = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]
            );
            if ($existingUsername) {
                $errors[] = 'Ya existe otro usuario con ese nombre.';
            }

            $existingEmail = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]
            );
            if ($existingEmail) {
                $errors[] = 'Ya existe otro usuario con ese email.';
            }

            // No permitir cambiar el rol del último admin
            if ($role !== 'admin') {
                $current = $this->db->fetchOne("SELECT role FROM users WHERE id = ?", [$id]);
                if ($current && $current['role'] === 'admin') {
                    $adminCount = $this->db->fetchOne(
                        "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'"
                    )['cnt'] ?? 0;
                    if ($adminCount <= 1) {
                        $errors[] = 'No puedes cambiar el rol del último administrador.';
                    }
                }
            }
        } else {
            // Nuevo usuario: comprobar unicidad
            $existingUsername = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ?", [$username]
            );
            if ($existingUsername) {
                $errors[] = 'Ya existe un usuario con ese nombre.';
            }

            $existingEmail = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ?", [$email]
            );
            if ($existingEmail) {
                $errors[] = 'Ya existe un usuario con ese email.';
            }

            // Validar contraseña
            if ($password === '') {
                $errors[] = 'La contraseña es obligatoria para nuevos usuarios.';
            } else {
                $pwCheck = $this->security->validatePasswordStrength($password);
                if (!$pwCheck['valid']) {
                    $errors[] = $pwCheck['message'];
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['admin_error'] = implode(' ', $errors);
            $redirect = $id ? '/admin/usuarios/editar?id=' . $id : '/admin/usuarios/editar';
            header('Location: ' . $redirect);
            exit;
        }

        if ($id) {
            $updateData = [
                'email' => $email,
                'role' => $role,
            ];
            $this->db->update('users', $updateData, 'id = ?', [$id]);
            $_SESSION['admin_success'] = 'Usuario actualizado correctamente.';
        } else {
            $insertData = [
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
                'role' => $role,
            ];
            $this->db->insert('users', $insertData);
            $_SESSION['admin_success'] = 'Usuario creado correctamente.';
        }

        header('Location: /admin/usuarios');
        exit;
    }

    public function userDelete(int $id): void
    {
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/usuarios');
            exit;
        }

        // No permitir eliminarse a uno mismo
        $currentUser = $this->auth->getCurrentUser();
        if ($currentUser && (int) $currentUser['id'] === $id) {
            $_SESSION['admin_error'] = 'No puedes eliminar tu propio usuario.';
            header('Location: /admin/usuarios');
            exit;
        }

        // No permitir eliminar al último admin
        $target = $this->db->fetchOne("SELECT role FROM users WHERE id = ?", [$id]);
        if (!$target) {
            $_SESSION['admin_error'] = 'Usuario no encontrado.';
            header('Location: /admin/usuarios');
            exit;
        }
        if ($target['role'] === 'admin') {
            $adminCount = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM users WHERE role = 'admin'"
            )['cnt'] ?? 0;
            if ($adminCount <= 1) {
                $_SESSION['admin_error'] = 'No puedes eliminar al único administrador.';
                header('Location: /admin/usuarios');
                exit;
            }
        }

        $this->db->delete('users', 'id = ?', [$id]);

        $_SESSION['admin_success'] = 'Usuario eliminado correctamente.';
        header('Location: /admin/usuarios');
        exit;
    }

    public function userResetPassword(array $data): void
    {
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/usuarios');
            exit;
        }

        $id = (int) ($data['id'] ?? 0);
        $newPassword = $data['new_password'] ?? '';

        if ($id < 1) {
            $_SESSION['admin_error'] = 'Usuario no válido.';
            header('Location: /admin/usuarios');
            exit;
        }

        // Validar que el usuario existe
        $user = $this->db->fetchOne("SELECT id, username FROM users WHERE id = ?", [$id]);
        if (!$user) {
            $_SESSION['admin_error'] = 'Usuario no encontrado.';
            header('Location: /admin/usuarios');
            exit;
        }

        // Generar contraseña si no se proporciona una
        if ($newPassword === '') {
            $newPassword = bin2hex(random_bytes(4)) . '!' . random_int(100, 999);
        }

        // Validar fortaleza
        $pwCheck = $this->security->validatePasswordStrength($newPassword);
        if (!$pwCheck['valid']) {
            $_SESSION['admin_error'] = $pwCheck['message'];
            header('Location: /admin/usuarios/editar?id=' . $id);
            exit;
        }

        $this->db->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_ARGON2ID),
        ], 'id = ?', [$id]);

        // Mostrar la contraseña una única vez en pantalla
        $_SESSION['admin_success'] = 'Contraseña restablecida para ' . $this->security->escapeHtml($user['username']) . '.';
        $_SESSION['new_password'] = $newPassword;
        header('Location: /admin/usuarios/editar?id=' . $id);
        exit;
    }

    // ─── Theme Editor (Apariencia) ───

    /**
     * GET /admin/apariencia — Formulario de personalización del theme
     */
    public function themeSettings(): string
    {
        $themeSettings = [
            'logo'    => theme_setting('theme_logo') ?? $this->getDefaultLogo(),
            'nav'     => theme_setting('theme_nav') ?? $this->getDefaultNav(),
            'social'  => theme_setting('theme_social') ?? $this->getDefaultSocial(),
            'footer'  => theme_setting('theme_footer') ?? $this->getDefaultFooter(),
        ];

        return $this->renderAdmin('theme-settings', [
            'themeSettings' => $themeSettings,
            'currentNav'    => 'theme',
        ]);
    }

    /**
     * POST /admin/apariencia — Guardar ajustes del theme
     */
    public function saveThemeSettings(array $data): void
    {
        if (!$this->security->validateCsrfToken($data['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/apariencia');
            exit;
        }

        // ── Logo ──
        $logoType = $data['logo_type'] ?? 'text';
        if ($logoType === 'image' && !empty($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $upload = new \MaterNatura\Core\UploadHandler($this->security);
            $result = $upload->upload($_FILES['logo_image'], 'theme-logo');
            if ($result['success']) {
                save_theme_setting($this->db, 'theme_logo', [
                    'type'   => 'image',
                    'path'   => $result['relativePath'],
                    'alt'    => trim($data['logo_alt'] ?? MATER_SITE_NAME),
                    'width'  => $result['width'],
                    'height' => $result['height'],
                ]);
            } else {
                $_SESSION['admin_error'] = 'Error al subir el logo: ' . $result['error'];
                header('Location: /admin/apariencia');
                exit;
            }
        } elseif ($logoType === 'text') {
            save_theme_setting($this->db, 'theme_logo', [
                'type' => 'text',
                'text' => trim($data['logo_text'] ?: MATER_SITE_NAME),
            ]);
        }

        // ── Navegación ──
        $navItems = [];
        $labels = $data['nav_label'] ?? [];
        $urls = $data['nav_url'] ?? [];
        $scopes = $data['nav_scope'] ?? [];
        $targets = $data['nav_target'] ?? [];
        $kept = $data['nav_keep'] ?? [];

        foreach ($kept as $i => $keep) {
            if (!isset($labels[$i], $urls[$i])) continue;
            $label = trim($labels[$i]);
            $url = trim($urls[$i]);
            if ($label === '' || $url === '') continue;

            $navItems[] = [
                'id'     => 'nav_' . bin2hex(random_bytes(4)),
                'label'  => $label,
                'url'    => $url,
                'target' => ($targets[$i] ?? '_self') === '_blank' ? '_blank' : '_self',
                'scope'  => $scopes[$i] ?? 'both',
            ];
        }

        save_theme_setting($this->db, 'theme_nav', $navItems);

        // ── Redes Sociales ──
        $socialItems = [];
        $platforms = $data['social_platform'] ?? [];
        $socialUrls = $data['social_url'] ?? [];
        $socialLabels = $data['social_label'] ?? [];
        $socialKept = $data['social_keep'] ?? [];

        foreach ($socialKept as $i => $keep) {
            if (!isset($platforms[$i], $socialUrls[$i])) continue;
            $url = trim($socialUrls[$i]);
            if ($url === '') continue;

            $socialItems[] = [
                'id'       => 'social_' . bin2hex(random_bytes(4)),
                'platform' => $platforms[$i] ?? 'custom',
                'url'      => $url,
                'label'    => trim($socialLabels[$i] ?? ''),
            ];
        }

        save_theme_setting($this->db, 'theme_social', $socialItems);

        // ── Footer ──
        save_theme_setting($this->db, 'theme_footer', [
            'copyright' => trim($data['footer_copyright'] ?? ''),
        ]);

        // Limpiar caché de settings para que se refleje en la vista previa
        // El helper theme_setting() cachea en estática, se limpia al refrescar la página
        $_SESSION['admin_success'] = 'Ajustes del theme guardados correctamente.';
        header('Location: /admin/apariencia');
        exit;
    }

    /**
     * Valores por defecto del logo
     */
    private function getDefaultLogo(): array
    {
        return [
            'type' => 'text',
            'text' => 'MATER NATURA',
        ];
    }

    /**
     * Valores por defecto de navegación
     */
    private function getDefaultNav(): array
    {
        return [
            ['id' => 'nav_1', 'label' => 'día',       'url' => '/post?tag=dia',   'target' => '_self', 'scope' => 'both'],
            ['id' => 'nav_2', 'label' => 'noche',     'url' => '/post?tag=noche', 'target' => '_self', 'scope' => 'both'],
            ['id' => 'nav_3', 'label' => 'info',      'url' => '/info',           'target' => '_self', 'scope' => 'both'],
        ];
    }

    /**
     * Valores por defecto de redes sociales
     */
    private function getDefaultSocial(): array
    {
        return [
            ['id' => 'social_1', 'platform' => 'instagram', 'url' => 'https://www.instagram.com/mater_natura/', 'label' => 'Instagram'],
        ];
    }

    /**
     * Valores por defecto del footer
     */
    private function getDefaultFooter(): array
    {
        return [
            'copyright' => MATER_SITE_NAME,
        ];
    }

    // ─── Privados ───

    /**
     * Renderiza una página del admin con el layout completo.
     * Incluye dinámicamente los items del menú de plugins activos.
     */
    public function renderAdmin(string $view, array $data = []): string
    {
        $template = new Template($this->security);
        $template->setMetaTitle(ucfirst($view) . ' — ' . MATER_SITE_NAME);

        $user = $this->auth->getCurrentUser();
        $template->exposeToJs('user', $user);

        $adminError = $_SESSION['admin_error'] ?? null;
        $adminSuccess = $_SESSION['admin_success'] ?? null;
        $template->exposeToJs('adminError', $adminError);
        $template->exposeToJs('adminSuccess', $adminSuccess);

        unset($_SESSION['admin_error'], $_SESSION['admin_success']);

        // Recolectar items del menú de plugins activos vía hook
        $pluginMenuItems = [];
        if ($this->pluginManager) {
            $menuResults = $this->pluginManager->executeHook('admin.menu.add', []);
            foreach ($menuResults as $result) {
                if (is_array($result) && isset($result['url'])) {
                    $pluginMenuItems[] = $result;
                }
            }
        }

        // Renderizar la vista de contenido
        $viewFile = MATER_TEMPLATES_DIR . '/admin/' . $view . '.php';
        ob_start();
        $escape = [$template, 'escapeHtml'];
        $csrfField = $template->csrfField();
        extract($data);
        require $viewFile;
        $content = ob_get_clean();

        // Renderizar el layout admin (standalone, sin wrapper)
        $layoutFile = MATER_TEMPLATES_DIR . '/admin/layout.php';
        $meta = $template->getMeta();
        $jsDataScript = $template->getJsDataScript();
        $escape = [$template, 'escapeHtml'];
        $pageTitle = $meta['title'] ?? 'Dashboard';
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }
}
