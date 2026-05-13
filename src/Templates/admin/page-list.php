<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Paginas</h1>
        <a href="/admin/pages/editar" class="btn btn-primary"><?= svg_icon('plus') ?> Nueva pagina</a>
    </div>

    <?php if (empty($pages)): ?>
        <div class="empty-state">
            <p>No hay paginas todavia.</p>
            <a href="/admin/pages/editar" class="btn btn-primary">Crear la primera pagina</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Slug</th>
                    <th>Estado</th>
                    <th>Home</th>
                    <th>Template</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <?php $pId = is_object($p) ? $p->id : $p['id']; ?>
                <?php $pTitle = is_object($p) ? $p->title : $p['title']; ?>
                <?php $pSlug = is_object($p) ? $p->slug : $p['slug']; ?>
                <?php $pStatus = is_object($p) ? $p->status : $p['status']; ?>
                <?php $pIsHome = is_object($p) ? $p->isHome : ($p['is_home'] ?? false); ?>
                <?php $pTemplate = is_object($p) ? $p->template : $p['template']; ?>
                <tr>
                    <td class="cell-title"><?= $escape($pTitle) ?></td>
                    <td class="cell-slug"><?= $escape($pSlug) ?></td>
                    <td><span class="badge badge-<?= $pStatus ?>"><?= $pStatus ?></span></td>
                    <td><?= $pIsHome ? svg_icon('star') : '' ?></td>
                    <td><?= $escape($pTemplate) ?></td>
                    <td class="cell-actions">
                        <a href="/admin/pages/editar?id=<?= $pId ?>" class="btn-sm"><?= svg_icon('pencil') ?> Editar</a>
                        <?php if (!$pIsHome): ?>
                        <form method="POST" action="/admin/pages/eliminar"
                              onsubmit="return confirm('¿Eliminar esta pagina?')"
                              style="display:inline">
                            <?= $csrfField ?>
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <button type="submit" class="btn-sm btn-danger"><?= svg_icon('trash') ?> Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
