<div class="post-editor">
    <!-- Editor Header / Actions -->
    <div class="editor-header">
        <div class="editor-header-left">
            <a href="/admin/pages" class="brutalist-btn-link"><?= svg_icon('chevron-left') ?> Volver a Páginas</a>
        </div>
        <div class="editor-header-right">
            <button type="submit" form="page-form" class="brutalist-btn-secondary" name="action" value="draft">Guardar Borrador</button>
            <button type="submit" form="page-form" class="brutalist-btn-primary" name="action" value="publish"><?= $page ? 'Actualizar' : 'Publicar' ?></button>
        </div>
    </div>

    <form method="POST" action="/admin/pages/editar" id="page-form">
        <?= $csrfField ?>

        <?php if ($page): ?>
            <input type="hidden" name="id" value="<?= $page->id ?>">
        <?php endif; ?>
        <input type="hidden" name="status" id="hidden-status" value="<?= $escape(($page->status ?? 'draft')) ?>">

        <?php if (isset($_SESSION['admin_error'])): ?>
            <div class="alert alert-error" style="margin:0 32px 16px"><?= $escape($_SESSION['admin_error']) ?></div>
            <?php unset($_SESSION['admin_error']); ?>
        <?php endif; ?>

        <div class="post-editor-body">
            <div class="editor-canvas">
                <!-- Title Input -->
                <input type="text" id="title" name="title" required
                       value="<?= $escape($page->title ?? '') ?>"
                       placeholder="Título de la página..."
                       class="editor-title" autofocus>

                <!-- Contenido (Brutalist Card) -->
                <div class="brutalist-card">
                    <textarea id="editor-description" name="content" rows="15"
                              placeholder="Escribe el contenido aquí…"><?= $escape($page->content ?? '') ?></textarea>
                </div>
            </div>

            <!-- Settings Panel -->
            <div class="settings-panel">
                <!-- Publish Settings -->
                <div class="settings-section">
                    <h3 class="settings-section-title">Ajustes de la página</h3>
                    <div class="settings-field">
                        <label for="sel-template">Plantilla</label>
                        <select id="sel-template" name="template" class="brutalist-input">
                            <option value="dark" <?= ($page->template ?? 'dark') === 'dark' ? 'selected' : '' ?>>Oscura</option>
                            <option value="light" <?= ($page->template ?? '') === 'light' ? 'selected' : '' ?>>Clara</option>
                        </select>
                    </div>
                    <div class="settings-field">
                        <label for="sel-status">Estado</label>
                        <select id="sel-status" name="status" class="brutalist-input">
                            <option value="published" <?= ($page->status ?? 'published') === 'published' ? 'selected' : '' ?>>Publicada</option>
                            <option value="draft" <?= ($page->status ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                        </select>
                    </div>
                </div>

                <!-- Slug -->
                <div class="settings-section">
                    <h3 class="settings-section-title">URL</h3>
                    <div class="settings-field">
                        <div class="url-slug-group">
                            <span class="url-prefix">/</span>
                            <input type="text" id="slug" name="slug"
                                   value="<?= $escape($page->slug ?? '') ?>"
                                   placeholder="page-slug"
                                   class="brutalist-input">
                        </div>
                    </div>
                </div>

                <?php if ($page && $page->isHome): ?>
                    <div class="brutalist-notice">
                        <?= svg_icon('star') ?> Esta página es la página de inicio.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
