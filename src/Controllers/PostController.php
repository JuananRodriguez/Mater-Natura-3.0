<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Auth;
use MaterNatura\Core\Database;
use MaterNatura\Core\PluginManager;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class PostController
{
    private Database $db;
    private Security $security;
    private Auth $auth;
    private ?PluginManager $pluginManager = null;

    public function __construct(Database $db, Security $security, Auth $auth, ?PluginManager $pluginManager = null)
    {
        $this->db = $db;
        $this->security = $security;
        $this->auth = $auth;
        $this->pluginManager = $pluginManager;
    }

    /**
     * GET /post — Listado paginado de posts publicados
     */
    public function index(int $page = 1): string
    {
        $perPage = MATER_POSTS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.status = 'published' AND p.visibility = 'public'
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );

        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM posts WHERE status = 'published' AND visibility = 'public'"
        );
        $totalPosts = (int) ($total['cnt'] ?? 0);
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));

        $template = new Template($this->security);
        $template->setMetaTitle('Posts — ' . MATER_SITE_NAME);
        $template->setMetaDescription('Todos los poemas publicados en ' . MATER_SITE_NAME);
        $template->setOgType('website');

        $template->exposeToJs('currentPage', $page);
        $template->exposeToJs('totalPages', $totalPages);

        return $template->render('post-list', [
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
        ]);
    }

    /**
     * GET /{slug} — Post individual
     */
    public function show(string $slug): string
    {
        $post = $this->db->fetchOne(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.slug = ? AND p.status = 'published'
             LIMIT 1",
            [$slug]
        );

        if (!$post) {
            http_response_code(404);
            $template = new Template($this->security);
            $template->setMetaTitle('Página no encontrada');
            return $template->render('404', ['message' => 'Este poema no ha sido escrito aún.']);
        }

        // ─── Control de visibilidad ───
        $visibility = $post['visibility'] ?? 'public';

        // Privado: solo usuarios autenticados
        if ($visibility === 'private' && !$this->auth->isAuthenticated()) {
            http_response_code(403);
            $template = new Template($this->security);
            $template->setMetaTitle('Acceso restringido — ' . MATER_SITE_NAME);
            return $template->render('403', [
                'message' => 'Este contenido es privado. Inicia sesión para acceder.',
                'loginUrl' => '/login',
            ]);
        }

        // Protegido con contraseña
        if ($visibility === 'password') {
            $verified = $_SESSION['post_password_' . $post['id']] ?? false;

            if (!$verified) {
                // Procesar envío de contraseña
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_password'])) {
                    if ($post['visibility_password'] && password_verify($_POST['post_password'], $post['visibility_password'])) {
                        $_SESSION['post_password_' . $post['id']] = true;
                        // Redirigir a la misma URL para limpiar POST
                        header('Location: /' . $slug);
                        exit;
                    } else {
                        $error = 'Contraseña incorrecta.';
                    }
                }

                http_response_code(403);
                $template = new Template($this->security);
                $template->setMetaTitle($post['title'] . ' — ' . MATER_SITE_NAME);
                return $template->render('post-password', [
                    'post' => $post,
                    'error' => $error ?? null,
                ]);
            }
        }

        // ─── Fin control de visibilidad ───

        $template = new Template($this->security);
        $template->setMetaTitle($post['title'] . ' — ' . MATER_SITE_NAME);
        $template->setMetaDescription(mb_substr(strip_tags($post['description']), 0, 160));
        $template->setOgType('article');

        if ($post['image_url']) {
            $template->setOgImage(MATER_BASE_URL . '/media/' . ltrim($post['image_url'], '/'));
        }

        $template->exposeToJs('postSlug', $post['slug']);

        // Ejecutar hooks de plugin (ej: comentarios)
        $commentsHtml = '';
        if ($this->pluginManager) {
            $context = [
                'post'     => $post,
                'db'       => $this->db,
                'security' => $this->security,
                'escape'   => [$this->security, 'escapeHtml'],
            ];
            $results = $this->pluginManager->executeHook('entry.render.after', $context);
            if (!empty($results)) {
                $commentsHtml = implode('', $results);
            }
        }

        return $template->render('post-single', [
            'post'         => $post,
            'commentsHtml' => $commentsHtml,
        ], $post['template']);
    }
}
