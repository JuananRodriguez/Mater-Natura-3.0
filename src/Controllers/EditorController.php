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
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

/**
 * Controlador del editor visual de componentes (Component Builder).
 */
class EditorController
{
    private ComponentManager $componentManager;
    private ComponentRenderer $renderer;

    public function __construct(
        private Database $db,
        private Security $security,
        private Auth $auth,
    ) {
        $this->componentManager = new ComponentManager();
        $this->registerBuiltInComponents();
        $this->renderer = new ComponentRenderer($this->componentManager);
    }

    public function getComponentManager(): ComponentManager
    {
        return $this->componentManager;
    }

    public function getRenderer(): ComponentRenderer
    {
        return $this->renderer;
    }

    /**
     * Registra los componentes incluidos por defecto.
     */
    private function registerBuiltInComponents(): void
    {
        $this->componentManager->register(new TextComponent());
        $this->componentManager->register(new ImageComponent());
        $this->componentManager->register(new HeroComponent());
        $this->componentManager->register(new GalleryComponent());
        $this->componentManager->register(new ColumnsComponent());
        $this->componentManager->register(new QuoteComponent());
        $this->componentManager->register(new DividerComponent());
        $this->componentManager->register(new SpacerComponent());
        $this->componentManager->register(new HTMLComponent());
    }

    /**
     * GET /admin/componentes/editar?type=page|post&id=X
     */
    public function edit(): string
    {
        $type = $_GET['type'] ?? 'page';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!in_array($type, ['page', 'post'], true)) {
            $type = 'page';
        }

        if (!$id) {
            $_SESSION['admin_error'] = 'ID de contenido no especificado.';
            header('Location: /admin/' . $type . 's');
            exit;
        }

        $table = $type === 'page' ? 'pages' : 'posts';
        $entry = $this->db->fetchOne(
            "SELECT * FROM {$table} WHERE id = ? LIMIT 1",
            [$id]
        );

        if (!$entry) {
            $_SESSION['admin_error'] = 'Contenido no encontrado.';
            header('Location: /admin/' . $type . 's');
            exit;
        }

        $template = new Template($this->security);
        $title = $type === 'page' ? 'Página' : 'Post';
        $template->setMetaTitle("Editor de componentes — {$title} — " . MATER_SITE_NAME);

        $components = [];
        if (!empty($entry['content_components'])) {
            $decoded = json_decode($entry['content_components'], true);
            if (is_array($decoded)) {
                $components = $decoded;
            }
        }

        $user = $this->auth->getCurrentUser();

        $adminError = $_SESSION['admin_error'] ?? null;
        $adminSuccess = $_SESSION['admin_success'] ?? null;
        unset($_SESSION['admin_error'], $_SESSION['admin_success']);

        $meta = $template->getMeta();
        $jsDataScript = $template->getJsDataScript();
        $escape = [$template, 'escapeHtml'];
        $csrfField = $template->csrfField();

        $pageTitle = "Editor: {$entry['title']}";

        // Recolectar componentes registrados para el panel
        $availableComponents = $this->getAvailableComponents();

        // Datos JS para el builder
        $template->exposeToJs('builder', [
            'entryId' => $id,
            'entryType' => $type,
            'components' => $components,
            'availableComponents' => $availableComponents,
        ]);
        $jsDataScript = $template->getJsDataScript();

        ob_start();
        require MATER_TEMPLATES_DIR . '/admin/builder-editor.php';
        return ob_get_clean();
    }

    /**
     * POST /admin/componentes/guardar
     */
    public function save(): void
    {
        $this->auth->requireAuth();
        $type = $_POST['type'] ?? 'page';
        $id = (int) ($_POST['id'] ?? 0);
        $componentsJson = $_POST['components'] ?? '[]';

        if (!in_array($type, ['page', 'post'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de contenido inválido.']);
            exit;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de contenido no especificado.']);
            exit;
        }

        // Validar CSRF
        try {
            $this->security->validateCsrfToken($_POST['_csrf_token'] ?? '');
        } catch (\RuntimeException $e) {
            http_response_code(403);
            echo json_encode(['error' => 'Sesión expirada. Recarga la página.']);
            exit;
        }

        // Decodificar y validar JSON
        $components = json_decode($componentsJson, true);
        if (!is_array($components)) {
            http_response_code(400);
            echo json_encode(['error' => 'Formato de componentes inválido.']);
            exit;
        }

        // Validar cada componente
        foreach ($components as $i => $comp) {
            $type_comp = $comp['type'] ?? '';
            $settings = $comp['settings'] ?? [];
            $errors = $this->componentManager->validate($type_comp, $settings);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Error en componente #' . ($i + 1) . ' (' . $type_comp . '): ' . implode(', ', $errors),
                ]);
                exit;
            }
        }

        // Guardar con prepared statement
        $json = json_encode($components, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $table = $type === 'page' ? 'pages' : 'posts';

        $this->db->update(
            $table,
            ['content_components' => $json, 'builder_version' => 1],
            'id = ?',
            [$id]
        );

        echo json_encode(['success' => true, 'message' => 'Componentes guardados correctamente.']);
        exit;
    }

    /**
     * POST /admin/componentes/preview
     * Renderiza un componente individual para vista previa AJAX.
     */
    public function preview(): void
    {
        $type = $_POST['type'] ?? '';
        $settings = json_decode($_POST['settings'] ?? '{}', true) ?? [];
        $theme = $_POST['theme'] ?? 'light';

        $html = $this->renderer->renderOne($type, $settings, $theme);
        echo json_encode(['html' => $html]);
        exit;
    }

    /**
     * Devuelve la lista de componentes disponibles para el panel lateral.
     */
    private function getAvailableComponents(): array
    {
        $list = [];
        foreach ($this->componentManager->getAll() as $comp) {
            $list[] = [
                'type' => $comp->getType(),
                'label' => $comp->getLabel(),
                'icon' => $comp->getIcon(),
                'defaultSettings' => $comp->getDefaultSettings(),
            ];
        }
        return $list;
    }

    /**
     * GET /admin/componentes/listar-componentes
     * Devuelve JSON con los componentes registrados (para refrescar el panel).
     */
    public function listComponents(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->getAvailableComponents());
        exit;
    }
}
