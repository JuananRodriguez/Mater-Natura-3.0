<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

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

        // ─── Component Builder: renderizar componentes si existen ───
        $componentsHtml = '';
        if (!empty($page['content_components'])) {
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

            $decoded = json_decode($page['content_components'], true);
            if (is_array($decoded)) {
                $componentsHtml = $renderer->render($decoded);
            }
        }

        return $template->render('page', [
            'page' => $page,
            'componentsHtml' => $componentsHtml,
        ], $page['template']);
    }
}
