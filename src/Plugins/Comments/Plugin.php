<?php

declare(strict_types=1);

namespace MaterNatura\Plugins\Comments;

use MaterNatura\Core\Auth;
use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;
use MaterNatura\Plugins\PluginInterface;

class Plugin implements PluginInterface
{
    private ?Database $db = null;
    private ?Security $security = null;
    private ?Auth $auth = null;

    public function setDatabase(Database $db): void
    {
        $this->db = $db;
    }

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    public function setAuth(Auth $auth): void
    {
        $this->auth = $auth;
    }

    public function getMeta(): array
    {
        return [
            'name'        => 'Comentarios',
            'version'     => '1.0.0',
            'description' => 'Añade comentarios anónimos o con nombre a los posts.',
            'slug'        => 'comments',
        ];
    }

    public function registerHooks(): array
    {
        return [
            ['hook' => 'entry.render.after', 'priority' => 10],
            ['hook' => 'comment.submit',      'priority' => 10],
            ['hook' => 'admin.menu.add',      'priority' => 20],
            ['hook' => 'admin.dashboard.widgets', 'priority' => 10],
        ];
    }

    public function onActivate(): void
    {
        if ($this->db === null) {
            return;
        }

        // Ejecutar migración
        $sqlFile = __DIR__ . '/migrations/001_comments.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if (!empty(trim($sql))) {
                $statements = explode(';', $sql);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->db->query($statement);
                    }
                }
            }
        }
    }

    public function onDeactivate(): void
    {
        // No destruimos datos al desactivar. Tabla se queda.
    }

    // ─── Handler Methods ───

    /**
     * Hook: entry.render.after
     * Renderiza la sección de comentarios tras el contenido del post.
     */
    public function onEntryRenderAfter(array $context): string
    {
        if (!isset($context['post']) || $this->db === null) {
            return '';
        }

        $post = $context['post'];
        $escape = $context['escape'] ?? fn($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Obtener comentarios aprobados
        $comments = $this->db->fetchAll(
            "SELECT * FROM comments
             WHERE post_id = ? AND status = 'approved'
             ORDER BY created_at ASC",
            [(int) $post['id']]
        );

        // Flash message desde sesión
        $flashMessage = null;
        if (isset($_SESSION['comment_flash'])) {
            $flashMessage = $_SESSION['comment_flash'];
            unset($_SESSION['comment_flash']);
        }

        // CSRF field
        $csrfField = $this->security?->getCsrfField() ?? '';

        // Renderizar template
        $templateFile = __DIR__ . '/templates/comments-section.php';
        if (!file_exists($templateFile)) {
            return '';
        }

        ob_start();
        require $templateFile;
        return (string) ob_get_clean();
    }

    /**
     * Hook: comment.submit
     * Procesa el envío de un comentario.
     */
    public function onCommentSubmit(array $context): ?string
    {
        if ($this->db === null || $this->security === null) {
            $_SESSION['comment_flash'] = [
                'type' => 'error',
                'text' => 'Error interno. Inténtalo de nuevo.',
            ];
            return null;
        }

        $postId = (int) ($context['post_id'] ?? 0);
        $authorName = trim((string) ($context['author_name'] ?? ''));
        $authorEmail = trim((string) ($context['author_email'] ?? ''));
        $authorWebsite = trim((string) ($context['author_website'] ?? ''));
        $content = trim((string) ($context['content'] ?? ''));

        // Validar
        if ($postId <= 0) {
            $_SESSION['comment_flash'] = ['type' => 'error', 'text' => 'Post inválido.'];
            return null;
        }

        if (empty($content)) {
            $_SESSION['comment_flash'] = ['type' => 'error', 'text' => 'El comentario no puede estar vacío.'];
            return null;
        }

        if (mb_strlen($content) > 2000) {
            $_SESSION['comment_flash'] = ['type' => 'error', 'text' => 'El comentario es demasiado largo (máx. 2000 caracteres).'];
            return null;
        }

        if (empty($authorName)) {
            $authorName = 'Anónimo';
        }

        // Verificar que el post existe
        $post = $this->db->fetchOne(
            "SELECT id FROM posts WHERE id = ? AND status = 'published'",
            [$postId]
        );

        if (!$post) {
            $_SESSION['comment_flash'] = ['type' => 'error', 'text' => 'El post no existe.'];
            return null;
        }

        // Guardar
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        try {
            $this->db->insert('comments', [
                'post_id'        => $postId,
                'author_name'    => $this->security->sanitizeString($authorName),
                'author_email'   => $authorEmail ? $this->security->sanitizeString($authorEmail) : null,
                'author_website' => $authorWebsite ? $this->security->sanitizeString($authorWebsite) : null,
                'content'        => $this->security->sanitizeString($content),
                'ip_address'     => $ipAddress,
                'status'         => 'pending',
            ]);

            $_SESSION['comment_flash'] = [
                'type' => 'success',
                'text' => '¡Comentario recibido! Se publicará tras ser revisado.',
            ];
        } catch (\Throwable $e) {
            error_log("Comments plugin error: " . $e->getMessage());
            $_SESSION['comment_flash'] = [
                'type' => 'error',
                'text' => 'Error al guardar el comentario. Inténtalo de nuevo.',
            ];
        }

        return null;
    }

    /**
     * Hook: admin.menu.add
     * Añade enlace al menú lateral del admin.
     */
    public function onAdminMenuAdd(array $context): array
    {
        $pending = 0;
        if ($this->db !== null) {
            $row = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM comments WHERE status = 'pending'"
            );
            $pending = (int) ($row['cnt'] ?? 0);
        }

        return [
            'label' => 'Comentarios' . ($pending > 0 ? " ({$pending})" : ''),
            'url'   => '/admin/comments',
            'icon'  => 'comment-bubble',
        ];
    }

    /**
     * Hook: admin.dashboard.widgets
     * Muestra contador de comentarios pendientes en el dashboard.
     */
    public function onAdminDashboardWidgets(array $context): array
    {
        if ($this->db === null) {
            return [];
        }

        $pending = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM comments WHERE status = 'pending'"
        );
        $approved = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM comments WHERE status = 'approved'"
        );
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM comments"
        );

        return [
            'title'   => 'Comentarios',
            'content' => sprintf(
                '%d pendientes · %d aprobados · %d total',
                (int) ($pending['cnt'] ?? 0),
                (int) ($approved['cnt'] ?? 0),
                (int) ($total['cnt'] ?? 0)
            ),
            'icon' => 'comment-bubble',
        ];
    }

    // ─── Helpers ───

    /**
     * Obtiene comentarios aprobados para un post.
     */
    public function getApprovedComments(int $postId): array
    {
        if ($this->db === null) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT * FROM comments
             WHERE post_id = ? AND status = 'approved'
             ORDER BY created_at ASC",
            [$postId]
        );
    }

    // ─── Admin Handlers ───

    /**
     * Handler: adminCommentsList
     * Renderiza el panel de administración de comentarios.
     */
    public function adminCommentsList(): string
    {
        if ($this->db === null || $this->auth === null || $this->security === null) {
            return 'Plugin no inicializado.';
        }

        // Obtener comentarios con información del post
        $comments = $this->db->fetchAll(
            "SELECT c.*, p.title as post_title, p.slug as post_slug
             FROM comments c
             JOIN posts p ON c.post_id = p.id
             ORDER BY c.created_at DESC"
        );

        $template = new Template($this->security);
        $template->setMetaTitle('Comentarios — ' . MATER_SITE_NAME);

        $user = $this->auth->getCurrentUser();
        $template->exposeToJs('user', $user);

        $adminError = $_SESSION['admin_error'] ?? null;
        $adminSuccess = $_SESSION['admin_success'] ?? null;
        unset($_SESSION['admin_error'], $_SESSION['admin_success']);

        // Renderizar vista admin del plugin
        $viewFile = __DIR__ . '/templates/admin-comments.php';
        ob_start();
        $escape = [$template, 'escapeHtml'];
        $csrfField = $template->csrfField();
        require $viewFile;
        $content = ob_get_clean();

        // Envolver en layout admin
        $layoutFile = MATER_TEMPLATES_DIR . '/admin/layout.php';
        $meta = $template->getMeta();
        $jsDataScript = $template->getJsDataScript();
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    /**
     * Handler: adminCommentsApprove
     * Aprueba un comentario pendiente.
     */
    public function adminCommentsApprove(int $id): void
    {
        if ($this->db === null) {
            return;
        }

        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/comments');
            exit;
        }

        $this->db->update('comments', ['status' => 'approved'], 'id = ?', [$id]);
        $_SESSION['admin_success'] = 'Comentario aprobado correctamente.';
        header('Location: /admin/comments');
        exit;
    }

    /**
     * Handler: adminCommentsDelete
     * Elimina un comentario definitivamente.
     */
    public function adminCommentsDelete(int $id): void
    {
        if ($this->db === null) {
            return;
        }

        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/comments');
            exit;
        }

        $this->db->delete('comments', 'id = ?', [$id]);
        $_SESSION['admin_success'] = 'Comentario eliminado correctamente.';
        header('Location: /admin/comments');
        exit;
    }
}
