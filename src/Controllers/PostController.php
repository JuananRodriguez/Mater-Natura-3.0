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
    public function __construct(private Database $db, private Security $security, private Auth $auth, private ?PluginManager $pluginManager = null)
    {
    }

    /**
     * GET /post — Listado paginado de posts publicados
     */
    public function index(int $page = 1): string
    {
        // Redirigir page=1 a la URL canónica
        if (isset($_GET['page']) && (int)$_GET['page'] === 1) {
            header('Location: /post', true, 301);
            exit;
        }

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
        if ($this->pluginManager) {
            $template->setPluginManager($this->pluginManager);
        }
        $template->setMetaTitle('Posts — ' . MATER_SITE_NAME);
        $template->setMetaDescription('Todos los poemas publicados en ' . MATER_SITE_NAME);
        $template->setOgType('website');

        // Canonical (page 1 sin query param)
        $canonical = rtrim(MATER_BASE_URL, '/') . '/post';
        $template->setCanonicalUrl($canonical);

        // JSON-LD CollectionPage
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Posts — ' . MATER_SITE_NAME,
            'description' => 'Todos los poemas publicados en ' . MATER_SITE_NAME,
            'url' => $canonical,
        ]);

        $template->exposeToJs('currentPage', $page);
        $template->exposeToJs('totalPages', $totalPages);
        $template->exposeToJs('hasPrevPage', $page > 1);
        $template->exposeToJs('hasNextPage', $page < $totalPages);

        // Rel prev/next para paginación
        $basePostUrl = rtrim(MATER_BASE_URL, '/') . '/post';
        if ($page > 1) {
            $template->addAlternateLink('prev', $basePostUrl . '?page=' . ($page - 1));
        }
        if ($page < $totalPages) {
            $template->addAlternateLink('next', $basePostUrl . '?page=' . ($page + 1));
        }

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
            if ($this->pluginManager) {
                $template->setPluginManager($this->pluginManager);
            }
            $template->setMetaTitle('Página no encontrada');
            return $template->render('404', ['message' => 'Este poema no ha sido escrito aún.']);
        }

        // ─── Control de visibilidad ───
        $visibility = $post['visibility'] ?? 'public';

        // Privado: solo usuarios autenticados
        if ($visibility === 'private' && !$this->auth->isAuthenticated()) {
            http_response_code(403);
            $template = new Template($this->security);
            if ($this->pluginManager) {
                $template->setPluginManager($this->pluginManager);
            }
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
                if ($this->pluginManager) {
                    $template->setPluginManager($this->pluginManager);
                }
                $template->setMetaTitle($post['title'] . ' — ' . MATER_SITE_NAME);
                return $template->render('post-password', [
                    'post' => $post,
                    'error' => $error ?? null,
                ]);
            }
        }

        // ─── Fin control de visibilidad ───

        $template = new Template($this->security);
        if ($this->pluginManager) {
            $template->setPluginManager($this->pluginManager);
        }
        $metaTitle = !empty($post['meta_title']) ? $post['meta_title'] : ($post['title'] . ' — ' . MATER_SITE_NAME);
        $template->setMetaTitle($metaTitle);
        $metaDescription = !empty($post['meta_description'])
            ? $post['meta_description']
            : mb_substr(strip_tags($post['description']), 0, 160);
        $template->setMetaDescription($metaDescription);
        $template->setOgType('article');

        // Canonical URL
        $canonical = rtrim(MATER_BASE_URL, '/') . '/' . $post['slug'];
        $template->setCanonicalUrl($canonical);

        // OG article timestamps
        $publishedTime = $post['published_at'] ?? date('Y-m-d\TH:i:s');
        $template->exposeToJs('ogPublishedTime', $publishedTime);
        $template->exposeToJs('ogModifiedTime', $post['updated_at'] ?? $publishedTime);

        if ($post['image_url']) {
            $template->setOgImage(MATER_BASE_URL . '/media/' . ltrim($post['image_url'], '/'));
        }

        // JSON-LD Article
        $authorName = $post['author_name'] ?? 'Mater-Natura';
        $articleLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'],
            'description' => mb_substr(strip_tags($post['description']), 0, 200),
            'datePublished' => $publishedTime,
            'dateModified' => $post['updated_at'] ?? $publishedTime,
            'author' => [
                '@type' => 'Person',
                'name' => $authorName,
            ],
        ];
        if ($post['image_url']) {
            $articleLd['image'] = MATER_BASE_URL . '/media/' . ltrim($post['image_url'], '/');
        }
        $template->addJsonLd($articleLd);

        // ─── Dimensiones de la imagen (para CLS) ───
        $imageWidth = null;
        $imageHeight = null;
        if (!empty($post['image_url'])) {
            $imagePath = MATER_UPLOADS_DIR . '/' . ltrim($post['image_url'], '/');
            $dimensions = false;
            if (is_file($imagePath)) {
                $dimensions = @getimagesize($imagePath);
                if ($dimensions) {
                    $imageWidth = $dimensions[0];
                    $imageHeight = $dimensions[1];
                }
            }
        }

        // Breadcrumbs + BreadcrumbList JSON-LD
        $baseUrl = rtrim(MATER_BASE_URL, '/');
        $template->setBreadcrumbs([
            ['label' => 'Inicio', 'url' => $baseUrl . '/'],
            ['label' => 'Poemas', 'url' => $baseUrl . '/post'],
            ['label' => $post['title']],
        ]);
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => $baseUrl . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Poemas', 'item' => $baseUrl . '/post'],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $canonical],
            ],
        ]);

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
            'imageWidth'   => $imageWidth,
            'imageHeight'  => $imageHeight,
        ], $post['template']);
    }
}
