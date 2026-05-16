<?php declare(strict_types=1);

/**
 * Theme Settings — Editor de apariencia
 *
 * Controla logo, items del header, items del footer y texto del footer.
 * Usa Alpine.js para pestañas, listas dinámicas y drag & drop.
 *
 * @var array $themeSettings Datos actuales del theme
 */

$logo         = $themeSettings['logo'] ?? [];
$headerItems  = $themeSettings['headerItems'] ?? [];
$footerItems  = $themeSettings['footerItems'] ?? [];
$footer       = $themeSettings['footer'] ?? [];

// Plataformas sociales
$socialPlatforms = [
    'instagram' => 'Instagram',
    'twitter'   => 'Twitter / X',
    'facebook'  => 'Facebook',
    'youtube'   => 'YouTube',
    'tiktok'    => 'TikTok',
    'github'    => 'GitHub',
    'linkedin'  => 'LinkedIn',
    'pinterest' => 'Pinterest',
    'bluesky'   => 'Bluesky',
    'mastodon'  => 'Mastodon',
    'custom'    => '— Otro —',
];

$hItemsJson = json_encode($headerItems, JSON_UNESCAPED_UNICODE);
$fItemsJson = json_encode($footerItems, JSON_UNESCAPED_UNICODE);
?>
<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Apariencia</h1>
        <p style="color:#888;font-size:14px;margin:4px 0 0">Personaliza el logo, los elementos del header y del footer.</p>
    </div>

    <form method="POST" action="/admin/apariencia" class="admin-form" enctype="multipart/form-data"
          x-data="themeEditor()"
          x-init="init()">
        <?= $csrfField ?>

        <!-- ════════════════════════════════════════════════
             LOGO (compartido)
             ════════════════════════════════════════════════ -->
        <div class="settings-section">
            <p class="settings-section-title">Logo</p>
            <p style="font-size:12px;color:#888;margin:-8px 0 16px">Se muestra tanto en el header como en el footer.</p>

            <div class="settings-field">
                <label>Tipo de logo</label>
                <div style="display:flex;gap:16px;margin-top:4px">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-family:'Geist',sans-serif;font-size:14px;font-weight:600;text-transform:uppercase">
                        <input type="radio" name="logo_type" value="text"
                               x-model="logoType"
                               @change="switchLogoMode('text')">
                        Texto
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-family:'Geist',sans-serif;font-size:14px;font-weight:600;text-transform:uppercase">
                        <input type="radio" name="logo_type" value="image"
                               x-model="logoType"
                               @change="switchLogoMode('image')">
                        Imagen
                    </label>
                </div>
            </div>

            <div class="settings-field" x-show="logoType === 'text'" x-cloak>
                <label for="logo_text">Texto del logo</label>
                <input type="text" id="logo_text" name="logo_text"
                       class="brutalist-input"
                       value="<?= $escape($logo['text'] ?? 'MATER NATURA') ?>"
                       placeholder="MATER NATURA"
                       style="max-width:400px">
            </div>

            <div class="settings-field" x-show="logoType === 'image'" x-cloak>
                <label for="logo_image">Imagen del logo</label>
                <p style="font-size:12px;color:#888;margin:2px 0 6px">Recomendado: WebP, fondo transparente, altura ~60px.</p>

                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <label class="brutalist-card-dashed" style="width:200px;height:100px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;text-align:center;position:relative"
                           @dragover.prevent @drop.prevent="handleLogoDrop($event)">
                        <?= svg_icon('cloud-upload') ?>
                        <span style="font-size:12px;font-family:'Geist',sans-serif;text-transform:uppercase;margin-top:6px;color:#888">Subir logo</span>
                        <input type="file" id="logo_image" name="logo_image"
                               accept="image/jpeg,image/png,image/webp"
                               style="position:absolute;inset:0;opacity:0;cursor:pointer"
                               @change="previewLogo($event)">
                    </label>

                    <template x-if="logoPreviewURL">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                            <img :src="logoPreviewURL" alt="Preview"
                                 style="max-height:60px;max-width:200px;border:1px solid #eee;padding:8px;background:#fafafa">
                            <button type="button" class="btn-sm btn-danger" @click="removeLogo()" style="font-size:11px">Eliminar</button>
                        </div>
                    </template>

                    <template x-if="!logoPreviewURL && currentLogoURL">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                            <p style="font-size:11px;color:#888;margin:0 0 2px;text-transform:uppercase;letter-spacing:0.05em">Actual</p>
                            <img :src="currentLogoURL" alt="Logo actual"
                                 style="max-height:60px;max-width:200px;border:1px solid #eee;padding:8px;background:#fafafa">
                            <button type="button" class="btn-sm btn-danger" @click="removeCurrentLogo()" style="font-size:11px">Eliminar</button>
                        </div>
                    </template>

                    <template x-if="!logoPreviewURL && !currentLogoURL">
                        <p style="font-size:13px;color:#aaa;font-style:italic">No hay logo de imagen subido.</p>
                    </template>
                </div>

                <div class="settings-field" style="margin-top:12px">
                    <label for="logo_alt">Texto alternativo (alt)</label>
                    <input type="text" id="logo_alt" name="logo_alt"
                           class="brutalist-input"
                           value="<?= $escape($logo['alt'] ?? MATER_SITE_NAME) ?>"
                           placeholder="<?= $escape(MATER_SITE_NAME) ?>"
                           style="max-width:400px"
                           x-bind:disabled="logoType !== 'image'">
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════
             PESTAÑAS: HEADER / FOOTER
             ════════════════════════════════════════════════ -->
        <div class="theme-tabs">
            <button type="button"
                    class="theme-tab-btn"
                    :class="{ active: tab === 'header' }"
                    @click="tab = 'header'">
                Header
            </button>
            <button type="button"
                    class="theme-tab-btn"
                    :class="{ active: tab === 'footer' }"
                    @click="tab = 'footer'">
                Footer
            </button>
        </div>

        <!-- ════════════════════════════════════════════════
             TAB: HEADER
             ════════════════════════════════════════════════ -->
        <div class="settings-section theme-tab-panel" x-show="tab === 'header'" x-cloak>
            <p class="settings-section-title">Elementos del Header</p>
            <p style="font-size:12px;color:#888;margin:-8px 0 16px">Cada elemento puede ser un enlace de navegación o un icono de red social. Arrastra para reordenar.</p>

            <template x-for="(item, index) in headerItems" :key="item._key">
                <div class="theme-item">
                    <!-- Drag handle -->
                    <span class="theme-item-drag"
                          @mousedown="startDrag($event, index, 'header')">⠿</span>

                    <div class="theme-item-fields">
                        <!-- Type selector -->
                        <div class="settings-field" style="min-width:100px">
                            <label>Tipo</label>
                            <select class="brutalist-input"
                                    :name="'header_type[' + index + ']'"
                                    x-model="item.type">
                                <option value="nav">Enlace</option>
                                <option value="social">Red social</option>
                            </select>
                        </div>

                        <!-- Nav fields -->
                        <div class="settings-field" style="flex:1;min-width:100px" x-show="item.type === 'nav'">
                            <label>Etiqueta</label>
                            <input type="text" class="brutalist-input"
                                   :name="'header_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="día">
                        </div>
                        <div class="settings-field" style="flex:1.5;min-width:140px" x-show="item.type === 'nav'">
                            <label>URL</label>
                            <input type="text" class="brutalist-input"
                                   :name="'header_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="/post?tag=dia">
                        </div>
                        <div class="settings-field" style="min-width:80px" x-show="item.type === 'nav'">
                            <label>Abrir en</label>
                            <select class="brutalist-input"
                                    :name="'header_target[' + index + ']'"
                                    x-model="item.target">
                                <option value="_self">Misma pestaña</option>
                                <option value="_blank">Nueva pestaña</option>
                            </select>
                        </div>

                        <!-- Social fields -->
                        <div class="settings-field" style="min-width:120px" x-show="item.type === 'social'">
                            <label>Plataforma</label>
                            <select class="brutalist-input"
                                    :name="'header_platform[' + index + ']'"
                                    x-model="item.platform">
<?php foreach ($socialPlatforms as $pValue => $pLabel): ?>
                                <option value="<?= $pValue ?>"><?= $pLabel ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="settings-field" style="flex:2;min-width:160px" x-show="item.type === 'social'">
                            <label>URL</label>
                            <input type="text" class="brutalist-input"
                                   :name="'header_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="https://instagram.com/mater_natura/">
                        </div>
                        <div class="settings-field" style="flex:1;min-width:100px" x-show="item.type === 'social'">
                            <label>Etiqueta</label>
                            <input type="text" class="brutalist-input"
                                   :name="'header_social_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="Instagram">
                        </div>
                    </div>

                    <!-- Hidden keep -->
                    <input type="hidden" :name="'header_keep[' + index + ']'" value="1">

                    <button type="button" class="btn-sm btn-danger theme-item-remove" @click="removeItem('header', index)"
                            x-show="headerItems.length > 1">✕</button>
                </div>
            </template>

            <button type="button" class="brutalist-btn-secondary" @click="addItem('header')" style="margin-top:4px;font-size:13px">
                + Añadir elemento al header
            </button>
        </div>

        <!-- ════════════════════════════════════════════════
             TAB: FOOTER
             ════════════════════════════════════════════════ -->
        <div class="settings-section theme-tab-panel" x-show="tab === 'footer'" x-cloak>
            <p class="settings-section-title">Elementos del Footer</p>
            <p style="font-size:12px;color:#888;margin:-8px 0 16px">Cada elemento puede ser un enlace de navegación o un icono de red social. Arrastra para reordenar.</p>

            <template x-for="(item, index) in footerItems" :key="item._key">
                <div class="theme-item">
                    <!-- Drag handle -->
                    <span class="theme-item-drag"
                          @mousedown="startDrag($event, index, 'footer')">⠿</span>

                    <div class="theme-item-fields">
                        <!-- Type selector -->
                        <div class="settings-field" style="min-width:100px">
                            <label>Tipo</label>
                            <select class="brutalist-input"
                                    :name="'footer_type[' + index + ']'"
                                    x-model="item.type">
                                <option value="nav">Enlace</option>
                                <option value="social">Red social</option>
                            </select>
                        </div>

                        <!-- Nav fields -->
                        <div class="settings-field" style="flex:1;min-width:100px" x-show="item.type === 'nav'">
                            <label>Etiqueta</label>
                            <input type="text" class="brutalist-input"
                                   :name="'footer_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="aviso legal">
                        </div>
                        <div class="settings-field" style="flex:1.5;min-width:140px" x-show="item.type === 'nav'">
                            <label>URL</label>
                            <input type="text" class="brutalist-input"
                                   :name="'footer_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="/aviso-legal">
                        </div>
                        <div class="settings-field" style="min-width:80px" x-show="item.type === 'nav'">
                            <label>Abrir en</label>
                            <select class="brutalist-input"
                                    :name="'footer_target[' + index + ']'"
                                    x-model="item.target">
                                <option value="_self">Misma pestaña</option>
                                <option value="_blank">Nueva pestaña</option>
                            </select>
                        </div>

                        <!-- Social fields -->
                        <div class="settings-field" style="min-width:120px" x-show="item.type === 'social'">
                            <label>Plataforma</label>
                            <select class="brutalist-input"
                                    :name="'footer_platform[' + index + ']'"
                                    x-model="item.platform">
<?php foreach ($socialPlatforms as $pValue => $pLabel): ?>
                                <option value="<?= $pValue ?>"><?= $pLabel ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="settings-field" style="flex:2;min-width:160px" x-show="item.type === 'social'">
                            <label>URL</label>
                            <input type="text" class="brutalist-input"
                                   :name="'footer_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="https://instagram.com/mater_natura/">
                        </div>
                        <div class="settings-field" style="flex:1;min-width:100px" x-show="item.type === 'social'">
                            <label>Etiqueta</label>
                            <input type="text" class="brutalist-input"
                                   :name="'footer_social_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="Instagram">
                        </div>
                    </div>

                    <input type="hidden" :name="'footer_keep[' + index + ']'" value="1">

                    <button type="button" class="btn-sm btn-danger theme-item-remove" @click="removeItem('footer', index)"
                            x-show="footerItems.length > 1">✕</button>
                </div>
            </template>

            <button type="button" class="brutalist-btn-secondary" @click="addItem('footer')" style="margin-top:4px;font-size:13px">
                + Añadir elemento al footer
            </button>

            <!-- Footer text -->
            <div style="margin-top:24px;padding-top:24px;border-top:1px solid #e0e0e0">
                <p class="settings-section-title">Texto del footer</p>
                <div class="settings-field">
                    <label for="footer_text">Texto opcional junto al logo</label>
                    <input type="text" id="footer_text" name="footer_text"
                           class="brutalist-input"
                           value="<?= $escape($footer['text'] ?? '') ?>"
                           placeholder="MATER-NATURA"
                           style="max-width:500px">
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════
             ACCIONES
             ════════════════════════════════════════════════ -->
        <div class="settings-section" style="border-bottom:none;background:#f5f5f5">
            <div class="form-actions" style="display:flex;gap:12px;align-items:center">
                <button type="submit" class="brutalist-btn-primary">Guardar cambios</button>
                <a href="/" target="_blank" class="brutalist-btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                    Ver sitio →
                </a>
            </div>
        </div>
    </form>
</div>

<script>
    function themeEditor() {
        return {
            // Tabs
            tab: 'header',

            // Logo
            logoType: '<?= $escape($logo['type'] ?? 'text') ?>',
            logoPreviewURL: null,
            currentLogoURL: <?php
                if (($logo['type'] ?? '') === 'image' && !empty($logo['path'])) {
                    echo json_encode('/media/' . ltrim($logo['path'], '/'), JSON_UNESCAPED_SLASHES);
                } else {
                    echo 'null';
                }
            ?>,

            // Items
            headerItems: <?= $hItemsJson ?: '[]' ?>,
            footerItems: <?= $fItemsJson ?: '[]' ?>,

            // Drag state
            dragIndex: null,
            dragList: null,
            dragClone: null,

            init() {
                this.headerItems = this.headerItems.map((item, i) => ({
                    ...item,
                    _key: 'h_' + Date.now() + '_' + i,
                    type: item.type || 'nav',
                    target: item.target || '_self',
                    platform: item.platform || 'instagram'
                }));
                this.footerItems = this.footerItems.map((item, i) => ({
                    ...item,
                    _key: 'f_' + Date.now() + '_' + i,
                    type: item.type || 'nav',
                    target: item.target || '_self',
                    platform: item.platform || 'instagram'
                }));

                if (this.headerItems.length === 0) this.addItem('header');
                if (this.footerItems.length === 0) this.addItem('footer');
            },

            // ─── Logo ───
            switchLogoMode(mode) {
                this.logoType = mode;
                if (mode === 'text') this.logoPreviewURL = null;
            },

            previewLogo(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => { this.logoPreviewURL = e.target.result; };
                reader.readAsDataURL(file);
            },

            handleLogoDrop(event) {
                const file = event.dataTransfer.files[0];
                if (!file || !file.type.startsWith('image/')) return;
                const input = document.getElementById('logo_image');
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
                this.previewLogo({target: input});
            },

            removeLogo() {
                this.logoPreviewURL = null;
                document.getElementById('logo_image').value = '';
            },

            removeCurrentLogo() {
                this.currentLogoURL = null;
                this.logoType = 'text';
                document.querySelector('input[name="logo_type"][value="text"]').checked = true;
            },

            // ─── Items ───
            getItems(list) {
                return list === 'header' ? this.headerItems : this.footerItems;
            },

            addItem(list) {
                const items = this.getItems(list);
                const prefix = list === 'header' ? 'h' : 'f';
                items.push({
                    _key: prefix + '_new_' + Date.now(),
                    id: 'item_' + Date.now(),
                    type: 'nav',
                    label: '',
                    url: '',
                    target: '_self',
                    platform: 'instagram',
                    label: ''
                });
            },

            removeItem(list, index) {
                const items = this.getItems(list);
                items.splice(index, 1);
            },

            // ─── Drag & Drop ───
            startDrag(event, index, listName) {
                this.dragIndex = index;
                this.dragList = listName;
                const target = event.target.closest('.theme-item');
                if (!target) return;

                const clone = target.cloneNode(true);
                clone.style.position = 'absolute';
                clone.style.pointerEvents = 'none';
                clone.style.opacity = '0.6';
                clone.style.zIndex = '9999';
                clone.style.width = target.offsetWidth + 'px';
                document.body.appendChild(clone);
                this.dragClone = clone;

                const rect = target.getBoundingClientRect();
                const offsetY = event.clientY - rect.top;

                const onMove = (e) => {
                    if (!this.dragClone) return;
                    const clientY = e.clientY || 0;
                    this.dragClone.style.left = rect.left + 'px';
                    this.dragClone.style.top = (clientY - offsetY) + 'px';

                    const items = document.querySelectorAll('.theme-item');
                    let dropIndex = -1;
                    items.forEach((el, i) => {
                        const r = el.getBoundingClientRect();
                        const mid = r.top + r.height / 2;
                        if (clientY >= r.top && clientY <= r.bottom) dropIndex = i;
                    });

                    if (dropIndex >= 0 && dropIndex !== this.dragIndex && this.dragIndex !== null) {
                        const list = this.dragList === 'header' ? this.headerItems : this.footerItems;
                        const [moved] = list.splice(this.dragIndex, 1);
                        list.splice(dropIndex, 0, moved);
                        this.dragIndex = dropIndex;
                    }
                };

                const onUp = () => {
                    if (this.dragClone) document.body.removeChild(this.dragClone);
                    this.dragClone = null;
                    this.dragIndex = null;
                    this.dragList = null;
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            }
        };
    }
</script>
