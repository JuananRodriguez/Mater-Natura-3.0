<div class="post-editor">
    <!-- Form principal para crear/editar usuario -->
    <form method="POST" action="/admin/usuarios/editar" id="user-form">
        <!-- Editor Header / Actions -->
        <div class="editor-header">
            <div class="editor-header-left">
                <a href="/admin/usuarios" class="brutalist-btn-link"><?= svg_icon('chevron-left') ?> Volver a Usuarios</a>
            </div>
            <div class="editor-header-right">
                <button type="submit" class="brutalist-btn-primary">
                    <?= $user ? 'Guardar cambios' : 'Crear usuario' ?>
                </button>
            </div>
        </div>

        <?= $csrfField ?>

        <?php if ($user): ?>
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
        <?php endif; ?>

        <?php
        $newPassword = $_SESSION['new_password'] ?? null;
        unset($_SESSION['new_password']);
        ?>
        <?php if ($newPassword): ?>
            <div class="alert alert-success" style="margin:0 32px 16px">
                <strong>Nueva contraseña generada:</strong>
                <code style="display:block;margin-top:0.5rem;padding:0.5rem;background:#f4f4f4;border:1px solid #ccc;font-size:1.1em"><?= $escape($newPassword) ?></code>
                <small style="color:#888;display:block;margin-top:0.25rem">Anótala en un lugar seguro. No se volverá a mostrar.</small>
            </div>
        <?php endif; ?>

        <div class="post-editor-body">
            <div class="editor-canvas">
                <div class="brutalist-card" style="padding:1.5rem">
                    <!-- Username -->
                    <div class="settings-field" style="margin-bottom:1rem">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" id="username" name="username"
                               value="<?= $escape($user['username'] ?? '') ?>"
                               <?= $user ? 'readonly' : 'required' ?>
                               class="brutalist-input" placeholder="ej: juanan">
                        <?php if ($user): ?>
                            <small style="color:#888">El nombre de usuario no se puede cambiar.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="settings-field" style="margin-bottom:1rem">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required
                               value="<?= $escape($user['email'] ?? '') ?>"
                               class="brutalist-input" placeholder="usuario@ejemplo.com">
                    </div>

                    <!-- Rol -->
                    <div class="settings-field" style="margin-bottom:1rem">
                        <label for="role">Rol</label>
                        <select id="role" name="role" class="brutalist-input">
                            <option value="editor" <?= ($user['role'] ?? 'editor') === 'editor' ? 'selected' : '' ?>>Editor</option>
                            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        </select>
                    </div>

                    <?php if (!$user): ?>
                    <!-- Password (solo para nuevos usuarios) -->
                    <div class="settings-field">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required
                               autocomplete="new-password"
                               class="brutalist-input" placeholder="Mínimo 8 caracteres">
                        <small style="color:#888">Mínimo 8 caracteres, incluye mayúsculas y números.</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="settings-panel">
                <?php if ($user): ?>
                <div class="settings-section">
                    <h3 class="settings-section-title">Información</h3>
                    <div class="settings-field">
                        <label>Registrado</label>
                        <p style="margin:0;font-size:0.9em"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                    </div>
                    <?php if ($user['last_login_at']): ?>
                    <div class="settings-field">
                        <label>Último acceso</label>
                        <p style="margin:0;font-size:0.9em"><?= date('d/m/Y H:i', strtotime($user['last_login_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="settings-field">
                        <label>ID</label>
                        <p style="margin:0;font-size:0.9em">#<?= $user['id'] ?></p>
                    </div>
                </div>
                <div class="settings-section">
                    <h3 class="settings-section-title">Contraseña</h3>
                    <p style="font-size:0.85em;color:#888;margin:0 0 0.75rem">
                        Genera una nueva contraseña aleatoria para este usuario.
                    </p>
                    <button type="button" class="brutalist-btn-secondary" style="width:100%"
                            onclick="var f=document.getElementById('user-form');var ff=document.createElement('form');ff.method='POST';ff.action='/admin/usuarios/restablecer-password';var i1=document.createElement('input');i1.type='hidden';i1.name='_csrf_token';i1.value=f.querySelector('[name=_csrf_token]').value;ff.appendChild(i1);var i2=document.createElement('input');i2.type='hidden';i2.name='id';i2.value='<?= $user['id'] ?>';ff.appendChild(i2);if(confirm('¿Restablecer la contraseña de <?= $escape($user['username']) ?>?')){document.body.appendChild(ff);ff.submit();}">
                        <?= svg_icon('cog') ?> Restablecer contraseña
                    </button>
                </div>
                <?php else: ?>
                <div class="settings-section">
                    <h3 class="settings-section-title">Nuevo usuario</h3>
                    <p style="font-size:0.85em;color:#888;margin:0">
                        Completa los campos para crear un nuevo usuario.
                        Podrá acceder al panel de administración según su rol.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
