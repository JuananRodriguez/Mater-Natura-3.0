<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Auth;

class CatalogController
{
    public function __construct(
        private Database $db,
        private Security $security,
        private Auth $auth,
    ) {}

    /**
     * GET /admin/catalog — Muestra el formulario del catálogo
     */
    public function form(): string
    {
        // Obtener todos los posts publicados
        $posts = $this->db->fetchAll(
            "SELECT id, title, slug, reference, description, image_url, template, created_at
             FROM posts
             WHERE status = 'published'
             ORDER BY published_at DESC"
        );

        // Renderizar usando AdminController
        $adminController = new AdminController($this->db, $this->security, $this->auth);
        return $adminController->renderAdmin('catalog', [
            'currentNav' => 'catalog',
            'posts' => $posts,
        ]);
    }

    /**
     * POST /admin/catalog/generate — Genera el PDF del catálogo
     */
    public function generate(): void
    {
        // Validar CSRF
        if (!$this->security->validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $_SESSION['admin_error'] = 'Token de seguridad inválido.';
            header('Location: /admin/catalog');
            exit;
        }

        // Opciones del formulario
        $showRef     = isset($_POST['show_ref']);
        $showImage   = isset($_POST['show_image']);
        $showTitle   = isset($_POST['show_title']);
        $showDesc    = isset($_POST['show_description']);
        $mode        = $_POST['mode'] ?? 'todo';
        $selectedIds = $_POST['post_ids'] ?? [];

        // Mapear modo a tema interno del PDF
        $theme = match ($mode) {
            'oscuro' => 'dark',
            default  => 'light',
        };

        // Validar selección
        if (empty($selectedIds)) {
            $_SESSION['admin_error'] = 'Debes seleccionar al menos un post.';
            header('Location: /admin/catalog');
            exit;
        }

        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username as author_name
             FROM posts p
             JOIN users u ON p.user_id = u.id
             WHERE p.id IN ({$placeholders})
             ORDER BY p.published_at DESC",
            $selectedIds
        );

        if (empty($posts)) {
            $_SESSION['admin_error'] = 'No se encontraron posts.';
            header('Location: /admin/catalog');
            exit;
        }

        // Generar HTML del catálogo
        $html = $this->buildCatalogHtml($posts, $theme, [
            'ref'   => $showRef,
            'image' => $showImage,
            'title' => $showTitle,
            'desc'  => $showDesc,
        ]);

        // Configurar Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream('catalogo-mater-natura.pdf', [
            'Attachment' => true,
        ]);
        exit;
    }

    /**
     * GET /admin/catalog/filter — Devuelve HTML de posts filtrados por modo (AJAX)
     */
    public function filterByMode(): void
    {
        $this->auth->requireAuth();

        $mode = $_GET['mode'] ?? 'todo';

        $sql = "SELECT id, title, slug, reference, description, image_url, template, created_at
                FROM posts
                WHERE status = 'published'";

        if ($mode === 'claro') {
            $sql .= " AND template = 'light'";
        } elseif ($mode === 'oscuro') {
            $sql .= " AND template = 'dark'";
        }

        $sql .= " ORDER BY published_at DESC";

        $posts = $this->db->fetchAll($sql);

        header('Content-Type: text/html; charset=utf-8');

        if (empty($posts)) {
            echo '<p class="empty-filter">No hay posts en este modo.</p>';
            return;
        }

        $adminController = new AdminController($this->db, $this->security, $this->auth);
        $escape = [$this->security, 'escapeHtml'];

        foreach ($posts as $post) {
            $postId = $post['id'];
            $postTitle = $post['title'];
            $postSlug = $post['slug'];
            $postRef = $post['reference'] ?? '';
            $postImage = $post['image_url'] ?? '';
            $postTemplate = $post['template'];
            ?>
            <label class="post-item"
                   x-transition.duration.200ms>
                <input type="checkbox" name="post_ids[]" value="<?= $postId ?>" checked>
                <div class="post-preview">
                    <?php if (!empty($postImage)): ?>
                    <?php
                    $imagePath = MATER_UPLOADS_DIR . '/' . $postImage;
                    if (file_exists($imagePath)): ?>
                    <img src="/media/<?= $escape($postImage) ?>" alt="<?= $escape($postTitle) ?>" loading="lazy">
                    <?php else: ?>
                    <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                    <?php endif; ?>
                    <?php endif; ?>
                    <div class="post-info">
                        <h3><?= $escape($postTitle) ?></h3>
                        <small><?= $postRef ? 'Ref: ' . $escape($postRef) : 'Ref: ' . $escape($postSlug) ?></small>
                    </div>
                </div>
            </label>
            <?php
        }
    }

    /**
     * Construye el HTML completo del catálogo
     */
    private function buildCatalogHtml(array $posts, string $theme, array $options): string
    {
        $themeStyles = $this->getThemeStyles($theme);
        $showRef     = $options['ref'];
        $showImage   = $options['image'];
        $showTitle   = $options['title'];
        $showDesc    = $options['desc'];

        $itemsHtml = '';
        $index = 0;

        foreach ($posts as $post) {
            $index++;
            $ref   = !empty($post['reference']) ? $this->security->escapeHtml($post['reference']) : str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $title = $this->security->escapeHtml($post['title']);
            $desc  = $this->sanitizeHtml($post['description'] ?? '');

            $itemsHtml .= '<div class="catalog-item">';

            if ($showImage && !empty($post['image_url'])) {
                $imagePath = MATER_UPLOADS_DIR . '/' . $post['image_url'];
                if (file_exists($imagePath)) {
                    $data = base64_encode(file_get_contents($imagePath));
                    $mime = mime_content_type($imagePath);
                    $itemsHtml .= '<div class="catalog-image">';
                    $itemsHtml .= '<img src="data:' . $mime . ';base64,' . $data . '" alt="' . $title . '" />';
                    $itemsHtml .= '</div>';
                }
            }

            $itemsHtml .= '<div class="catalog-content">';

            if ($showRef) {
                $itemsHtml .= '<span class="catalog-ref">' . $ref . '</span>';
            }

            if ($showTitle) {
                $itemsHtml .= '<h2 class="catalog-title">' . $title . '</h2>';
            }

            if ($showDesc) {
                $itemsHtml .= '<div class="catalog-desc">' . $desc . '</div>';
            }

            $itemsHtml .= '</div>';
            $itemsHtml .= '</div>';

            if ($index < count($posts)) {
                $itemsHtml .= '<div class="catalog-separator"></div>';
            }
        }

        $siteName = $this->security->escapeHtml(MATER_SITE_NAME);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo {$siteName}</title>
    <style>
        {$themeStyles}
    </style>
</head>
<body>
    <div class="catalog-cover">
        <h1>Catálogo</h1>
        <p>{$siteName}</p>
        <p class="catalog-date">Generado el {$this->security->escapeHtml(date('d/m/Y'))}</p>
    </div>
    <div class="catalog-items">
        {$itemsHtml}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Sanitiza HTML para el PDF: permite solo etiquetas seguras
     */
    private function sanitizeHtml(string $html): string
    {
        // Permitir solo etiquetas básicas de formato
        $allowed = '<p><br><br/><b><strong><i><em><u><s><span><div><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><hr><a><img><figure><figcaption>';
        $clean = strip_tags($html, $allowed);

        // Decodificar entidades HTML para que Dompdf las renderice correctamente
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Eliminar atributos peligrosos (onclick, onload, etc.)
        $clean = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace("/\bon\w+\s*=\s*'[^']*'/i", '', $clean);

        return $clean;
    }

    /**
     * Temas disponibles
     */
    public function getThemes(): array
    {
        return [
            'light' => [
                'name' => 'Claro',
                'desc' => 'Blanco y negro elegante con tipografía serif',
            ],
            'dark' => [
                'name' => 'Oscuro',
                'desc' => 'Fondo oscuro con texto claro',
            ],
            'naturaleza' => [
                'name' => 'Naturaleza',
                'desc' => 'Tonos tierra y verdes suaves',
            ],
        ];
    }

    /**
     * CSS para cada tema
     */
    public function getThemeStyles(string $theme): string
    {
        $styles = [
            'light' => <<<CSS
                @page { margin: 2cm; }
                body {
                    font-family: 'Georgia', 'Times New Roman', serif;
                    color: #1a1a1a;
                    background: #ffffff;
                    margin: 0; padding: 0;
                    line-height: 1.6;
                }
                .catalog-cover {
                    text-align: center;
                    padding: 6cm 2cm 4cm;
                    page-break-after: always;
                }
                .catalog-cover h1 {
                    font-size: 42pt;
                    font-weight: normal;
                    letter-spacing: 0.15em;
                    text-transform: uppercase;
                    margin-bottom: 1cm;
                }
                .catalog-cover p { font-size: 14pt; color: #666; }
                .catalog-date { font-size: 10pt; color: #999; margin-top: 2cm !important; }
                .catalog-item {
                    padding: 1.5cm 2cm;
                    page-break-inside: avoid;
                }
                .catalog-image { text-align: center; margin-bottom: 1cm; }
                .catalog-image img { max-width: 12cm; max-height: 16cm; height: auto; }
                .catalog-content { text-align: center; }
                .catalog-ref {
                    display: block; font-size: 10pt; color: #999;
                    letter-spacing: 0.1em; margin-bottom: 0.3cm;
                }
                .catalog-title {
                    font-size: 20pt; font-weight: normal;
                    margin: 0.5cm 0; letter-spacing: 0.05em;
                }
                .catalog-desc {
                    font-size: 11pt; color: #555;
                    max-width: 14cm; margin: 0 auto; line-height: 1.8;
                }
                .catalog-separator {
                    border: none; border-top: 1px solid #e0e0e0;
                    margin: 0 2cm;
                }
            CSS,

            'dark' => <<<CSS
                @page { margin: 0; }
                html { background: #000000; }
                body {
                    font-family: 'Helvetica', 'Arial', sans-serif;
                    color: #e0e0e0;
                    background: #000000;
                    margin: 0;
                    padding: 2cm;
                    line-height: 1.6;
                    min-height: 100%;
                }
                .catalog-cover {
                    text-align: center;
                    padding: 6cm 0 4cm;
                    page-break-after: always;
                }
                .catalog-cover h1 {
                    font-size: 42pt; font-weight: 300;
                    letter-spacing: 0.2em; text-transform: uppercase;
                    margin-bottom: 1cm; color: #ffffff;
                }
                .catalog-cover p { font-size: 14pt; color: #888; }
                .catalog-date { font-size: 10pt; color: #555; margin-top: 2cm !important; }
                .catalog-item {
                    padding: 1.5cm 0;
                    page-break-inside: avoid;
                }
                .catalog-image { text-align: center; margin-bottom: 1cm; }
                .catalog-image img { max-width: 12cm; max-height: 16cm; height: auto; }
                .catalog-content { text-align: center; }
                .catalog-ref {
                    display: block; font-size: 10pt; color: #555;
                    letter-spacing: 0.1em; margin-bottom: 0.3cm;
                }
                .catalog-title {
                    font-size: 20pt; font-weight: 300;
                    margin: 0.5cm 0; letter-spacing: 0.05em; color: #ffffff;
                }
                .catalog-desc {
                    font-size: 11pt; color: #999;
                    max-width: 14cm; margin: 0 auto; line-height: 1.8;
                }
                .catalog-separator {
                    border: none; border-top: 1px solid #1a1a1a;
                    margin: 0;
                }
            CSS,

            'naturaleza' => <<<CSS
                @page { margin: 2cm; }
                body {
                    font-family: 'Georgia', 'Times New Roman', serif;
                    color: #3d3229;
                    background: #faf6f0;
                    margin: 0; padding: 0;
                    line-height: 1.6;
                }
                .catalog-cover {
                    text-align: center;
                    padding: 6cm 2cm 4cm;
                    page-break-after: always;
                }
                .catalog-cover h1 {
                    font-size: 42pt; font-weight: normal;
                    letter-spacing: 0.15em; text-transform: uppercase;
                    margin-bottom: 1cm; color: #5b7a5b;
                }
                .catalog-cover p { font-size: 14pt; color: #8a7a6a; }
                .catalog-date { font-size: 10pt; color: #b0a090; margin-top: 2cm !important; }
                .catalog-item {
                    padding: 1.5cm 2cm;
                    page-break-inside: avoid;
                }
                .catalog-image { text-align: center; margin-bottom: 1cm; }
                .catalog-image img { max-width: 12cm; max-height: 16cm; height: auto; border-radius: 4px; }
                .catalog-content { text-align: center; }
                .catalog-ref {
                    display: block; font-size: 10pt; color: #b0a090;
                    letter-spacing: 0.1em; margin-bottom: 0.3cm;
                }
                .catalog-title {
                    font-size: 20pt; font-weight: normal;
                    margin: 0.5cm 0; letter-spacing: 0.05em; color: #5b7a5b;
                }
                .catalog-desc {
                    font-size: 11pt; color: #6a5a4a;
                    max-width: 14cm; margin: 0 auto; line-height: 1.8;
                }
                .catalog-separator {
                    border: none; border-top: 1px solid #e0d8cc;
                    margin: 0 2cm;
                }
            CSS,
        ];

        return $styles[$theme] ?? $styles['light'];
    }
}
