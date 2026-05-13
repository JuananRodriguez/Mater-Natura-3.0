<div class="admin-wrapper" x-data="postList()">
    <div class="admin-header">
        <h1>Posts</h1>
        <a href="/admin/posts/editar" class="btn btn-primary"><?= svg_icon('plus') ?> Nuevo post</a>
    </div>

    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <p>No hay posts todavia.</p>
            <a href="/admin/posts/editar" class="btn btn-primary">Crear el primer post</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Slug</th>
                    <th>Estado</th>
                    <th>Visibilidad</th>
                    <th>Template</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                <?php $pId = is_object($p) ? $p->id : $p['id']; ?>
                <?php $pTitle = is_object($p) ? $p->title : $p['title']; ?>
                <?php $pSlug = is_object($p) ? $p->slug : $p['slug']; ?>
                <?php $pStatus = is_object($p) ? $p->status : $p['status']; ?>
                <?php $pVisibility = is_object($p) ? ($p->visibility ?? 'public') : ($p['visibility'] ?? 'public'); ?>
                <?php $pTemplate = is_object($p) ? $p->template : $p['template']; ?>
                <?php $pCreated = is_object($p) ? $p->createdAt : $p['created_at']; ?>
                <tr>
                    <td class="cell-title"><?= $escape($pTitle) ?></td>
                    <td class="cell-slug"><a href="/<?= rawurlencode($pSlug) ?>" target="_blank" rel="noopener"><?= $escape($pSlug) ?></a></td>
                    <td>
                        <span class="badge badge-<?= $pStatus ?>">
                            <?= $pStatus ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($pVisibility === 'private'): ?>
                            <span class="badge badge-private">Privado</span>
                        <?php elseif ($pVisibility === 'password'): ?>
                            <span class="badge badge-password">Protegido</span>
                        <?php else: ?>
                            <span class="badge badge-public">Publico</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $escape($pTemplate) ?></td>
                    <td><?= date('d/m/Y', strtotime($pCreated)) ?></td>
                    <td class="cell-actions">
                        <a href="/admin/posts/editar?id=<?= $pId ?>" class="btn-sm"><?= svg_icon('pencil') ?> Editar</a>
                        <form method="POST" action="/admin/posts/eliminar"
                              onsubmit="return confirm('¿Eliminar este post?')"
                              style="display:inline">
                            <?= $csrfField ?>
                            <input type="hidden" name="id" value="<?= $pId ?>">
                            <button type="submit" class="btn-sm btn-danger"><?= svg_icon('trash') ?> Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="/admin/posts?page=<?= $currentPage - 1 ?>"><?= svg_icon('chevron-left') ?> Anterior</a>
            <?php endif; ?>
            <span>Pagina <?= $currentPage ?> de <?= $totalPages ?></span>
            <?php if ($currentPage < $totalPages): ?>
                <a href="/admin/posts?page=<?= $currentPage + 1 ?>">Siguiente <?= svg_icon('chevron-right') ?></a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function postList() {
    return { /* Alpine state for post list if needed */ };
}
</script>
