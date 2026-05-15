<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Database;
use MaterNatura\Core\PluginManager;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class PageController
{
    public function __construct(private Database $db, private Security $security, private ?PluginManager $pluginManager = null)
    {
    }

    /**
     * GET /{slug} — Página individual
     */
    public function show(string $slug): string
    {
        $page = $this->db->fetchOne(
            "SELECT p.*, u.username as author_name
             FROM pages p
             JOIN users u ON p.user_id = u.id
             WHERE p.slug = ? AND p.status = 'published'
             LIMIT 1",
            [$slug]
        );

        if (!$page) {
            http_response_code(404);
            $template = new Template($this->security);
            if ($this->pluginManager) {
                $template->setPluginManager($this->pluginManager);
            }
            $template->setMetaTitle('Página no encontrada');
            return $template->render('404', ['message' => 'Esta página no existe.']);
        }

        $template = new Template($this->security);
        if ($this->pluginManager) {
            $template->setPluginManager($this->pluginManager);
        }
        $metaTitle = !empty($page['meta_title']) ? $page['meta_title'] : ($page['title'] . ' — ' . MATER_SITE_NAME);
        $template->setMetaTitle($metaTitle);
        $metaDescription = !empty($page['meta_description'])
            ? $page['meta_description']
            : MATER_SITE_DESCRIPTION;
        $template->setMetaDescription($metaDescription);
        $template->setOgType('website');

        // Canonical
        $canonical = rtrim(MATER_BASE_URL, '/') . '/' . $slug;
        $template->setCanonicalUrl($canonical);

        // JSON-LD WebPage
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $metaTitle,
            'description' => $metaDescription,
            'url' => $canonical,
        ]);

        return $template->render('page', [
            'page' => $page,
        ], $page['template']);
    }
}
