<?php declare(strict_types=1);

/**
 * Header del sitio — renderizado desde theme settings
 *
 * Variables disponibles: $escape, $isAuthenticated, $themeLogo, $themeNav, $themeSocial
 */
$logo = $themeLogo ?? ['type' => 'text', 'text' => 'MATER NATURA'];
$nav  = $themeNav ?? [];

// Filtrar enlaces de navegación para header
$headerNav = array_filter($nav, fn($item) => ($item['scope'] ?? 'both') === 'both' || ($item['scope'] ?? '') === 'header');
?>
<header class="fixed top-0 left-0 right-0 z-50 px-8 py-4 flex items-center justify-between header-footer-bg">
    <!-- Logo -->
    <a href="/" class="text-xl font-bold tracking-widest uppercase no-underline logo-text">
        <?php if (($logo['type'] ?? 'text') === 'image' && !empty($logo['path'])): ?>
            <img src="/media/<?= ltrim($logo['path'], '/') ?>"
                 alt="<?= $escape($logo['alt'] ?? '') ?>"
                 style="height:32px;width:auto;display:block"
                 width="<?= $logo['width'] ?? 0 ?>"
                 height="<?= $logo['height'] ?? 0 ?>">
        <?php else: ?>
            <?= $escape($logo['text'] ?? 'MATER NATURA') ?>
        <?php endif; ?>
    </a>

    <!-- Navigation -->
    <nav class="flex items-center gap-4 text-sm font-medium">
        <?php foreach ($headerNav as $item): ?>
            <a href="<?= $escape($item['url'] ?? '#') ?>"
               class="nav-link no-underline"
               <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= $escape($item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
