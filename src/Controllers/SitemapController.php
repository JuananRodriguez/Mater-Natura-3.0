<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Database;

class SitemapController
{
    public function __construct(private Database $db)
    {
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
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        // Home — daily
        $xml .= $this->urlEntry($baseUrl . '/', date('Y-m-d'), '1.0', 'daily');

        // Posts publicados — weekly, con imagen destacada
        $posts = $this->db->fetchAll(
            "SELECT slug, image_url, updated_at FROM posts WHERE status = 'published' AND visibility = 'public' ORDER BY published_at DESC"
        );
        foreach ($posts as $post) {
            $lastmod = $post['updated_at'] ? date('Y-m-d', strtotime($post['updated_at'])) : date('Y-m-d');
            $imageUrl = $post['image_url'] ? $baseUrl . '/media/' . ltrim($post['image_url'], '/') : null;
            $xml .= $this->urlEntry($baseUrl . '/' . $post['slug'], $lastmod, '0.6', 'weekly', $imageUrl);
        }

        // Páginas publicadas — monthly
        $pages = $this->db->fetchAll(
            "SELECT slug, updated_at, is_home FROM pages WHERE status = 'published' ORDER BY updated_at DESC"
        );
        foreach ($pages as $page) {
            $lastmod = $page['updated_at'] ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
            $xml .= $this->urlEntry($baseUrl . '/' . $page['slug'], $lastmod, '0.8', 'monthly');
        }

        // Listado de posts — daily
        $xml .= $this->urlEntry($baseUrl . '/post', date('Y-m-d'), '0.5', 'daily');

        $xml .= '</urlset>';

        return $xml;
    }

    private function urlEntry(string $loc, string $lastmod, string $priority, string $changefreq = 'weekly', ?string $imageUrl = null): string
    {
        $escapedLoc = htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $entry = "  <url>\n"
             . "    <loc>{$escapedLoc}</loc>\n"
             . "    <lastmod>{$lastmod}</lastmod>\n"
             . "    <changefreq>{$changefreq}</changefreq>\n"
             . "    <priority>{$priority}</priority>\n";

        if ($imageUrl) {
            $escapedImg = htmlspecialchars($imageUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $entry .= "    <image:image>\n"
                   . "      <image:loc>{$escapedImg}</image:loc>\n"
                   . "    </image:image>\n";
        }

        $entry .= "  </url>\n";
        return $entry;
    }
}
