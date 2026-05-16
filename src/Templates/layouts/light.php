<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->renderPartial('head', ['meta' => $meta, 'jsDataScript' => $jsDataScript]) ?>
</head>
<body class="theme-light" style="display:flex;flex-direction:column;min-height:100dvh;margin:0;">
    <?php
    // Cargar settings del theme
    $themeLogo   = theme_setting('theme_logo') ?? ['type' => 'text', 'text' => 'MATER NATURA'];
    $themeNav    = theme_setting('theme_nav') ?? [];
    $themeSocial = theme_setting('theme_social') ?? [];
    $themeFooter = theme_setting('theme_footer') ?? [];
    ?>
    <?= $this->renderPartial('header', [
        'escape' => $escape,
        'isAuthenticated' => isset($_SESSION['mn_user']),
        'themeLogo'   => $themeLogo,
        'themeNav'    => $themeNav,
        'themeSocial' => $themeSocial,
    ]) ?>

    <main class="pt-20 pb-8 flex-1" style="max-width:724px;width:100%;margin:0 auto;">
        <?= $content ?>
    </main>

    <?= $this->renderPartial('footer', [
        'escape'      => $escape,
        'themeNav'    => $themeNav,
        'themeSocial' => $themeSocial,
        'themeFooter' => $themeFooter,
    ]) ?>

    <script src='/assets/js/alpine.min.js' defer></script>
    <script src='/assets/js/app.js?v=2' defer></script>
    <?php if (isset($pluginManager)) {
        $footerResults = $pluginManager->executeHook('page.footer', ['escape' => $escape]);
        echo implode("\n", array_filter($footerResults, 'is_string'));
    } ?>
</body>
</html>
