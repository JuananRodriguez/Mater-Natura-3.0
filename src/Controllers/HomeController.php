<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

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

        $template->exposeToJs('isHome', true);

        return $template->render('home', [
            'page' => $homePage,
            'siteName' => MATER_SITE_NAME,
            'siteDescription' => MATER_SITE_DESCRIPTION,
        ], $layout);
    }
}
