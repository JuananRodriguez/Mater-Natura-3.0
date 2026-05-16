<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $this->renderPartial('head', ['meta' => $meta, 'jsDataScript' => $jsDataScript]) ?>
</head>
<body class="theme-dark" style="display:grid;grid-template-rows:auto 1fr auto;min-height:100dvh;margin:0;">
    <?= $this->renderPartial('header', [
        'escape' => $escape,
        'isAuthenticated' => isset($_SESSION['mn_user']),
    ]) ?>

    <main class="pt-20">
        <?= $content ?>
    </main>

    <?= $this->renderPartial('footer', ['escape' => $escape]) ?>

    <script src='/assets/js/alpine.min.js' defer></script>
    <script src='/assets/js/app.js' defer></script>
    <?php if (isset($pluginManager)) {
        $footerResults = $pluginManager->executeHook('page.footer', ['escape' => $escape]);
        echo implode("\n", array_filter($footerResults, 'is_string'));
    } ?>
</body>
</html>
