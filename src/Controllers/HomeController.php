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

class HomeController
{
    public function __construct(private Database $db, private Security $security, private ?PluginManager $pluginManager = null)
    {
    }

    public function index(): string
    {
        $template = new Template($this->security);
        if ($this->pluginManager) {
            $template->setPluginManager($this->pluginManager);
        }

        // Buscar la página de inicio configurada
        $homePage = $this->db->fetchOne(
            "SELECT * FROM pages WHERE is_home = 1 AND status = 'published' LIMIT 1"
        );

        $template->setMetaTitle(
            !empty($homePage['meta_title'])
                ? $homePage['meta_title']
                : MATER_SITE_NAME
        );
        $template->setMetaDescription(
            !empty($homePage['meta_description'])
                ? $homePage['meta_description']
                : MATER_SITE_DESCRIPTION
        );
        $template->setOgType('website');

        // Canonical
        $template->setCanonicalUrl(rtrim(MATER_BASE_URL, '/') . '/');

        // JSON-LD WebSite + Organization
        $baseUrl = rtrim(MATER_BASE_URL, '/');
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => MATER_SITE_NAME,
            'description' => MATER_SITE_DESCRIPTION,
            'url' => $baseUrl . '/',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/post?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ]);
        $template->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => MATER_SITE_NAME,
            'url' => $baseUrl . '/',
        ]);

        $layout = $homePage['template'] ?? 'dark';

        // ─── Component Builder: renderizar componentes si existen ───
        $componentsHtml = '';
        if (!empty($homePage['content_components'])) {
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

            $decoded = json_decode($homePage['content_components'], true);
            if (is_array($decoded)) {
                $componentsHtml = $renderer->render($decoded);
            }
        }

        $template->exposeToJs('isHome', true);

        return $template->render('home', [
            'page' => $homePage,
            'componentsHtml' => $componentsHtml,
            'siteName' => MATER_SITE_NAME,
            'siteDescription' => MATER_SITE_DESCRIPTION,
        ], $layout);
    }
}
