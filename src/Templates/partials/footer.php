<?php declare(strict_types=1);

/**
 * Footer del sitio — renderizado desde theme settings
 *
 * Variables disponibles: $escape, $themeLogo, $themeNav, $themeSocial, $themeFooter
 */
$logo   = $themeLogo ?? ['type' => 'text', 'text' => 'MATER NATURA'];
$nav    = $themeNav ?? [];
$social = $themeSocial ?? [];

// Filtrar enlaces de navegación para footer
$footerNav = array_filter($nav, fn($item) => ($item['scope'] ?? 'both') === 'both' || ($item['scope'] ?? '') === 'footer');
?>
<footer class="w-full py-4 px-8 flex justify-between items-center header-footer-bg">
    <!-- Logo -->
    <a href="/" class="text-lg font-bold tracking-widest uppercase no-underline logo-text">
        <?php if (($logo['type'] ?? 'text') === 'image' && !empty($logo['path'])): ?>
            <img src="/media/<?= ltrim($logo['path'], '/') ?>"
                 alt="<?= $escape($logo['alt'] ?? '') ?>"
                 style="height:24px;width:auto;display:block"
                 width="<?= $logo['width'] ?? 0 ?>"
                 height="<?= $logo['height'] ?? 0 ?>">
        <?php else: ?>
            <?= $escape($logo['text'] ?? 'MATER NATURA') ?>
        <?php endif; ?>
    </a>

    <!-- Footer Navigation + Social -->
    <div class="flex items-center gap-4 text-sm">
        <?php foreach ($footerNav as $item): ?>
            <a href="<?= $escape($item['url'] ?? '#') ?>"
               class="nav-link no-underline"
               <?= ($item['target'] ?? '_self') === '_blank' ? 'target="_blank" rel="noopener"' : '' ?>>
                <?= $escape($item['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>

        <?php foreach ($social as $s): ?>
            <a href="<?= $escape($s['url'] ?? '#') ?>"
               target="_blank"
               rel="noopener"
               class="nav-link no-underline"
               aria-label="<?= $escape($s['label'] ?: $s['platform'] ?? '') ?>">
                <?= social_svg($s['platform'] ?? 'custom') ?>
            </a>
        <?php endforeach; ?>
    </div>
</footer>
