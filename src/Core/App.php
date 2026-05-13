<?php

declare(strict_types=1);

namespace MaterNatura\Core;

class App
{
    private Database $db;
    private Security $security;
    private Auth $auth;
    private Router $router;
    private PluginManager $pluginManager;

    public function __construct(array $config, array $dbConfig)
    {
        // Inicializar sesión segura
        $this->initSession();

        // Capa de datos
        $this->db = new Database($dbConfig);

        // Capa de seguridad (depende de DB para rate limiting)
        $this->security = new Security($this->db);

        // Enviar cabeceras de seguridad en cada petición
        $this->security->sendSecurityHeaders();

        // Capa de autenticación
        $this->auth = new Auth($this->db, $this->security);

        // Sistema de plugins
        $this->pluginManager = new PluginManager($this->db, $this->security, $this->auth);
        $this->pluginManager->loadActivePlugins();

        // Enrutador
        $this->router = new Router($this->auth, $this->security, $this->db, $this->pluginManager);
    }

    public function run(): void
    {
        // Ejecutar migraciones si se solicita
        if (isset($_GET['_migrate']) || (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === 'migrate')) {
            $this->db->runMigrations();
            echo "Migraciones ejecutadas correctamente.\n";
            if (PHP_SAPI === 'cli') {
                exit(0);
            }
        }

        // Dispatch de la ruta
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        try {
            $this->router->dispatch($method, $uri);
        } catch (\Throwable $e) {
            if (MATER_DEBUG) {
                throw $e;
            }
            // 500 en producción
            http_response_code(500);
            echo $this->renderError('Error interno del servidor');
        }
    }

    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Configuración de sesión segura
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');

        if (MATER_ENV === 'production') {
            ini_set('session.cookie_secure', '1');
        }

        // Nombre de sesión personalizado (no PHPSESSID)
        session_name('MN_SESSION');

        session_start();

        // Comprobar expiración por inactividad
        $this->checkSessionExpiry();
    }

    private function checkSessionExpiry(): void
    {
        $lifetime = MATER_SESSION_LIFETIME;

        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > $lifetime)) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }

        $_SESSION['_last_activity'] = time();
    }

    private function renderError(string $message): string
    {
        return '<!DOCTYPE html><html><head><title>Error</title>'
             . '<style>body{font-family:Georgia,serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;color:#333}'
             . '.error{text-align:center}h1{font-size:4rem;margin:0;color:#2d2d2d}p{font-size:1.2rem}</style>'
             . '</head><body><div class="error"><h1>Error</h1><p>' . $this->security->escapeHtml($message) . '</p></div></body></html>';
    }
}
