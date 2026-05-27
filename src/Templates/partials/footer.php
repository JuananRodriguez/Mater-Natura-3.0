<?php declare(strict_types=1);

/**
 * Footer del sitio — renderizado desde theme settings
 *
 * Variables disponibles: $escape, $themeLogo, $themeItems, $themeFooter
 */
$logo  = $themeLogo ?? ['type' => 'text', 'text' => 'MATER NATURA'];
$items = $themeItems ?? [];
$footer = $themeFooter ?? [];
$footerText = $footer['text'] ?? '';
?>
<footer class="w-full py-4 px-8 flex justify-between items-center header-footer-bg" style="view-transition-name:site-footer">
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

    <!-- Items -->
    <div class="flex items-center gap-4 text-sm">
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
    </div>
</footer>
