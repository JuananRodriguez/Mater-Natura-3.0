<?php declare(strict_types=1); ?>
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

                <!-- SEO -->
                <div class="settings-section" style="flex:1;border-bottom:none">
                    <h3 class="settings-section-title">Optimización SEO</h3>
                    <div class="settings-field">
                        <div class="settings-field-header">
                            <label for="meta-title">Meta Title</label>
                            <span class="meta-counter" id="meta-title-counter"><?= strlen($page->metaTitle ?? '') ?>/60</span>
                        </div>
                        <input type="text" id="meta-title" name="meta_title"
                               value="<?= $escape($page->metaTitle ?? '') ?>"
                               placeholder="Título optimizado" class="brutalist-input"
                               oninput="updateMetaCounters()">
                    </div>
                    <div class="settings-field">
                        <div class="settings-field-header">
                            <label for="meta-desc">Meta Descripción</label>
                            <span class="meta-counter" id="meta-desc-counter"><?= strlen($page->metaDescription ?? '') ?>/160</span>
                        </div>
                        <textarea id="meta-desc" name="meta_description"
                                  placeholder="Resumen atractivo de la página…"
                                  class="brutalist-input" style="height:100px;resize:none"
                                  oninput="updateMetaCounters()"><?= $escape($page->metaDescription ?? '') ?></textarea>
                    </div>
                    <!-- SEO Preview -->
                    <div class="seo-preview" id="seo-preview">
                        <p style="font-family:'Inter',sans-serif;font-size:12px;color:#5e5e5e;text-transform:uppercase;font-weight:bold;margin:0 0 4px">Vista previa de búsqueda</p>
                        <p class="seo-preview-title" id="seo-title-preview"><?= $escape(($page->metaTitle ?: $page->title ?? 'Título de la página') . ' — ' . MATER_SITE_NAME) ?></p>
                        <p class="seo-preview-url" id="seo-url-preview"><?= rtrim(MATER_BASE_URL, '/') ?>/<?= $escape($page->slug ?? 'page-slug') ?></p>
                        <p class="seo-preview-desc" id="seo-desc-preview"><?= $escape($page->metaDescription ?: 'Resumen de la página que aparecerá en los resultados de búsqueda.') ?></p>
                        <div class="seo-indicators" style="margin-top:8px;font-size:11px;display:flex;gap:12px">
                            <span id="seo-title-indicator" style="color:<?= (strlen($page->metaTitle ?? '') > 60) ? '#e53e3e' : ((strlen($page->metaTitle ?? '') >= 30) ? '#38a169' : '#dd6b20') ?>">
                                ● Título: <?= (strlen($page->metaTitle ?? '') > 60) ? 'demasiado largo' : ((strlen($page->metaTitle ?? '') >= 30) ? 'óptimo' : 'corto') ?>
                            </span>
                            <span id="seo-desc-indicator" style="color:<?= (strlen($page->metaDescription ?? '') > 160) ? '#e53e3e' : ((strlen($page->metaDescription ?? '') >= 120) ? '#38a169' : ((strlen($page->metaDescription ?? '') > 0) ? '#dd6b20' : '#718096')) ?>">
                                ● Descripción: <?= (strlen($page->metaDescription ?? '') > 160) ? 'demasiado larga' : ((strlen($page->metaDescription ?? '') >= 120) ? 'óptima' : ((strlen($page->metaDescription ?? '') > 0) ? 'corta' : 'sin definir')) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateMetaCounters() {
    // Contadores
    const mt = document.getElementById('meta-title');
    const md = document.getElementById('meta-desc');
    if (mt) {
        const len = mt.value.length;
        document.getElementById('meta-title-counter').textContent = len + '/60';
        mt.style.borderColor = len > 60 ? '#e53e3e' : (len >= 30 ? '#38a169' : '#dd6b20');

        // Actualizar preview del título
        const titleEl = document.getElementById('seo-title-preview');
        if (titleEl) {
            titleEl.textContent = (mt.value || document.getElementById('title')?.value || 'Título de la página') + ' — <?= addslashes(MATER_SITE_NAME) ?>';
        }
    }
    if (md) {
        const len = md.value.length;
        document.getElementById('meta-desc-counter').textContent = len + '/160';
        md.style.borderColor = len > 160 ? '#e53e3e' : (len >= 120 ? '#38a169' : (len > 0 ? '#dd6b20' : ''));

        // Actualizar preview de la descripción
        const descEl = document.getElementById('seo-desc-preview');
        if (descEl) {
            descEl.textContent = md.value || 'Resumen de la página que aparecerá en los resultados de búsqueda.';
        }
    }

    // Actualizar URL preview con el slug
    const slug = document.getElementById('slug')?.value || 'page-slug';
    const urlEl = document.getElementById('seo-url-preview');
    if (urlEl) {
        urlEl.textContent = '<?= addslashes(rtrim(MATER_BASE_URL, '/')) ?>/' + slug;
    }

    // Indicadores de calidad
    const titleInd = document.getElementById('seo-title-indicator');
    const descInd = document.getElementById('seo-desc-indicator');
    if (titleInd) {
        const len = mt ? mt.value.length : 0;
        if (len > 60) {
            titleInd.style.color = '#e53e3e';
            titleInd.textContent = '● Título: demasiado largo';
        } else if (len >= 30) {
            titleInd.style.color = '#38a169';
            titleInd.textContent = '● Título: óptimo';
        } else {
            titleInd.style.color = '#dd6b20';
            titleInd.textContent = '● Título: corto';
        }
    }
    if (descInd) {
        const len = md ? md.value.length : 0;
        if (len > 160) {
            descInd.style.color = '#e53e3e';
            descInd.textContent = '● Descripción: demasiado larga';
        } else if (len >= 120) {
            descInd.style.color = '#38a169';
            descInd.textContent = '● Descripción: óptima';
        } else if (len > 0) {
            descInd.style.color = '#dd6b20';
            descInd.textContent = '● Descripción: corta';
        } else {
            descInd.style.color = '#718096';
            descInd.textContent = '● Descripción: sin definir';
        }
    }
}

// Actualizar preview cuando cambie el slug
(function() {
    const slugInput = document.getElementById('slug');
    const titleInput = document.getElementById('title');
    if (slugInput) {
        slugInput.addEventListener('input', updateMetaCounters);
    }
    if (titleInput) {
        titleInput.addEventListener('input', updateMetaCounters);
    }
})();
</script>
