<?php declare(strict_types=1); ?>
<div class="admin-wrapper">
    <h1>Dashboard</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?= $postCount ?></span>
            <span class="stat-label">Posts</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= $pageCount ?></span>
            <span class="stat-label">Páginas</span>
        </div>
        <div class="stat-card stat-warning">
            <span class="stat-number"><?= $draftCount ?></span>
            <span class="stat-label">Borradores</span>
        </div>
    </div>

    <div class="admin-actions">
        <a href="/admin/posts/editar" class="btn btn-primary"><?= svg_icon('plus') ?> Nuevo post</a>
        <a href="/admin/pages/editar" class="btn btn-secondary"><?= svg_icon('note-add') ?> Nueva pagina</a>
    </div>
</div>
