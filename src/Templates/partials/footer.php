<?php declare(strict_types=1);

/**
 * Footer del sitio — renderizado desde theme settings
 *
 * Variables disponibles: $escape, $themeNav, $themeSocial, $themeFooter
 */
$nav     = $themeNav ?? [];
$social  = $themeSocial ?? [];
$footer  = $themeFooter ?? [];

// Filtrar enlaces de navegación para footer
$footerNav = array_filter($nav, fn($item) => ($item['scope'] ?? 'both') === 'both' || ($item['scope'] ?? '') === 'footer');

$copyright = $footer['copyright'] ?? MATER_SITE_NAME;
$currentYear = date('Y');
?>
<footer class="w-full py-4 px-8 flex justify-between items-center header-footer-bg">
    <!-- Copyright + Logo -->
    <div class="text-sm logo-text">
        <?= $escape($copyright) ?><?= $copyright ? ' · ' : '' ?><?= $currentYear ?>
    </div>

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
