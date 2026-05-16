<?php declare(strict_types=1);

/**
 * Header del sitio — renderizado desde theme settings
 *
 * Variables disponibles: $escape, $isAuthenticated, $themeLogo, $themeItems
 */
$logo  = $themeLogo ?? ['type' => 'text', 'text' => 'MATER NATURA'];
$items = $themeItems ?? [];
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

    <!-- Items -->
    <nav class="flex items-center gap-4 text-sm font-medium">
        <?php foreach ($items as $item): ?>
            <?php if (($item['type'] ?? 'nav') === 'nav'): ?>
                <a href="<?= $escape($item['url'] ?? '#') ?>"
                   class="nav-link no-underline"
                   <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                    <?= $escape($item['label'] ?? '') ?>
                </a>
            <?php else: ?>
                <a href="<?= $escape($item['url'] ?? '#') ?>"
                   target="_blank"
                   rel="noopener"
                   class="nav-link no-underline"
                   aria-label="<?= $escape($item['label'] ?: $item['platform'] ?? '') ?>">
                    <?= social_svg($item['platform'] ?? 'custom') ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</header>
