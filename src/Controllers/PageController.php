<?php

declare(strict_types=1);

namespace MaterNatura\Controllers;

use MaterNatura\Core\Database;
use MaterNatura\Core\Security;
use MaterNatura\Core\Template;

class PageController
{
    private Database $db;
    private Security $security;

    public function __construct(Database $db, Security $security)
    {
        $this->db = $db;
        $this->security = $security;
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
            $template->setMetaTitle('Página no encontrada');
            return $template->render('404', ['message' => 'Esta página no existe.']);
        }

        $template = new Template($this->security);
        $template->setMetaTitle($page['title'] . ' — ' . MATER_SITE_NAME);
        $template->setMetaDescription(MATER_SITE_DESCRIPTION);
        $template->setOgType('website');

        return $template->render('page', [
            'page' => $page,
        ], $page['template']);
    }
}
