<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Database;

class SitemapController
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * GET /sitemap.xml — Generar y servir el sitemap
     */
    public function xml(): void
    {
        // Procesar cola de regeneración pendiente
        $this->processQueue();

        $sitemapPath = MATER_PUBLIC_DIR . '/sitemap.xml';

        if (!file_exists($sitemapPath)) {
            // Generar si no existe
            $xml = $this->generate();
            file_put_contents($sitemapPath, $xml);
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        readfile($sitemapPath);
        exit;
    }

    /**
     * Regenerar el sitemap (llamado desde admin)
     */
    public function regenerate(): void
    {
        $xml = $this->generate();
        file_put_contents(MATER_PUBLIC_DIR . '/sitemap.xml', $xml);
    }

    /**
     * Cola de regeneración
     */
    public function queueRegeneration(string $type, int $entryId, string $action): void
    {
        $this->db->insert('sitemap_queue', [
            'type' => $type,
            'entry_id' => $entryId,
            'action' => $action,
        ]);
    }

    /**
     * Procesar cola pendiente
     */
    private function processQueue(): void
    {
        $pending = $this->db->fetchAll(
            "SELECT * FROM sitemap_queue WHERE processed = FALSE ORDER BY created_at ASC LIMIT 100"
        );

        if (empty($pending)) {
            return;
        }

        // Regenerar sitemap
        $this->regenerate();

        // Marcar como procesado
        $ids = array_column($pending, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $this->db->query(
            "UPDATE sitemap_queue SET processed = TRUE WHERE id IN ({$placeholders})",
            $ids
        );
    }

    /**
     * Generar el XML del sitemap
     */
    private function generate(): string
    {
        $baseUrl = rtrim(MATER_BASE_URL, '/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Home
        $xml .= $this->urlEntry($baseUrl . '/', date('Y-m-d'), '1.0');

        // Posts publicados
        $posts = $this->db->fetchAll(
            "SELECT slug, updated_at FROM posts WHERE status = 'published' AND visibility = 'public' ORDER BY published_at DESC"
        );
        foreach ($posts as $post) {
            $lastmod = $post['updated_at'] ? date('Y-m-d', strtotime($post['updated_at'])) : date('Y-m-d');
            $xml .= $this->urlEntry($baseUrl . '/' . $post['slug'], $lastmod, '0.6');
        }

        // Páginas publicadas
        $pages = $this->db->fetchAll(
            "SELECT slug, updated_at, is_home FROM pages WHERE status = 'published' ORDER BY updated_at DESC"
        );
        foreach ($pages as $page) {
            $lastmod = $page['updated_at'] ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
            $xml .= $this->urlEntry($baseUrl . '/' . $page['slug'], $lastmod, '0.8');
        }

        // Listado de posts
        $xml .= $this->urlEntry($baseUrl . '/post', date('Y-m-d'), '0.5');

        $xml .= '</urlset>';

        return $xml;
    }

    private function urlEntry(string $loc, string $lastmod, string $priority): string
    {
        $escapedLoc = htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "  <url>\n"
             . "    <loc>{$escapedLoc}</loc>\n"
             . "    <lastmod>{$lastmod}</lastmod>\n"
             . "    <priority>{$priority}</priority>\n"
             . "  </url>\n";
    }
}
