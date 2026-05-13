<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class HomeController
{
    private Database $db;
    private Security $security;

    public function __construct(Database $db, Security $security)
    {
        $this->db = $db;
        $this->security = $security;
    }

    public function index(): string
    {
        $template = new Template($this->security);

        // Buscar la página de inicio configurada
        $homePage = $this->db->fetchOne(
            "SELECT * FROM pages WHERE is_home = 1 AND status = 'published' LIMIT 1"
        );

        $template->setMetaTitle(MATER_SITE_NAME);
        $template->setMetaDescription(MATER_SITE_DESCRIPTION);
        $template->setOgType('website');

        $layout = $homePage['template'] ?? 'dark';

        $template->exposeToJs('isHome', true);

        return $template->render('home', [
            'page' => $homePage,
            'siteName' => MATER_SITE_NAME,
            'siteDescription' => MATER_SITE_DESCRIPTION,
        ], $layout);
    }
}
