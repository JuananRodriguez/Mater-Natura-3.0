<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Auth;
use MaterNatura\Core\ComponentBuilder\ComponentManager;
use MaterNatura\Core\ComponentBuilder\ComponentRenderer;
use MaterNatura\Core\ComponentBuilder\Components\ColumnsComponent;
use MaterNatura\Core\ComponentBuilder\Components\DividerComponent;
use MaterNatura\Core\ComponentBuilder\Components\GalleryComponent;
use MaterNatura\Core\ComponentBuilder\Components\HeroComponent;
use MaterNatura\Core\ComponentBuilder\Components\HTMLComponent;
use MaterNatura\Core\ComponentBuilder\Components\ImageComponent;
use MaterNatura\Core\ComponentBuilder\Components\QuoteComponent;
use MaterNatura\Core\ComponentBuilder\Components\SpacerComponent;
use MaterNatura\Core\ComponentBuilder\Components\TextComponent;
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
     * GET /post — Listado completo de posts publicados (sin paginación)
     */
    public function index(): string
    {
        // ─── Filtro por tag ───
        $tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
        $templateFilter = match ($tag) {
            'dia'   => 'light',
            'noche' => 'dark',
            default => null,
        };

        $where = "WHERE p.status = 'published' AND p.visibility = 'public'";
        $params = [];

        if ($templateFilter !== null) {
            $where .= " AND p.template = ?";
            $params[] = $templateFilter;
        }

        // Primero obtenemos el total para mostrarlo
        $countResult = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM posts p {$where}",
            $params
        );
        $totalPosts = (int)($countResult['total'] ?? 0);

        // Solo cargamos los primeros N posts; el resto via AJAX scroll infinito
        $initialLimit = 6;
        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             {$where}
             ORDER BY p.published_at DESC
             LIMIT ?",
            array_merge($params, [$initialLimit])
        );

        $template = new Template($this->security);
        if ($this->pluginManager) {
            $template->setPluginManager($this->pluginManager);
        }
        $template->setMetaTitle('Posts — ' . MATER_SITE_NAME);
        $template->setMetaDescription('Todos los poemas publicados en ' . MATER_SITE_NAME);
        $template->setOgType('website');

        // Canonical
        $canonical = rtrim(MATER_BASE_URL, '/') . '/post';
        $template->setCanonicalUrl($canonical);

        // JSON-LD CollectionPage
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Posts — ' . MATER_SITE_NAME,
            'description' => 'Todos los poemas publicados en ' . MATER_SITE_NAME,
        ]);

        // Determinar layout según tag
        $layout = $templateFilter ?? 'dark';

        return $template->render('post-list', [
            'posts'      => $posts,
            'totalPosts' => (int)$totalPosts,
            'tag'        => $tag,
        ], $layout);
    }

    /**
     * GET /post/fragment — Fragmento HTML para scroll infinito
     */
    public function fragment(): void
    {
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $limit = max(1, min(10, (int)($_GET['limit'] ?? 6)));

        $tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
        $templateFilter = match ($tag) {
            'dia'   => 'light',
            'noche' => 'dark',
            default => null,
        };

        $where = "WHERE p.status = 'published' AND p.visibility = 'public'";
        $params = [];

        if ($templateFilter !== null) {
            $where .= " AND p.template = ?";
            $params[] = $templateFilter;
        }

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             {$where}
             ORDER BY p.published_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Devolvemos HTML plano (sin layout)
        $template = new Template($this->security);
        echo $template->render('post-list-fragment', [
            'posts'  => $posts,
            'offset' => $offset,
        ], 'raw');
        exit;
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

        // ─── Component Builder: renderizar componentes si existen ───
        $componentsHtml = '';
        if (!empty($post['content_components'])) {
            $manager = new ComponentManager();
            $manager->register(new TextComponent());
            $manager->register(new ImageComponent());
            $manager->register(new HeroComponent());
            $manager->register(new GalleryComponent());
            $manager->register(new ColumnsComponent());
            $manager->register(new QuoteComponent());
            $manager->register(new DividerComponent());
            $manager->register(new SpacerComponent());
            $manager->register(new HTMLComponent());
            $renderer = new ComponentRenderer($manager);

            $decoded = json_decode($post['content_components'], true);
            if (is_array($decoded)) {
                $componentsHtml = $renderer->render($decoded);
            }
        }

        // ─── Posts anterior y siguiente (mismo template) ───
        $prevPost = $this->db->fetchOne(
            "SELECT * FROM posts
             WHERE status = 'published' AND visibility = 'public'
               AND template = ?
               AND (published_at < ? OR (published_at = ? AND id < ?))
             ORDER BY published_at DESC, id DESC
             LIMIT 1",
            [$post['template'], $post['published_at'], $post['published_at'], $post['id']]
        );

        $nextPost = $this->db->fetchOne(
            "SELECT * FROM posts
             WHERE status = 'published' AND visibility = 'public'
               AND template = ?
               AND (published_at > ? OR (published_at = ? AND id > ?))
             ORDER BY published_at ASC, id ASC
             LIMIT 1",
            [$post['template'], $post['published_at'], $post['published_at'], $post['id']]
        );

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
            'post'           => $post,
            'prevPost'       => $prevPost,
            'nextPost'       => $nextPost,
            'componentsHtml' => $componentsHtml,
            'commentsHtml'   => $commentsHtml,
            'imageWidth'     => $imageWidth,
            'imageHeight'    => $imageHeight,
        ], $post['template']);
    }
}
