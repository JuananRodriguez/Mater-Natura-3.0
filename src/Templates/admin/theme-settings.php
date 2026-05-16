<?php declare(strict_types=1);

/**
 * Theme Settings — Editor de apariencia
 *
 * Controla logo, navegación, redes sociales y footer.
 * Usa Alpine.js para listas dinámicas y vista previa.
 *
 * @var array $themeSettings Datos actuales del theme
 */

$logo   = $themeSettings['logo'] ?? [];
$nav    = $themeSettings['nav'] ?? [];
$social = $themeSettings['social'] ?? [];
$footer = $themeSettings['footer'] ?? [];

// Plataformas sociales soportadas
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

// Convertir arrays a JSON para Alpine
$navJson    = json_encode($nav, JSON_UNESCAPED_UNICODE);
$socialJson = json_encode($social, JSON_UNESCAPED_UNICODE);
?>
<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Apariencia</h1>
        <p style="color:#888;font-size:14px;margin:4px 0 0">Personaliza el logo, la navegación, redes sociales y footer del sitio.</p>
    </div>

    <form method="POST" action="/admin/apariencia" class="admin-form" enctype="multipart/form-data"
          x-data="themeEditor()"
          x-init="init()">
        <?= $csrfField ?>

        <!-- ════════════════════════════════════════════════
             LOGO
             ════════════════════════════════════════════════ -->
        <div class="settings-section">
            <p class="settings-section-title">Logo</p>

            <!-- Toggle texto / imagen -->
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

            <!-- Logo de texto -->
            <div class="settings-field" x-show="logoType === 'text'" x-cloak>
                <label for="logo_text">Texto del logo</label>
                <p style="font-size:12px;color:#888;margin:2px 0 6px">Se mostrará como texto simple en la cabecera.</p>
                <input type="text" id="logo_text" name="logo_text"
                       class="brutalist-input"
                       value="<?= $escape($logo['text'] ?? 'MATER NATURA') ?>"
                       placeholder="MATER NATURA"
                       style="max-width:400px">
            </div>

            <!-- Logo de imagen -->
            <div class="settings-field" x-show="logoType === 'image'" x-cloak>
                <label for="logo_image">Imagen del logo</label>
                <p style="font-size:12px;color:#888;margin:2px 0 6px">Recomendado: formato WebP, fondo transparente, altura máxima ~60px.</p>

                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                    <!-- Upload -->
                    <label class="brutalist-card-dashed" style="width:200px;height:100px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;text-align:center;position:relative"
                           @dragover.prevent @drop.prevent="handleLogoDrop($event)">
                        <?= svg_icon('cloud-upload') ?>
                        <span style="font-size:12px;font-family:'Geist',sans-serif;text-transform:uppercase;margin-top:6px;color:#888">Subir logo</span>
                        <input type="file" id="logo_image" name="logo_image"
                               accept="image/jpeg,image/png,image/webp"
                               style="position:absolute;inset:0;opacity:0;cursor:pointer"
                               @change="previewLogo($event)">
                    </label>

                    <!-- Preview -->
                    <template x-if="logoPreviewURL">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                            <img :src="logoPreviewURL" alt="Vista previa"
                                 style="max-height:60px;max-width:200px;border:1px solid #eee;padding:8px;background:#fafafa">
                            <button type="button" class="btn-sm btn-danger" @click="removeLogo()"
                                    style="font-size:11px">Eliminar</button>
                        </div>
                    </template>

                    <!-- Logo actual -->
                    <template x-if="!logoPreviewURL && currentLogoURL">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px">
                            <p style="font-size:11px;color:#888;margin:0 0 2px;text-transform:uppercase;letter-spacing:0.05em">Actual</p>
                            <img :src="currentLogoURL" alt="Logo actual"
                                 style="max-height:60px;max-width:200px;border:1px solid #eee;padding:8px;background:#fafafa">
                            <button type="button" class="btn-sm btn-danger" @click="removeCurrentLogo()"
                                    style="font-size:11px">Eliminar logo actual</button>
                        </div>
                    </template>

                    <template x-if="!logoPreviewURL && !currentLogoURL">
                        <p style="font-size:13px;color:#aaa;font-style:italic">No hay logo de imagen subido.</p>
                    </template>
                </div>

                <!-- Hidden inputs para gestión de logo actual -->
                <input type="hidden" name="remove_current_logo" x-model="removeCurrentLogoField" value="0">

                <div class="settings-field" style="margin-top:12px">
                    <label for="logo_alt">Texto alternativo (alt) del logo</label>
                    <input type="text" id="logo_alt" name="logo_alt"
                           class="brutalist-input"
                           value="<?= $escape($logo['alt'] ?? MATER_SITE_NAME) ?>"
                           placeholder="<?= $escape(MATER_SITE_NAME) ?>"
                           style="max-width:400px"
                           x-bind:disabled="logoType !== 'image'">
                </div>
            </div>

            <!-- Logo mode inicial desde PHP -->
            <input type="hidden" name="initial_logo_type" value="<?= $escape($logo['type'] ?? 'text') ?>">
        </div>

        <!-- ════════════════════════════════════════════════
             NAVEGACIÓN
             ════════════════════════════════════════════════ -->
        <div class="settings-section">
            <p class="settings-section-title">Navegación</p>
            <p style="font-size:12px;color:#888;margin:-8px 0 16px">Los enlaces aparecen en la cabecera y/o footer del sitio. Arrastra para reordenar.</p>

            <template x-for="(item, index) in navItems" :key="item._key">
                <div class="nav-item" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;padding:12px;border:1px solid #e0e0e0;background:#fafafa">
                    <!-- Drag handle -->
                    <span style="cursor:grab;color:#bbb;padding-top:8px;font-size:18px;user-select:none"
                          @mousedown="startDrag($event, index, 'nav')"
                          @touchstart.prevent="startDrag($event, index, 'nav')">⠿</span>

                    <div style="flex:1;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start">
                        <div class="settings-field" style="flex:1;min-width:120px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">Etiqueta</label>
                            <input type="text" class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                   :name="'nav_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="día">
                        </div>
                        <div class="settings-field" style="flex:1.5;min-width:160px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">URL</label>
                            <input type="text" class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                   :name="'nav_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="/post?tag=dia">
                        </div>
                        <div class="settings-field" style="min-width:80px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">Ámbito</label>
                            <select class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                    :name="'nav_scope[' + index + ']'"
                                    x-model="item.scope">
                                <option value="both">Header + Footer</option>
                                <option value="header">Header</option>
                                <option value="footer">Footer</option>
                            </select>
                        </div>
                        <div class="settings-field" style="min-width:80px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">Abrir en</label>
                            <select class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                    :name="'nav_target[' + index + ']'"
                                    x-model="item.target">
                                <option value="_self">Misma pestaña</option>
                                <option value="_blank">Nueva pestaña</option>
                            </select>
                        </div>

                        <!-- Hidden para trackear items a guardar -->
                        <input type="hidden" :name="'nav_keep[' + index + ']'" value="1">
                    </div>

                    <button type="button" class="btn-sm btn-danger" @click="removeNavItem(index)"
                            style="margin-top:20px;font-size:11px;flex-shrink:0"
                            x-show="navItems.length > 1">✕</button>
                </div>
            </template>

            <button type="button" class="brutalist-btn-secondary" @click="addNavItem()" style="margin-top:4px;font-size:13px">
                + Añadir enlace
            </button>
        </div>

        <!-- ════════════════════════════════════════════════
             REDES SOCIALES
             ════════════════════════════════════════════════ -->
        <div class="settings-section">
            <p class="settings-section-title">Redes Sociales</p>
            <p style="font-size:12px;color:#888;margin:-8px 0 16px">Los iconos aparecen en el footer del sitio.</p>

            <template x-for="(item, index) in socialItems" :key="item._key">
                <div class="nav-item" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;padding:12px;border:1px solid #e0e0e0;background:#fafafa">
                    <!-- Drag handle -->
                    <span style="cursor:grab;color:#bbb;padding-top:8px;font-size:18px;user-select:none"
                          @mousedown="startDrag($event, index, 'social')"
                          @touchstart.prevent="startDrag($event, index, 'social')">⠿</span>

                    <div style="flex:1;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start">
                        <div class="settings-field" style="min-width:130px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">Plataforma</label>
                            <select class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                    :name="'social_platform[' + index + ']'"
                                    x-model="item.platform">
<?php foreach ($socialPlatforms as $pValue => $pLabel): ?>
                                <option value="<?= $pValue ?>"><?= $pLabel ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="settings-field" style="flex:2;min-width:200px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">URL</label>
                            <input type="text" class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                   :name="'social_url[' + index + ']'"
                                   x-model="item.url"
                                   placeholder="https://www.instagram.com/mater_natura/">
                        </div>
                        <div class="settings-field" style="flex:1;min-width:120px;margin:0">
                            <label style="font-size:11px;margin-bottom:2px">Etiqueta</label>
                            <input type="text" class="brutalist-input" style="font-size:14px;padding:6px 8px"
                                   :name="'social_label[' + index + ']'"
                                   x-model="item.label"
                                   placeholder="Instagram">
                        </div>

                        <!-- Hidden para trackear items a guardar -->
                        <input type="hidden" :name="'social_keep[' + index + ']'" value="1">
                    </div>

                    <button type="button" class="btn-sm btn-danger" @click="removeSocialItem(index)"
                            style="margin-top:20px;font-size:11px;flex-shrink:0"
                            x-show="socialItems.length > 1">✕</button>
                </div>
            </template>

            <button type="button" class="brutalist-btn-secondary" @click="addSocialItem()" style="margin-top:4px;font-size:13px">
                + Añadir red social
            </button>
        </div>

        <!-- ════════════════════════════════════════════════
             FOOTER
             ════════════════════════════════════════════════ -->
        <div class="settings-section" style="border-bottom:none">
            <p class="settings-section-title">Footer</p>

            <div class="settings-field">
                <label for="footer_copyright">Texto de copyright</label>
                <p style="font-size:12px;color:#888;margin:2px 0 6px">Se muestra en la parte inferior del sitio.</p>
                <input type="text" id="footer_copyright" name="footer_copyright"
                       class="brutalist-input"
                       value="<?= $escape($footer['copyright'] ?? MATER_SITE_NAME) ?>"
                       placeholder="<?= $escape(MATER_SITE_NAME) ?>"
                       style="max-width:500px">
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
            // State
            logoType: '<?= $escape($logo['type'] ?? 'text') ?>',
            logoPreviewURL: null,
            currentLogoURL: <?php
                if (($logo['type'] ?? '') === 'image' && !empty($logo['path'])) {
                    echo json_encode('/media/' . ltrim($logo['path'], '/'), JSON_UNESCAPED_SLASHES);
                } else {
                    echo 'null';
                }
            ?>,
            removeCurrentLogoField: '0',

            navItems: <?= $navJson ?: '[]' ?>,
            socialItems: <?= $socialJson ?: '[]' ?>,

            // Drag state
            dragIndex: null,
            dragList: null,
            dragClone: null,

            init() {
                // Asegurar keys únicas para Alpine
                this.navItems = this.navItems.map((item, i) => ({...item, _key: 'nav_' + Date.now() + '_' + i}));
                this.socialItems = this.socialItems.map((item, i) => ({...item, _key: 'social_' + Date.now() + '_' + i}));

                if (this.navItems.length === 0) this.addNavItem();
                if (this.socialItems.length === 0) this.addSocialItem();
            },

            // ─── Logo ───
            switchLogoMode(mode) {
                this.logoType = mode;
                if (mode === 'text') {
                    this.logoPreviewURL = null;
                }
            },

            previewLogo(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.logoPreviewURL = e.target.result;
                };
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
                this.removeCurrentLogoField = '1';
                this.logoType = 'text';
                // Force radio button
                document.querySelector('input[name="logo_type"][value="text"]').checked = true;
            },

            // ─── Nav Items ───
            addNavItem() {
                const idx = this.navItems.length;
                this.navItems.push({
                    _key: 'nav_new_' + Date.now(),
                    id: 'nav_' + Date.now(),
                    label: '',
                    url: '',
                    target: '_self',
                    scope: 'both'
                });
            },

            removeNavItem(index) {
                this.navItems.splice(index, 1);
            },

            // ─── Social Items ───
            addSocialItem() {
                this.socialItems.push({
                    _key: 'social_new_' + Date.now(),
                    id: 'social_' + Date.now(),
                    platform: 'instagram',
                    url: '',
                    label: ''
                });
            },

            removeSocialItem(index) {
                this.socialItems.splice(index, 1);
            },

            // ─── Drag & Drop reordering ───
            startDrag(event, index, listName) {
                this.dragIndex = index;
                this.dragList = listName;
                const target = event.target.closest('.nav-item');
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
                    const clientY = e.clientY || e.touches?.[0]?.clientY || 0;
                    this.dragClone.style.left = rect.left + 'px';
                    this.dragClone.style.top = (clientY - offsetY) + 'px';

                    // Encontrar el item sobre el que estamos
                    const items = document.querySelectorAll('.nav-item');
                    let dropIndex = -1;
                    items.forEach((item, i) => {
                        const r = item.getBoundingClientRect();
                        const mid = r.top + r.height / 2;
                        if (clientY >= r.top && clientY <= r.bottom) {
                            dropIndex = i;
                        }
                    });

                    if (dropIndex >= 0 && dropIndex !== this.dragIndex && this.dragIndex !== null) {
                        const list = this.dragList === 'nav' ? this.navItems : this.socialItems;
                        const [moved] = list.splice(this.dragIndex, 1);
                        list.splice(dropIndex, 0, moved);
                        this.dragIndex = dropIndex;
                    }
                };

                const onUp = () => {
                    if (this.dragClone) {
                        document.body.removeChild(this.dragClone);
                    }
                    this.dragClone = null;
                    this.dragIndex = null;
                    this.dragList = null;
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    document.removeEventListener('touchmove', onMove);
                    document.removeEventListener('touchend', onUp);
                };

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
                document.addEventListener('touchmove', onMove, {passive: true});
                document.addEventListener('touchend', onUp);
            }
        };
    }
</script>
