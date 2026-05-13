<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Router
{
    private array $getRoutes = [];
    private array $postRoutes = [];
    private $slugHandler = null;

    private Auth $auth;
    private Security $security;
    private Database $db;
    private ?PluginManager $pluginManager = null;

    public function __construct(Auth $auth, Security $security, Database $db, ?PluginManager $pluginManager = null)
    {
        $this->auth = $auth;
        $this->security = $security;
        $this->db = $db;
        $this->pluginManager = $pluginManager;

        $this->registerRoutes();
    }

    public function dispatch(string $method, string $uri): void
    {
        // Normalizar URI
        $uri = '/' . trim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        // Extraer query string
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Determinar el manejador
        $handler = null;

        if ($method === 'GET') {
            $handler = $this->getRoutes[$path] ?? null;
        } elseif ($method === 'POST') {
            $handler = $this->postRoutes[$path] ?? null;
        }

        if ($handler !== null) {
            call_user_func($handler);
            return;
        }

        // Ruta /media/{path}
        if ($method === 'GET' && preg_match('#^/media/(.+)$#', $path, $m)) {
            $controller = new \MaterNatura\Controllers\MediaController($this->security);
            $controller->serve($m[1]);
            return;
        }

        // Si no es ruta fija, delegar en slug handler
        if ($this->slugHandler && preg_match('#^/([^/]+)$#', $path, $matches)) {
            $slug = $matches[1];
            call_user_func($this->slugHandler, $slug);
            return;
        }

        // 404
        http_response_code(404);
        $this->render404();
    }

    public function get(string $path, callable $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    public function addSlugRoute(callable $slugHandler): void
    {
        $this->slugHandler = $slugHandler;
    }

    // ─── Registro de rutas del sistema ───

    private function registerRoutes(): void
    {
        // Home
        $this->get('/', function () {
            $controller = new \MaterNatura\Controllers\HomeController($this->db, $this->security);
            echo $controller->index();
        });

        // Post listing
        $this->get('/post', function () {
            $controller = new \MaterNatura\Controllers\PostController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->index((int) ($_GET['page'] ?? 1));
        });

        // Login
        $this->get('/login', function () {
            $controller = new \MaterNatura\Controllers\AuthController($this->auth, $this->security);
            echo $controller->loginForm();
        });

        $this->post('/login', function () {
            $controller = new \MaterNatura\Controllers\AuthController($this->auth, $this->security);
            $result = $controller->login();
            if (is_string($result)) {
                echo $result;
            }
        });

        // Logout
        $this->get('/logout', function () {
            $controller = new \MaterNatura\Controllers\AuthController($this->auth, $this->security);
            $controller->logout();
        });

        // Admin panel
        $this->get('/admin', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->dashboard();
        });

        // Admin posts
        $this->get('/admin/posts', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->postList();
        });

        $this->get('/admin/posts/editar', function () {
            $this->auth->requireAuth();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->postForm($id);
        });

        $this->post('/admin/posts/editar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->postSave($_POST);
        });

        $this->post('/admin/posts/eliminar', function () {
            $this->auth->requireAuth();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->postDelete($id);
        });

        // Admin pages
        $this->get('/admin/pages', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->pageList();
        });

        $this->get('/admin/pages/editar', function () {
            $this->auth->requireAuth();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->pageForm($id);
        });

        $this->post('/admin/pages/editar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->pageSave($_POST);
        });

        $this->post('/admin/pages/eliminar', function () {
            $this->auth->requireAuth();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->pageDelete($id);
        });

        // AJAX upload de imagenes para el editor
        $this->post('/admin/upload-image', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->uploadImage();
        });

        // Admin plugins (solo administradores)
        $this->get('/admin/plugins', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->plugins();
        });

        // Admin settings (solo administradores)
        $this->get('/admin/ajustes', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->settings();
        });

        $this->post('/admin/ajustes', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->saveSettings($_POST);
        });

        // Admin usuarios (solo administradores)
        $this->get('/admin/usuarios', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->userList();
        });

        $this->get('/admin/usuarios/editar', function () {
            $this->auth->requireAdmin();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            echo $controller->userForm($id);
        });

        $this->post('/admin/usuarios/editar', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->userSave($_POST);
        });

        $this->post('/admin/usuarios/eliminar', function () {
            $this->auth->requireAdmin();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->userDelete($id);
        });

        $this->post('/admin/usuarios/restablecer-password', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth);
            $controller->userResetPassword($_POST);
        });

        // Admin comments (plugin)
        $this->get('/admin/comments', function () {
            $this->auth->requireAuth();
            $plugin = $this->pluginManager ? $this->pluginManager->getPlugin('comments') : null;
            if (!$plugin) {
                header('HTTP/1.0 404 Not Found');
                echo 'Plugin no encontrado';
                return;
            }
            if (method_exists($plugin, 'adminCommentsList')) {
                echo $plugin->adminCommentsList();
            }
        });

        $this->post('/admin/comments/aprobar', function () {
            $this->auth->requireAuth();
            $plugin = $this->pluginManager ? $this->pluginManager->getPlugin('comments') : null;
            if (!$plugin) return;
            if (method_exists($plugin, 'adminCommentsApprove')) {
                $plugin->adminCommentsApprove((int) ($_POST['id'] ?? 0));
            }
        });

        $this->post('/admin/comments/eliminar', function () {
            $this->auth->requireAuth();
            $plugin = $this->pluginManager ? $this->pluginManager->getPlugin('comments') : null;
            if (!$plugin) return;
            if (method_exists($plugin, 'adminCommentsDelete')) {
                $plugin->adminCommentsDelete((int) ($_POST['id'] ?? 0));
            }
        });

        // Media serving
        $this->get('/media/{path}', function () {
            // Handled via slug resolution fallback
            // We'll detect it in the slug handler
        });

        // Sitemap
        $this->get('/sitemap.xml', function () {
            $controller = new \MaterNatura\Controllers\SitemapController($this->db);
            $controller->xml();
        });

        // Comment submission (plugin hook)
        $this->post('/comment', function () {
            try {
                $this->security->validateCsrfToken($_POST['_csrf_token'] ?? '');
            } catch (\RuntimeException $e) {
                $_SESSION['comment_flash'] = [
                    'type' => 'error',
                    'text' => 'Sesión expirada. Recarga la página e inténtalo de nuevo.',
                ];
                $return = $_POST['return_url'] ?? '/';
                header('Location: ' . $return);
                exit;
            }

            if ($this->pluginManager) {
                $context = $_POST;
                $context['security'] = $this->security;
                $context['db'] = $this->db;
                $this->pluginManager->executeHook('comment.submit', $context);
            }

            $return = $_POST['return_url'] ?? '/';
            header('Location: ' . $return);
            exit;
        });

        // Slug handler — resuelve /{slug} como post o page
        $this->addSlugRoute(function (string $slug) {
            // Primero buscar en posts
            $post = $this->db->fetchOne(
                "SELECT * FROM posts WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );

            if ($post) {
                $controller = new \MaterNatura\Controllers\PostController($this->db, $this->security, $this->auth, $this->pluginManager);
                echo $controller->show($slug);
                return;
            }

            // Luego en pages
            $page = $this->db->fetchOne(
                "SELECT * FROM pages WHERE slug = ? AND status = 'published' LIMIT 1",
                [$slug]
            );

            if ($page) {
                $controller = new \MaterNatura\Controllers\PageController($this->db, $this->security);
                echo $controller->show($slug);
                return;
            }

            // 404
            http_response_code(404);
            $this->render404();
        });
    }

    private function render404(): void
    {
        $template = new Template($this->security);
        $template->setMetaTitle('Página no encontrada');
        $template->setMetaDescription('El contenido que buscas no existe.');
        echo $template->render('404', ['message' => 'Este poema no ha sido escrito aún.']);
    }
}
