<?php declare(strict_types=1); ?>
<div class="page-header">
    <h1><?= svg_icon('puzzle') ?> Plugins</h1>
</div>

<?php if (empty($plugins)): ?>
    <div class="empty-state">
        <?= svg_icon('puzzle') ?>
        <p>No hay plugins instalados.</p>
    </div>
<?php else: ?>
    <div class="plugins-grid">
        <?php foreach ($plugins as $plugin): ?>
            <div class="plugin-card">
                <div class="plugin-card-header">
                    <div class="plugin-icon">
                        <?= svg_icon('puzzle') ?>
                    </div>
                    <div class="plugin-info">
                        <h3><?= $escape($plugin['name']) ?></h3>
                        <span class="plugin-slug"><?= $escape($plugin['slug']) ?></span>
                    </div>
                    <div class="plugin-status">
                        <?php if ($plugin['enabled']): ?>
                            <span class="badge badge-active">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Inactivo</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="plugin-card-body">
                    <p><?= $escape($plugin['description'] ?? 'Sin descripción') ?></p>
                </div>

                <div class="plugin-card-footer">
                    <?php if ($plugin['enabled']): ?>
                        <form method="POST" action="/admin/plugins/desactivar" class="inline-form">
                            <?= $csrfField ?>
                            <input type="hidden" name="slug" value="<?= $escape($plugin['slug']) ?>">
                            <button type="submit" class="btn btn-sm btn-outline">
                                <?= svg_icon('ban') ?> Desactivar
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/admin/plugins/activar" class="inline-form">
                            <?= $csrfField ?>
                            <input type="hidden" name="slug" value="<?= $escape($plugin['slug']) ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <?= svg_icon('check') ?> Activar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
