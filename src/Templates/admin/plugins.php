<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Plugins</h1>
    </div>

    <?php if (empty($plugins)): ?>
        <div class="empty-state">
            <p>No hay plugins instalados.</p>
            <p class="empty-sub">Los plugins se instalan añadiendo un directorio en <code>src/Plugins/</code>.</p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Versión</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                <tr>
                    <td><strong><?= $escape($plugin['name']) ?></strong></td>
                    <td><?= $escape($plugin['version']) ?></td>
                    <td><?= $escape($plugin['description']) ?></td>
                    <td>
                        <span class="badge badge-<?= $plugin['enabled'] ? 'published' : 'draft' ?>">
                            <?= $plugin['enabled'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
