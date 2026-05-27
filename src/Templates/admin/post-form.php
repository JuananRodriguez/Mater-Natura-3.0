<?php declare(strict_types=1);
// Helper: resolver valor del campo. Prioridad: formData (post-error) > post (BD) > default
$val = function(string $field, string $default = '') use ($post, $formData): string {
    if ($formData !== null && isset($formData[$field])) {
        return $formData[$field];
    }
    if ($post !== null) {
        $camelMap = [
            'meta_title'          => 'metaTitle',
            'meta_description'    => 'metaDescription',
            'visibility_password' => 'visibilityPassword',
            'image_url'           => 'imageUrl',
        ];
        $prop = $camelMap[$field] ?? $field;
        $value = $post->$prop ?? $default;
        return is_string($value) ? $value : (string) $value;
    }
    return $default;
};
// Ídem pero raw (sin escape, para JS, etc.)
$valRaw = function(string $field, string $default = '') use ($post, $formData): string {
    if ($formData !== null && isset($formData[$field])) {
        return $formData[$field];
    }
    if ($post !== null) {
        $camelMap = [
            'meta_title'          => 'metaTitle',
            'meta_description'    => 'metaDescription',
            'visibility_password' => 'visibilityPassword',
            'image_url'           => 'imageUrl',
        ];
        $prop = $camelMap[$field] ?? $field;
        $value = $post->$prop ?? $default;
        return is_string($value) ? $value : (string) $value;
    }
    return $default;
};
?>
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

    <form method="POST" action="/admin/posts/editar" enctype="multipart/form-data" id="post-form" novalidate>
        <?= $csrfField ?>

        <?php if ($post || !empty($formData['id'])): ?>
            <input type="hidden" name="id" value="<?= (int)($val('id')) ?>">
        <?php endif; ?>
        <input type="hidden" name="status" id="hidden-status" value="<?= $escape($val('status', 'draft')) ?>">

        <?php
        $initialError = $_SESSION['admin_error'] ?? null;
        unset($_SESSION['admin_error']);
        ?>
        <div class="alert alert-error" id="form-error-alert"
             style="margin:0 32px 16px;display:<?= $initialError ? 'block' : 'none' ?>">
            <?= $initialError ? $escape($initialError) : '' ?>
        </div>

        <div class="post-editor-body">
            <div class="editor-canvas">
                <!-- Title Input -->
                <input type="text" id="title" name="title"
                       value="<?= $escape($val('title')) ?>"
                       @input.debounce="generateSlug()"
                       @input="updateSEOPreview()"
                       placeholder="Título del post..."
                       class="editor-title" autofocus>

                <!-- Rich Text Editor (Brutalist Card) -->
                <div class="brutalist-card">
                    <textarea id="editor-description" name="description" rows="12"
                              placeholder="Escribe tu contenido aquí…"><?= $escape($val('description')) ?></textarea>
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
                        <option value="dark" <?= $val('template', 'dark') === 'dark' ? 'selected' : '' ?>>Oscura</option>
                        <option value="light" <?= $val('template') === 'light' ? 'selected' : '' ?>>Clara</option>
                    </select>
                </div>
                <div class="settings-field">
                    <label for="sel-visibility">Visibilidad</label>
                    <select id="sel-visibility" name="visibility" class="brutalist-input"
                            onchange="togglePasswordField(this.value)">
                        <option value="public" <?= $val('visibility', 'public') === 'public' ? 'selected' : '' ?>>Público</option>
                        <option value="private" <?= $val('visibility') === 'private' ? 'selected' : '' ?>>Privado</option>
                        <option value="password" <?= $val('visibility') === 'password' ? 'selected' : '' ?>>Protegido con contraseña</option>
                    </select>
                    <div id="password-field-wrapper" style="margin-top:8px;display:<?= $val('visibility') === 'password' ? 'block' : 'none' ?>">
                        <label for="visibility-password" style="font-size:0.75rem;color:#666;display:block;margin-bottom:4px">Contraseña de acceso</label>
                        <input type="password" id="visibility-password" name="visibility_password"
                               class="brutalist-input" placeholder="Contraseña para ver el post"
                               autocomplete="new-password">
                        <?php if ($post && $val('visibility') === 'password'): ?>
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
                               value="<?= $escape($val('slug')) ?>"
                               placeholder="post-title-slug"
                               class="brutalist-input"
                               @input="updateSEOPreview()">
                    </div>
                </div>
            </div>

            <!-- Reference -->
            <div class="settings-section">
                <h3 class="settings-section-title">Referencia</h3>
                <div class="settings-field">
                    <label for="reference">Número de referencia (catálogo)</label>
                    <input type="text" id="reference" name="reference"
                           value="<?= $escape($val('reference')) ?>"
                           placeholder="Ej: 001, A-01, etc."
                           class="brutalist-input">
                </div>
            </div>

            <!-- SEO -->
            <div class="settings-section" style="flex:1;border-bottom:none">
                <h3 class="settings-section-title">Optimización SEO</h3>
                <div class="settings-field">
                    <div class="settings-field-header">
                        <label for="meta-title">Meta Title</label>
                        <span class="meta-counter" id="meta-title-counter"><?= strlen($val('meta_title')) ?>/60</span>
                    </div>
                    <input type="text" id="meta-title" name="meta_title"
                           value="<?= $escape($val('meta_title')) ?>"
                           placeholder="Título optimizado" class="brutalist-input"
                           @input="updateSEOPreview()"
                           oninput="updateMetaCounters()">
                </div>
                <div class="settings-field">
                    <div class="settings-field-header">
                        <label for="meta-desc">Meta Descripción</label>
                        <span class="meta-counter" id="meta-desc-counter"><?= strlen($val('meta_description')) ?>/160</span>
                    </div>
                    <textarea id="meta-desc" name="meta_description"
                              placeholder="Resumen atractivo del post…"
                              class="brutalist-input" style="height:100px;resize:none"
                              @input="updateSEOPreview()"
                              oninput="updateMetaCounters()"><?= $escape($val('meta_description')) ?></textarea>
                </div>
                <!-- SEO Preview -->
                <div class="seo-preview" id="seo-preview">
                    <p style="font-family:'Inter',sans-serif;font-size:12px;color:#5e5e5e;text-transform:uppercase;font-weight:bold;margin:0 0 4px">Vista previa de búsqueda</p>
                    <p class="seo-preview-title" id="seo-title-preview"><?= $escape(($val('meta_title') ?: $val('title')) . ' — ' . MATER_SITE_NAME) ?></p>
                    <p class="seo-preview-url" id="seo-url-preview"><?= rtrim(MATER_BASE_URL, '/') ?>/<?= $escape($val('slug')) ?></p>
                    <p class="seo-preview-desc" id="seo-desc-preview"><?= $escape($val('meta_description') ?: 'Resumen del post que aparecerá en los resultados de búsqueda.') ?></p>
                    <div class="seo-indicators" style="margin-top:8px;font-size:11px;display:flex;gap:12px">
                        <span id="seo-title-indicator" style="color:<?= (strlen($val('meta_title')) > 60) ? '#e53e3e' : ((strlen($val('meta_title')) >= 30) ? '#38a169' : '#dd6b20') ?>">
                            ● Título: <?= (strlen($val('meta_title')) > 60) ? 'demasiado largo' : ((strlen($val('meta_title')) >= 30) ? 'óptimo' : 'corto') ?>
                        </span>
                        <span id="seo-desc-indicator" style="color:<?= (strlen($val('meta_description')) > 160) ? '#e53e3e' : ((strlen($val('meta_description')) >= 120) ? '#38a169' : ((strlen($val('meta_description')) > 0) ? '#dd6b20' : '#718096')) ?>">
                            ● Descripción: <?= (strlen($val('meta_description')) > 160) ? 'demasiado larga' : ((strlen($val('meta_description')) >= 120) ? 'óptima' : ((strlen($val('meta_description')) > 0) ? 'corta' : 'sin definir')) ?>
                        </span>
                    </div>
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
        originalTitle: '<?= addslashes($valRaw('title')) ?>',
        generateSlug() {
            var titleEl = document.getElementById('title');
            if (!titleEl) return;
            const title = titleEl.value;
            const slugEl = document.getElementById('slug');
            if (!slugEl) return;
            slugEl.value = title.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '')
                .trim();
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
            document.getElementById('seo-url-preview').textContent = '<?= addslashes(rtrim(MATER_BASE_URL, '/')) ?>/' + slug;

            const descEl = document.getElementById('seo-desc-preview');
            descEl.textContent = metaDesc || 'Resumen del post que aparecerá en los resultados de búsqueda.';

            // Actualizar indicadores de calidad SEO
            updateMetaCounters();
        }
    };
}

function updateMetaCounters() {
    // Contadores
    const mt = document.getElementById('meta-title');
    const md = document.getElementById('meta-desc');
    if (mt) {
        const len = mt.value.length;
        document.getElementById('meta-title-counter').textContent = len + '/60';
        mt.style.borderColor = len > 60 ? '#e53e3e' : (len >= 30 ? '#38a169' : '#dd6b20');
    }
    if (md) {
        const len = md.value.length;
        document.getElementById('meta-desc-counter').textContent = len + '/160';
        md.style.borderColor = len > 160 ? '#e53e3e' : (len >= 120 ? '#38a169' : (len > 0 ? '#dd6b20' : ''));
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

// ─── Envío AJAX del formulario ───
(function() {
    const form = document.getElementById('post-form');
    if (!form) return;

    const alertBox = document.getElementById('form-error-alert');
    const requiredFields = {
        title:     'El título es obligatorio.',
        reference: 'La referencia es obligatoria.',
    };

    function clearFieldErrors() {
        document.querySelectorAll('.field-error-msg').forEach(function(el) { el.remove(); });
        document.querySelectorAll('.field-error').forEach(function(el) { el.classList.remove('field-error'); });
    }

    function showFieldError(name, msg) {
        var input = document.querySelector('[name="' + name + '"]');
        if (!input) return;
        input.classList.add('field-error');
        var p = document.createElement('p');
        p.className = 'field-error-msg';
        p.textContent = msg;
        p.style.cssText = 'color:#e53e3e;font-size:0.75rem;margin:4px 0 0';
        input.parentNode.appendChild(p);
    }

    function showErrors(errors) {
        clearFieldErrors();
        if (alertBox) {
            alertBox.textContent = errors.join('. ');
            alertBox.style.display = 'block';
        }
        errors.forEach(function(error) {
            if (error.indexOf('título') !== -1)          showFieldError('title', error);
            else if (error.indexOf('referencia') !== -1)  showFieldError('reference', error);
        });
    }

    form.addEventListener('submit', async function(e) {
        try {
            e.preventDefault();

            // 1. Validación en cliente
            clearFieldErrors();
            var clientErrors = [];
            for (var field in requiredFields) {
                if (!requiredFields.hasOwnProperty(field)) continue;
                var input = document.querySelector('[name="' + field + '"]');
                var val = (input && typeof input.value === 'string') ? input.value.trim() : '';
                if (!val) {
                    clientErrors.push(requiredFields[field]);
                }
            }
            if (clientErrors.length > 0) {
                showErrors(clientErrors);
                return;
            }

            // 2. Incluir el botón pulsado en FormData
            var formData = new FormData(form);
            var submitter = e.submitter;
            if (submitter && submitter.name) {
                formData.append(submitter.name, submitter.value);
            }

            // 3. Envío AJAX
            try {
                var actionUrl = form.getAttribute('action') || '/admin/posts/editar';
                var response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                // Si la respuesta no es JSON (redirect, error PHP), mostrarlo
                var contentType = response.headers.get('content-type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    showErrors(['Error inesperado del servidor. La página se recargará.']);
                    setTimeout(function() { window.location.reload(); }, 2000);
                    return;
                }

                var result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    showErrors(result.errors || ['Error desconocido']);
                }
            } catch (err) {
                showErrors(['Error de conexión. Inténtalo de nuevo.']);
            }
        } catch (err) {
            console.error('post-form AJAX error:', err);
            showErrors(['Error inesperado. Recargando…']);
            setTimeout(function() { window.location.reload(); }, 2000);
        }
    });
})();
</script>
