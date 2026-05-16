<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?= $template->renderPartial('head', ['meta' => $meta, 'jsDataScript' => $jsDataScript]) ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Geist:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/admin.css?v=5">
    <link rel="stylesheet" href="/assets/quill/quill.snow.css">
</head>
<body class="theme-light admin-body">
    <div class="admin-layout" x-data="{ sidebarOpen: true }">
        <aside class="admin-sidebar" :class="{ 'sidebar-collapsed': !sidebarOpen }">
            <div class="sidebar-header">
                <a href="/admin" class="sidebar-logo"><?= $escape(MATER_SITE_NAME) ?></a>
            </div>
            <nav class="sidebar-nav">
                <a href="/admin" class="sidebar-link<?= ($currentNav ?? '') === 'dashboard' ? ' active' : '' ?>"><?= svg_icon('speedometer') ?> Dashboard</a>
                <a href="/admin/posts" class="sidebar-link<?= ($currentNav ?? '') === 'posts' ? ' active' : '' ?>"><?= svg_icon('pencil') ?> Posts</a>
                <a href="/admin/pages" class="sidebar-link<?= ($currentNav ?? '') === 'pages' ? ' active' : '' ?>"><?= svg_icon('file') ?> Paginas</a>

                <!-- Plugins activos -->
                <?php if (!empty($pluginMenuItems)): ?>
                    <?php foreach ($pluginMenuItems as $item): ?>
                        <?php
                        $itemUrl = $escape($item['url']);
                        $itemIcon = !empty($item['icon']) ? svg_icon($item['icon']) : svg_icon('puzzle');
                        $itemLabel = $escape($item['label'] ?? 'Plugin');
                        $itemSlug = trim(str_replace('admin/', '', parse_url($item['url'], PHP_URL_PATH)), '/');
                        ?>
                        <a href="<?= $itemUrl ?>" class="sidebar-link<?= ($currentNav ?? '') === $itemSlug ? ' active' : '' ?>"><?= $itemIcon ?> <?= $itemLabel ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="/admin/usuarios" class="sidebar-link<?= ($currentNav ?? '') === 'users' ? ' active' : '' ?>"><?= svg_icon('people') ?> Usuarios</a>
                <a href="/admin/apariencia" class="sidebar-link<?= ($currentNav ?? '') === 'theme' ? ' active' : '' ?>"><?= svg_icon('mater-natura') ?> Apariencia</a>
                <a href="/admin/plugins" class="sidebar-link<?= ($currentNav ?? '') === 'plugins' ? ' active' : '' ?>"><?= svg_icon('puzzle') ?> Plugins</a>
                <a href="/admin/ajustes" class="sidebar-link<?= ($currentNav ?? '') === 'settings' ? ' active' : '' ?>"><?= svg_icon('cog') ?> Ajustes</a>
                <?php endif; ?>

                <hr class="sidebar-divider">
                <a href="/" class="sidebar-link"><?= svg_icon('globe-alt') ?> Ver sitio</a>
                <a href="/logout" class="sidebar-link"><?= svg_icon('account-logout') ?> Salir</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-toolbar">
                <div class="breadcrumb">
                    <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen"><?= svg_icon('hamburger-menu') ?></button>
                    <span class="breadcrumb-sep" style="color:#ccc;margin:0 0.3rem;">/</span>
                    <span><?= $escape($pageTitle ?? 'Dashboard') ?></span>
                </div>
                <div class="admin-user">
                    <span class="avatar"><?= strtoupper(substr($escape($user['username'] ?? 'A'), 0, 1)) ?></span>
                    <span><?= $escape($user['username'] ?? '') ?></span>
                </div>
            </header>

            <div class="admin-content<?= isset($contentFullWidth) && $contentFullWidth ? ' admin-content--full' : '' ?>">
                <?php if (isset($adminError) && $adminError): ?>
                    <div class="alert alert-error" x-data="{ show: true }" x-show="show">
                        <?= $escape($adminError) ?>
                        <button @click="show = false" class="alert-close"><?= svg_icon('x') ?></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($adminSuccess) && $adminSuccess): ?>
                    <div class="alert alert-success" x-data="{ show: true }" x-show="show">
                        <?= $escape($adminSuccess) ?>
                        <button @click="show = false" class="alert-close"><?= svg_icon('check') ?></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </main>
    </div>

    <script src="/assets/quill/quill.min.js"></script>
    <script src="/assets/js/alpine.min.js" defer></script>
    <script src="/assets/js/admin.js" defer></script>
</body>
</html>
