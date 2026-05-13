<div class="admin-page-comments">
    <div class="page-header">
        <h1><?= svg_icon('comment-bubble') ?> Comentarios</h1>
    </div>

    <?php if (empty($comments)): ?>
        <div class="empty-state">
            <p>No hay comentarios todavia.</p>
        </div>
    <?php else: ?>
        <table class="admin-table comment-table">
            <thead>
                <tr>
                    <th class="col-author">Autor</th>
                    <th class="col-content">Comentario</th>
                    <th class="col-post">Post</th>
                    <th class="col-date">Fecha</th>
                    <th class="col-status">Estado</th>
                    <th class="col-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): 
                    $isPending = $comment['status'] === 'pending';
                    $isApproved = $comment['status'] === 'approved';
                    $isSpam = $comment['status'] === 'spam';
                    $rowClass = $isPending ? 'row-pending' : ($isSpam ? 'row-spam' : '');
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="col-author">
                        <strong><?= $escape($comment['author_name']) ?></strong>
                        <?php if (!empty($comment['ip_address'])): ?>
                            <br><small class="text-muted"><?= $escape($comment['ip_address']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="col-content">
                        <div class="comment-preview"><?= nl2br($escape($comment['content'])) ?></div>
                    </td>
                    <td class="col-post">
                        <a href="/<?= $escape($comment['post_slug']) ?>" target="_blank" class="post-link">
                            <?= $escape($comment['post_title']) ?>
                        </a>
                    </td>
                    <td class="col-date">
                        <time datetime="<?= $escape($comment['created_at']) ?>">
                            <?= date('d/m/Y H:i', strtotime($comment['created_at'])) ?>
                        </time>
                    </td>
                    <td class="col-status">
                        <?php if ($isPending): ?>
                            <span class="badge badge-pending">Pendiente</span>
                        <?php elseif ($isApproved): ?>
                            <span class="badge badge-approved">Aprobado</span>
                        <?php elseif ($isSpam): ?>
                            <span class="badge badge-spam">Spam</span>
                        <?php else: ?>
                            <span class="badge"><?= $escape($comment['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="col-actions">
                        <?php if ($isPending): ?>
                            <form method="POST" action="/admin/comments/aprobar" class="inline-form">
                                <?= $csrfField ?>
                                <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                                <button type="submit" class="btn-sm btn-approve" title="Aprobar"><?= svg_icon('check') ?></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="/admin/comments/eliminar" class="inline-form"
                              onsubmit="return confirm('¿Eliminar este comentario definitivamente?')">
                            <?= $csrfField ?>
                            <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                            <button type="submit" class="btn-sm btn-delete" title="Eliminar"><?= svg_icon('trash') ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
