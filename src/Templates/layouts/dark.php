<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->renderPartial('head', ['meta' => $meta, 'jsDataScript' => $jsDataScript]) ?>
</head>
<body class="theme-dark" style="display:flex;flex-direction:column;min-height:100dvh;margin:0;">
    <?php
    // Cargar settings del theme
    $themeLogo       = theme_setting('theme_logo') ?? ['type' => 'text', 'text' => 'MATER NATURA'];
    $themeHeaderItems = theme_setting('theme_header_items') ?? [];
    $themeFooterItems = theme_setting('theme_footer_items') ?? [];
    $themeFooter     = theme_setting('theme_footer') ?? [];
    ?>
    <?= $this->renderPartial('header', [
        'escape' => $escape,
        'isAuthenticated' => isset($_SESSION['mn_user']),
        'themeLogo'  => $themeLogo,
        'themeItems' => $themeHeaderItems,
    ]) ?>

    <main class="pt-20 pb-8 flex-1" style="view-transition-name:post-content;max-width:<?= $mainMaxWidth ?? '724px' ?>;width:100%;margin:0 auto;">
        <?= $content ?>
    </main>

    <?= $this->renderPartial('footer', [
        'escape'      => $escape,
        'themeLogo'   => $themeLogo,
        'themeItems'  => $themeFooterItems,
        'themeFooter' => $themeFooter,
    ]) ?>

    <script src='/assets/js/alpine.min.js' defer></script>
    <script src='/assets/js/app.js?v=4' defer></script>
    <?php if (isset($pluginManager)) {
        $footerResults = $pluginManager->executeHook('page.footer', ['escape' => $escape]);
        echo implode("\n", array_filter($footerResults, 'is_string'));
    } ?>
</body>
</html>
