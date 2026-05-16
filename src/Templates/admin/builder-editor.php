<!DOCTYPE html>
<html lang="es" class="theme-light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($meta['title'] ?? 'Editor de componentes') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css?v=3">
    <link rel="stylesheet" href="/assets/css/builder.css?v=1">
    <?= $jsDataScript ?>
    <style>
        /* ─── Reset para el builder layout (sin layout admin wrapper) ─── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #1a1a1a;
            background: #f5f5f5;
        }
    </style>
</head>
<body>

    <!-- ─── Toolbar ─── -->
    <header class="builder-toolbar">
        <div class="builder-toolbar-left">
            <a href="/admin/<?= $type ?>s" class="builder-back-btn"><?= svg_icon('chevron-left') ?> Volver</a>
            <span class="builder-title"><?= $escape($entry['title']) ?></span>
            <span class="builder-badge"><?= $type === 'page' ? 'Página' : 'Post' ?></span>
        </div>
        <div class="builder-toolbar-right">
            <button type="button" class="builder-btn builder-btn-outline" id="btn-preview"><?= svg_icon('globe-alt') ?> Vista previa</button>
            <button type="button" class="builder-btn builder-btn-primary" id="btn-save"><?= svg_icon('check') ?> Guardar</button>
        </div>
    </header>

    <!-- ─── Editor body ─── -->
    <div class="builder-body">

        <!-- ─── Panel izquierdo: Bloques disponibles ─── -->
        <aside class="builder-panel-left" id="panel-blocks">
            <div class="builder-panel-header">
                <h3>Bloques</h3>
            </div>
            <div class="builder-blocks-list" id="blocks-list">
                <!-- Se rellena desde JS -->
            </div>
        </aside>

        <!-- ─── Canvas central ─── -->
        <main class="builder-canvas" id="builder-canvas">
            <div class="builder-dropzone" id="components-sortable">
                <!-- Los componentes se renderizan desde JS -->
                <div class="builder-empty-state" id="empty-state">
                    <p>Añade bloques desde el panel izquierdo</p>
                    <p class="builder-hint">Arrastra un bloque o haz clic para añadirlo</p>
                </div>
            </div>
        </main>

        <!-- ─── Panel derecho: Configuración ─── -->
        <aside class="builder-panel-right" id="panel-settings">
            <div class="builder-panel-header">
                <h3>Configuración</h3>
            </div>
            <div class="builder-settings-content" id="settings-content">
                <p class="builder-settings-empty">Selecciona un bloque en el canvas para configurarlo</p>
            </div>
        </aside>

    </div>

    <!-- ─── Templates de formularios (renderizados por PHP) ─── -->
    <div id="form-templates" style="display:none;">
        <?php foreach ($availableComponents as $comp): ?>
        <div class="form-template" data-type="<?= $comp['type'] ?>">
            <div class="builder-settings-form" data-type="<?= $comp['type'] ?>">
                <?php
                $compObj = $this->getComponentManager()->getComponent($comp['type']);
                if ($compObj) echo $compObj->renderForm($comp['defaultSettings']);
                ?>
            </div>
            <div class="builder-preview-template" data-type="<?= $comp['type'] ?>">
                <?php
                if ($compObj) {
                    echo '<div class="component-preview-inner">';
                    echo $compObj->render($comp['defaultSettings']);
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ─── Notificaciones ─── -->
    <div id="builder-toast" class="builder-toast" style="display:none;"></div>

    <!-- ─── SortableJS ─── -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

    <!-- ─── Builder JS ─── -->
    <script>
    (function() {
        'use strict';

        // ─── Estado ───
        const state = {
            entryId: <?= json_encode((int) ($entry['id'] ?? 0)) ?>,
            entryType: <?= json_encode($type) ?>,
            components: <?= json_encode($components, JSON_UNESCAPED_UNICODE) ?>,
            availableComponents: <?= json_encode($availableComponents, JSON_UNESCAPED_UNICODE) ?>,
            selectedIndex: -1,
            nextId: Date.now(),
        };

        const formTemplates = {};
        const previewTemplates = {};
        document.querySelectorAll('.form-template').forEach(function(el) {
            const type = el.dataset.type;
            formTemplates[type] = el.querySelector('.builder-settings-form').innerHTML;
            const preview = el.querySelector('.builder-preview-template');
            if (preview) previewTemplates[type] = preview.innerHTML;
        });

        // ─── DOM refs ───
        const canvas = document.getElementById('components-sortable');
        const emptyState = document.getElementById('empty-state');
        const blocksList = document.getElementById('blocks-list');
        const settingsContent = document.getElementById('settings-content');
        const toast = document.getElementById('builder-toast');
        const btnSave = document.getElementById('btn-save');
        const btnPreview = document.getElementById('btn-preview');

        // ─── Renderizar bloques disponibles ───
        function renderBlocks() {
            blocksList.innerHTML = '';
            state.availableComponents.forEach(function(comp) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'builder-block-btn';
                btn.dataset.type = comp.type;
                btn.innerHTML = '<span class="builder-block-icon">' + getIconHtml(comp.icon) + '</span>' +
                                '<span class="builder-block-label">' + comp.label + '</span>';
                btn.addEventListener('click', function() {
                    addComponent(comp.type);
                });
                blocksList.appendChild(btn);
            });
        }

        // ─── Icon helper ───
        function getIconHtml(iconName) {
            return '<svg class="icon" width="1em" height="1em" fill="currentColor"><use href="/assets/icons/cil-' + iconName + '.svg#icon"/></svg>';
        }

        // ─── Añadir componente ───
        function addComponent(type) {
            const comp = state.availableComponents.find(function(c) { return c.type === type; });
            if (!comp) return;

            const newComp = {
                id: 'comp_' + (state.nextId++),
                type: type,
                settings: JSON.parse(JSON.stringify(comp.defaultSettings)),
            };
            state.components.push(newComp);
            renderCanvas();
            selectComponent(state.components.length - 1);
            showToast('Bloque añadido: ' + comp.label);
        }

        // ─── Renderizar canvas ───
        function renderCanvas() {
            if (state.components.length === 0) {
                canvas.innerHTML = '';
                canvas.appendChild(emptyState);
                emptyState.style.display = 'block';
                return;
            }
            emptyState.style.display = 'none';

            let html = '';
            state.components.forEach(function(comp, index) {
                const avail = state.availableComponents.find(function(c) { return c.type === comp.type; });
                const label = avail ? avail.label : comp.type;
                const selected = index === state.selectedIndex ? ' component-card--selected' : '';
                const settingsHtml = index === state.selectedIndex
                    ? getSettingsForm(comp.type, comp.settings)
                    : '';

                html += '<div class="component-card' + selected + '" data-index="' + index + '" data-id="' + comp.id + '">';
                html += '  <div class="component-card-header">';
                html += '    <span class="component-drag-handle">⠿</span>';
                html += '    <span class="component-card-type">' + label + '</span>';
                html += '    <div class="component-card-actions">';
                html += '      <button type="button" class="component-btn component-btn-duplicate" data-index="' + index + '" title="Duplicar">⧉</button>';
                html += '      <button type="button" class="component-btn component-btn-delete" data-index="' + index + '" title="Eliminar">✕</button>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div class="component-card-settings" data-index="' + index + '">';
                html += settingsHtml;
                html += '  </div>';
                html += '</div>';
            });

            canvas.innerHTML = html;

            // Event listeners
            document.querySelectorAll('.component-card').forEach(function(card) {
                card.addEventListener('click', function(e) {
                    // Don't select if clicking a button or input
                    if (e.target.closest('.component-btn') || e.target.closest('input') ||
                        e.target.closest('textarea') || e.target.closest('select') ||
                        e.target.closest('button')) return;
                    const index = parseInt(card.dataset.index);
                    selectComponent(index);
                });
            });

            document.querySelectorAll('.component-btn-delete').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = parseInt(btn.dataset.index);
                    deleteComponent(index);
                });
            });

            document.querySelectorAll('.component-btn-duplicate').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = parseInt(btn.dataset.index);
                    duplicateComponent(index);
                });
            });

            // Settings form: listen to changes
            document.querySelectorAll('.component-card-settings input, .component-card-settings textarea, .component-card-settings select').forEach(function(input) {
                input.addEventListener('change', function() {
                    updateComponentSettings(this);
                });
                input.addEventListener('input', function() {
                    if (this.tagName !== 'SELECT') {
                        updateComponentSettings(this);
                    }
                });
            });

            // Init Sortable
            if (window.Sortable) {
                Sortable.create(canvas, {
                    handle: '.component-drag-handle',
                    animation: 150,
                    onEnd: function(evt) {
                        const item = state.components.splice(evt.oldIndex, 1)[0];
                        state.components.splice(evt.newIndex, 0, item);
                        if (state.selectedIndex === evt.oldIndex) {
                            state.selectedIndex = evt.newIndex;
                        }
                        renderCanvas();
                    },
                });
            }
        }

        // ─── Obtener formulario de settings (rellenado) ───
        function getSettingsForm(type, settings) {
            const tmpl = formTemplates[type];
            if (!tmpl) return '<p>Formulario no disponible</p>';

            // Crear un DOM temporal y rellenar valores
            const div = document.createElement('div');
            div.innerHTML = tmpl;

            // Rellenar inputs
            Object.keys(settings).forEach(function(key) {
                const input = div.querySelector('[name="settings[' + key + ']"]');
                if (input) {
                    if (input.tagName === 'INPUT' && input.type === 'checkbox') {
                        input.checked = !!settings[key];
                    } else if (input.tagName === 'INPUT' && input.type === 'range') {
                        input.value = settings[key] || 0;
                        const valDisplay = div.querySelector('.builder-range-value');
                        if (valDisplay) valDisplay.textContent = (settings[key] || 0) + '%';
                    } else {
                        input.value = settings[key] || '';
                    }
                }
                const textarea = div.querySelector('[name="settings[' + key + ']"]');
                if (textarea && textarea.tagName === 'TEXTAREA') {
                    textarea.value = settings[key] || '';
                }
                const select = div.querySelector('[name="settings[' + key + ']"]');
                if (select && select.tagName === 'SELECT') {
                    select.value = settings[key] || '';
                }
            });

            // Range listeners
            div.querySelectorAll('.builder-range').forEach(function(range) {
                range.addEventListener('input', function() {
                    const val = this.parentElement.querySelector('.builder-range-value');
                    if (val) val.textContent = this.value + '%';
                });
            });

            return div.innerHTML;
        }

        // ─── Actualizar settings desde input ───
        function updateComponentSettings(input) {
            if (state.selectedIndex < 0) return;
            const card = input.closest('.component-card-settings');
            if (!card) return;
            const index = parseInt(card.dataset.index);
            if (isNaN(index) || index >= state.components.length) return;

            const name = input.getAttribute('name');
            const match = name && name.match(/^settings\[(\w+)\]$/);
            if (!match) return;

            const key = match[1];
            let val = input.value;
            if (input.type === 'checkbox') val = input.checked;
            if (input.type === 'number') val = parseFloat(val) || 0;

            state.components[index].settings[key] = val;
        }

        // ─── Seleccionar componente ───
        function selectComponent(index) {
            state.selectedIndex = index;
            renderCanvas();

            // Scroll al componente seleccionado
            const card = document.querySelector('.component-card[data-index="' + index + '"]');
            if (card) card.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Actualizar panel de configuración
            if (index >= 0 && index < state.components.length) {
                const comp = state.components[index];
                const avail = state.availableComponents.find(function(c) { return c.type === comp.type; });
                const label = avail ? avail.label : comp.type;
                const formHtml = getSettingsForm(comp.type, comp.settings);

                settingsContent.innerHTML = '';
                const header = document.createElement('div');
                header.className = 'settings-active-header';
                header.innerHTML = '<h4>' + label + '</h4><button type="button" class="component-btn component-btn-delete" id="settings-delete">Eliminar bloque</button>';

                const form = document.createElement('div');
                form.className = 'settings-active-form';
                form.innerHTML = formHtml;

                settingsContent.appendChild(header);
                settingsContent.appendChild(form);

                // Listeners
                form.querySelectorAll('input, textarea, select').forEach(function(input) {
                    input.addEventListener('change', function() {
                        updateComponentSettings(this);
                    });
                    input.addEventListener('input', function() {
                        if (this.tagName !== 'SELECT') {
                            updateComponentSettings(this);
                        }
                    });
                });

                form.querySelectorAll('.builder-range').forEach(function(range) {
                    range.addEventListener('input', function() {
                        const val = this.parentElement.querySelector('.builder-range-value');
                        if (val) val.textContent = this.value + '%';
                        updateComponentSettings(this);
                    });
                });

                document.getElementById('settings-delete').addEventListener('click', function() {
                    deleteComponent(index);
                });
            }
        }

        // ─── Eliminar componente ───
        function deleteComponent(index) {
            if (index < 0 || index >= state.components.length) return;
            state.components.splice(index, 1);
            if (state.selectedIndex === index) {
                state.selectedIndex = -1;
            } else if (state.selectedIndex > index) {
                state.selectedIndex--;
            }
            renderCanvas();
            if (state.selectedIndex < 0) {
                settingsContent.innerHTML = '<p class="builder-settings-empty">Selecciona un bloque en el canvas para configurarlo</p>';
            }
            showToast('Bloque eliminado');
        }

        // ─── Duplicar componente ───
        function duplicateComponent(index) {
            if (index < 0 || index >= state.components.length) return;
            const copy = JSON.parse(JSON.stringify(state.components[index]));
            copy.id = 'comp_' + (state.nextId++);
            state.components.splice(index + 1, 0, copy);
            renderCanvas();
            selectComponent(index + 1);
            showToast('Bloque duplicado');
        }

        // ─── Guardar ───
        function saveComponents() {
            btnSave.disabled = true;
            btnSave.textContent = 'Guardando...';

            const data = new URLSearchParams();
            data.append('type', state.entryType);
            data.append('id', String(state.entryId));
            data.append('components', JSON.stringify(state.components));
            data.append('_csrf_token', '<?= $csrfField ?>'.match(/value="([^"]+)"/)?.[1] || '');

            fetch('/admin/componentes/guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data.toString(),
            })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.success) {
                    showToast('✅ Componentes guardados correctamente');
                } else {
                    showToast('❌ ' + (result.error || 'Error al guardar'));
                }
            })
            .catch(function(err) {
                showToast('❌ Error de conexión: ' + err.message);
            })
            .finally(function() {
                btnSave.disabled = false;
                btnSave.innerHTML = '<?= svg_icon("check") ?> Guardar';
            });
        }

        // ─── Toast ───
        function showToast(msg) {
            toast.textContent = msg;
            toast.style.display = 'block';
            toast.style.opacity = '1';
            setTimeout(function() {
                toast.style.opacity = '0';
                setTimeout(function() { toast.style.display = 'none'; }, 300);
            }, 2500);
        }

        // ─── Vista previa ───
        function openPreview() {
            const w = window.open('', '_blank');
            if (!w) return;
            const html = buildPreviewHtml();
            w.document.write(html);
            w.document.close();
        }

        function buildPreviewHtml() {
            const theme = '<?= $entry['template'] ?? 'light' ?>';
            const siteName = '<?= MATER_SITE_NAME ?>';
            const title = '<?= $escape($entry['title']) ?>';

            let compsHtml = '';
            state.components.forEach(function(comp) {
                const tmpl = previewTemplates[comp.type];
                if (tmpl) {
                    // Reemplazar placeholders con settings reales
                    let html = tmpl;
                    Object.keys(comp.settings).forEach(function(key) {
                        const val = String(comp.settings[key] || '');
                        html = html.split('{{' + key + '}}').join(val);
                    });
                    compsHtml += html;
                }
            });

            return '<!DOCTYPE html><html><head><title>' + title + '</title>' +
                '<link rel="stylesheet" href="/assets/css/tailwind.css">' +
                '<link rel="stylesheet" href="/assets/css/base.css">' +
                '<link rel="stylesheet" href="/assets/css/builder.css?v=1">' +
                '</head><body class="theme-' + theme + '"><div class="preview-container">' +
                compsHtml + '</div></body></html>';
        }

        // ─── Init ───
        renderBlocks();
        renderCanvas();

        // Eventos
        btnSave.addEventListener('click', saveComponents);
        btnPreview.addEventListener('click', openPreview);

        // Tecla Escape para desseleccionar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                state.selectedIndex = -1;
                renderCanvas();
                settingsContent.innerHTML = '<p class="builder-settings-empty">Selecciona un bloque en el canvas para configurarlo</p>';
            }
        });

    })();
    </script>
</body>
</html>
