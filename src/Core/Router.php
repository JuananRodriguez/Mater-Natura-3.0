<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class Router
{
    private array $getRoutes = [];
    private array $postRoutes = [];
    private mixed $slugHandler = null;

    public function __construct(
        private Auth $auth,
        private Security $security,
        private Database $db,
        private ?PluginManager $pluginManager = null,
    ) {
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
            $controller = new \MaterNatura\Controllers\HomeController($this->db, $this->security, $this->pluginManager);
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
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->dashboard();
        });

        // Admin posts
        $this->get('/admin/posts', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->postList();
        });

        $this->get('/admin/posts/editar', function () {
            $this->auth->requireAuth();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->postForm($id);
        });

        $this->post('/admin/posts/editar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->postSave($_POST);
        });

        $this->post('/admin/posts/eliminar', function () {
            $this->auth->requireAuth();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->postDelete($id);
        });

        // Admin pages
        $this->get('/admin/pages', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->pageList();
        });

        $this->get('/admin/pages/editar', function () {
            $this->auth->requireAuth();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->pageForm($id);
        });

        $this->post('/admin/pages/editar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->pageSave($_POST);
        });

        $this->post('/admin/pages/eliminar', function () {
            $this->auth->requireAuth();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->pageDelete($id);
        });

        // AJAX upload de imagenes para el editor
        $this->post('/admin/upload-image', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->uploadImage();
        });

        // Admin plugins (solo administradores)
        $this->get('/admin/plugins', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->plugins();
        });

        // Activar plugin
        $this->post('/admin/plugins/activar', function () {
            $this->auth->requireAdmin();
            $slug = trim($_POST['slug'] ?? '');
            if ($slug === '') {
                $_SESSION['admin_error'] = 'Slug de plugin no especificado.';
                header('Location: /admin/plugins');
                exit;
            }
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->pluginActivate($slug);
        });

        // Desactivar plugin
        $this->post('/admin/plugins/desactivar', function () {
            $this->auth->requireAdmin();
            $slug = trim($_POST['slug'] ?? '');
            if ($slug === '') {
                $_SESSION['admin_error'] = 'Slug de plugin no especificado.';
                header('Location: /admin/plugins');
                exit;
            }
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->pluginDeactivate($slug);
        });

        // Admin settings (solo administradores)
        $this->get('/admin/ajustes', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->settings();
        });

        $this->post('/admin/ajustes', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->saveSettings($_POST);
        });

        // Admin usuarios (solo administradores)
        $this->get('/admin/usuarios', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->userList();
        });

        $this->get('/admin/usuarios/editar', function () {
            $this->auth->requireAdmin();
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            echo $controller->userForm($id);
        });

        $this->post('/admin/usuarios/editar', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->userSave($_POST);
        });

        $this->post('/admin/usuarios/eliminar', function () {
            $this->auth->requireAdmin();
            $id = (int) ($_POST['id'] ?? 0);
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
            $controller->userDelete($id);
        });

        $this->post('/admin/usuarios/restablecer-password', function () {
            $this->auth->requireAdmin();
            $controller = new \MaterNatura\Controllers\AdminController($this->db, $this->security, $this->auth, $this->pluginManager);
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
            // Verificar que el plugin está activo
            if ($this->pluginManager && !$this->pluginManager->isActive('comments')) {
                echo '<p>El plugin de comentarios está desactivado.</p>';
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
            if ($this->pluginManager && !$this->pluginManager->isActive('comments')) return;
            if (method_exists($plugin, 'adminCommentsApprove')) {
                $plugin->adminCommentsApprove((int) ($_POST['id'] ?? 0));
            }
        });

        $this->post('/admin/comments/eliminar', function () {
            $this->auth->requireAuth();
            $plugin = $this->pluginManager ? $this->pluginManager->getPlugin('comments') : null;
            if (!$plugin) return;
            if ($this->pluginManager && !$this->pluginManager->isActive('comments')) return;
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

        // Robots.txt
        $this->get('/robots.txt', function () {
            header('Content-Type: text/plain; charset=utf-8');
            $baseUrl = rtrim(MATER_BASE_URL, '/');
            echo "User-agent: *\n";
            echo "Allow: /\n";
            echo "Disallow: /admin/\n";
            echo "Disallow: /login\n";
            echo "Disallow: /logout\n";
            echo "Disallow: /media/\n";
            echo "Sitemap: {$baseUrl}/sitemap.xml\n";
            exit;
        });

        // Analytics tracking endpoint (client-side tracking via sendBeacon)
        $this->post('/analytics/track', function () {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($this->pluginManager) {
                $this->pluginManager->executeHook('analytics.track', $input);
            }
            http_response_code(204);
            exit;
        });

        // Admin analytics (plugin dashboard)
        $this->get('/admin/analytics', function () {
            $this->auth->requireAuth();
            $plugin = $this->pluginManager ? $this->pluginManager->getPlugin('analytics') : null;
            if (!$plugin || !$this->pluginManager->isActive('analytics')) {
                http_response_code(404);
                echo '<p>Plugin de analytics no disponible.</p>';
                return;
            }
            $analyticsHtml = method_exists($plugin, 'adminAnalyticsDashboard')
                ? $plugin->adminAnalyticsDashboard()
                : '<p>Dashboard no disponible.</p>';
            $adminController = new \MaterNatura\Controllers\AdminController(
                $this->db, $this->security, $this->auth, $this->pluginManager
            );
            echo $adminController->renderAdmin('plugin-analytics', [
                'currentNav' => 'analytics',
                'analyticsHtml' => $analyticsHtml,
            ]);
        });

        // ─── Component Builder (Editor visual) ───

        $this->get('/admin/componentes/editar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\EditorController(
                $this->db, $this->security, $this->auth
            );
            echo $controller->edit();
        });

        $this->post('/admin/componentes/guardar', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\EditorController(
                $this->db, $this->security, $this->auth
            );
            $controller->save();
        });

        $this->post('/admin/componentes/preview', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\EditorController(
                $this->db, $this->security, $this->auth
            );
            $controller->preview();
        });

        $this->get('/admin/componentes/listar-componentes', function () {
            $this->auth->requireAuth();
            $controller = new \MaterNatura\Controllers\EditorController(
                $this->db, $this->security, $this->auth
            );
            $controller->listComponents();
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
                $controller = new \MaterNatura\Controllers\PageController($this->db, $this->security, $this->pluginManager);
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
