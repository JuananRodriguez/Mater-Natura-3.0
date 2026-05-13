<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Usuarios</h1>
        <a href="/admin/usuarios/editar" class="btn btn-primary"><?= svg_icon('plus') ?> Nuevo usuario</a>
    </div>

    <?php if (empty($users)): ?>
        <div class="empty-state">
            <p>No hay usuarios todavía.</p>
            <a href="/admin/usuarios/editar" class="btn btn-primary">Crear el primer usuario</a>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Último acceso</th>
                    <th>Registrado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="cell-title"><?= $escape($u['username']) ?></td>
                    <td><?= $escape($u['email']) ?></td>
                    <td>
                        <span class="badge badge-<?= $u['role'] ?>">
                            <?= $u['role'] === 'admin' ? 'Administrador' : 'Editor' ?>
                        </span>
                    </td>
                    <td><?= $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : '—' ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td class="cell-actions">
                        <a href="/admin/usuarios/editar?id=<?= $u['id'] ?>" class="btn-sm"><?= svg_icon('pencil') ?> Editar</a>
                        <form method="POST" action="/admin/usuarios/eliminar"
                              onsubmit="return confirm('¿Eliminar este usuario? Se perderán todos sus posts y páginas.')"
                              style="display:inline">
                            <?= $csrfField ?>
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger"><?= svg_icon('trash') ?> Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
