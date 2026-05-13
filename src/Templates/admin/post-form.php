<div class="post-editor" x-data="postForm()">
    <!-- Editor Header / Actions -->
    <div class="editor-header">
        <div class="editor-header-left">
            <a href="/admin/posts" class="brutalist-btn-link"><?= svg_icon('chevron-left') ?> Volver a Posts</a>
        </div>
        <div class="editor-header-right">
            <span class="unsaved-text" x-show="hasChanges" style="display:none">Cambios sin guardar</span>
            <button type="submit" form="post-form" class="brutalist-btn-secondary" name="action" value="draft">Guardar Borrador</button>
            <button type="submit" form="post-form" class="brutalist-btn-primary" name="action" value="publish"><?= $post ? 'Actualizar' : 'Publicar' ?></button>
        </div>
    </div>

    <form method="POST" action="/admin/posts/editar" enctype="multipart/form-data" id="post-form">
        <?= $csrfField ?>

        <?php if ($post): ?>
            <input type="hidden" name="id" value="<?= $post->id ?>">
        <?php endif; ?>
        <input type="hidden" name="status" id="hidden-status" value="<?= $escape(($post->status ?? 'draft')) ?>">

        <?php if (isset($_SESSION['admin_error'])): ?>
            <div class="alert alert-error" style="margin:0 32px 16px"><?= $escape($_SESSION['admin_error']) ?></div>
            <?php unset($_SESSION['admin_error']); ?>
        <?php endif; ?>

        <div class="post-editor-body">
            <div class="editor-canvas">
                <!-- Title Input -->
                <input type="text" id="title" name="title" required
                       value="<?= $escape($post->title ?? '') ?>"
                       @input.debounce="generateSlug()"
                       placeholder="Título del post..."
                       class="editor-title" autofocus>

                <!-- Rich Text Editor (Brutalist Card) -->
                <div class="brutalist-card">
                    <textarea id="editor-description" name="description" rows="12"
                              placeholder="Escribe tu contenido aquí…"><?= $escape($post->description ?? '') ?></textarea>
                </div>

                <!-- Featured Image Upload -->
                <label class="brutalist-card-dashed" id="image-dropzone" @dragover.prevent @drop.prevent="handleDrop($event)">
                    <div class="icon" style="width:48px;height:48px;fill:#dadada;margin-bottom:16px"><?= svg_icon('cloud-upload') ?></div>
                <p style="font-family:'Geist',sans-serif;font-size:24px;font-weight:600;letter-spacing:-0.01em;margin:0 0 8px;color:#000">Arrastra una imagen aquí</p>
                <p style="font-family:'Inter',sans-serif;font-size:12px;color:#4c4546;margin:0 0 16px">o haz clic para seleccionar</p>
                <input type="file" id="image-input" x-ref="imageInput" name="image" accept="image/jpeg,image/png,image/webp"
                       tabindex="-1"
                       class="sr-only"
                       @change="previewImage($event)">
                <div id="image-preview-area" style="margin-top:16px;display:none"></div>
                <?php if ($post && $post->imageUrl): ?>
                    <div style="margin-top:16px">
                        <img src="/media/<?= $escape(ltrim($post->imageUrl, '/')) ?>" alt="Actual" style="max-width:200px;max-height:120px">
                        <p style="font-family:'Inter',sans-serif;font-size:12px;color:#4c4546;margin:4px 0 0">Imagen actual</p>
                    </div>
                <?php endif; ?>
            </label>
            </div>

            <!-- Settings Panel (Right) -->
            <div class="settings-panel">
                <!-- Publish Settings -->
                <div class="settings-section">
                    <h3 class="settings-section-title">Ajustes de publicación</h3>
                <div class="settings-field">
                    <label for="sel-template">Plantilla</label>
                    <select id="sel-template" name="template" class="brutalist-input">
                        <option value="dark" <?= ($post->template ?? 'dark') === 'dark' ? 'selected' : '' ?>>Oscura</option>
                        <option value="light" <?= ($post->template ?? '') === 'light' ? 'selected' : '' ?>>Clara</option>
                    </select>
                </div>
                <div class="settings-field">
                    <label for="sel-visibility">Visibilidad</label>
                    <select id="sel-visibility" name="visibility" class="brutalist-input"
                            onchange="togglePasswordField(this.value)">
                        <option value="public" <?= ($post->visibility ?? 'public') === 'public' ? 'selected' : '' ?>>Público</option>
                        <option value="private" <?= ($post->visibility ?? '') === 'private' ? 'selected' : '' ?>>Privado</option>
                        <option value="password" <?= ($post->visibility ?? '') === 'password' ? 'selected' : '' ?>>Protegido con contraseña</option>
                    </select>
                    <div id="password-field-wrapper" style="margin-top:8px;display:<?= ($post->visibility ?? '') === 'password' ? 'block' : 'none' ?>">
                        <label for="visibility-password" style="font-size:0.75rem;color:#666;display:block;margin-bottom:4px">Contraseña de acceso</label>
                        <input type="password" id="visibility-password" name="visibility_password"
                               class="brutalist-input" placeholder="Contraseña para ver el post"
                               autocomplete="new-password">
                        <?php if ($post && $post->visibility === 'password'): ?>
                            <p style="font-size:0.7rem;color:#999;margin-top:4px">Deja en blanco para mantener la contraseña actual.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="settings-field">
                    <label for="publish-date">Fecha de publicación</label>
                    <div style="display:flex;gap:8px">
                        <input type="date" id="publish-date" class="brutalist-input" value="<?= date('Y-m-d') ?>" disabled>
                        <input type="time" id="publish-time" class="brutalist-input" style="width:100px" value="<?= date('H:i') ?>" disabled>
                    </div>
                </div>
            </div>

            <!-- Slug -->
            <div class="settings-section">
                <h3 class="settings-section-title">URL</h3>
                <div class="settings-field">
                    <div class="url-slug-group">
                        <span class="url-prefix">/post/</span>
                        <input type="text" id="slug" name="slug"
                               value="<?= $escape($post->slug ?? '') ?>"
                               placeholder="post-title-slug"
                               class="brutalist-input">
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="settings-section" style="flex:1;border-bottom:none">
                <h3 class="settings-section-title">Optimización SEO</h3>
                <div class="settings-field">
                    <div class="settings-field-header">
                        <label for="meta-title">Meta Title</label>
                        <span class="meta-counter">0/60</span>
                    </div>
                    <input type="text" id="meta-title" name="meta_title"
                           value="<?= $escape($post->meta_title ?? '') ?>"
                           placeholder="Título optimizado" class="brutalist-input"
                           @input="updateSEOPreview()">
                </div>
                <div class="settings-field">
                    <div class="settings-field-header">
                        <label for="meta-desc">Meta Descripción</label>
                        <span class="meta-counter">0/160</span>
                    </div>
                    <textarea id="meta-desc" name="meta_description"
                              placeholder="Resumen atractivo del post…"
                              class="brutalist-input" style="height:100px;resize:none"
                              @input="updateSEOPreview()"><?= $escape($post->meta_description ?? '') ?></textarea>
                </div>
                <!-- SEO Preview -->
                <div class="seo-preview" id="seo-preview">
                    <p style="font-family:'Inter',sans-serif;font-size:12px;color:#5e5e5e;text-transform:uppercase;font-weight:bold;margin:0 0 4px">Vista previa</p>
                    <p class="seo-preview-title" id="seo-title-preview"><?= $escape($post->title ?? 'Título del post') ?> — <?= $escape(MATER_SITE_NAME) ?></p>
                    <p class="seo-preview-url" id="seo-url-preview">https://tusitio.com/post/<?= $escape($post->slug ?? 'post-title') ?></p>
                    <p class="seo-preview-desc" id="seo-desc-preview"><?= $escape($post->meta_description ?? 'Resumen del post que aparecerá en los resultados de búsqueda.') ?></p>
                </div>
            </div>
        </div>
        </div>
    </form>
</div>

<script>
function postForm() {
    return {
        hasChanges: false,
        originalTitle: '<?= addslashes($post->title ?? '') ?>',
        generateSlug() {
            const title = document.getElementById('title').value;
            const slugEl = document.getElementById('slug');
            if (!slugEl.value) {
                slugEl.value = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim();
            }
            this.hasChanges = title !== this.originalTitle;
            this.updateSEOPreview();
        },
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const area = document.getElementById('image-preview-area');
                    area.style.display = 'block';
                    area.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:200px;max-height:120px">`;
                };
                reader.readAsDataURL(file);
            }
        },
        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                const input = document.getElementById('image-input');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                this.previewImage({target: input});
            }
        },
        updateSEOPreview() {
            const title = document.getElementById('title')?.value || 'Título del post';
            const slug = document.getElementById('slug')?.value || 'post-title';
            const metaTitle = document.getElementById('meta-title')?.value || title;
            const metaDesc = document.getElementById('meta-desc')?.value || '';

            document.getElementById('seo-title-preview').textContent = metaTitle + ' — <?= addslashes(MATER_SITE_NAME) ?>';
            document.getElementById('seo-url-preview').textContent = 'https://tusitio.com/post/' + slug;

            const descEl = document.getElementById('seo-desc-preview');
            descEl.textContent = metaDesc || 'Resumen del post que aparecerá en los resultados de búsqueda.';
        }
    };
}

// Visibilidad: mostrar/ocultar campo de contraseña
function togglePasswordField(val) {
    const wrapper = document.getElementById('password-field-wrapper');
    if (wrapper) {
        wrapper.style.display = val === 'password' ? 'block' : 'none';
    }
}

// Native JS: escuchar cambio del input file
(function() {
    const fileInput = document.getElementById('image-input');
    if (!fileInput) return;
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const area = document.getElementById('image-preview-area');
            if (area) {
                area.style.display = 'block';
                area.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width:200px;max-height:120px">';
            }
        };
        reader.readAsDataURL(file);
    });
})();
</script>
