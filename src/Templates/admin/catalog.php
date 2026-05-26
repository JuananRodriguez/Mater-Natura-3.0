<?php declare(strict_types=1); ?>
<div class="admin-wrapper">
    <h1>Generar Catálogo PDF</h1>
    <p class="admin-description">Selecciona los posts y las opciones para generar tu catálogo PDF</p>

    <?php if (!empty($posts)): ?>
    <form method="POST" action="/admin/catalog/generate" class="catalog-form"
          x-data="catalogForm()">
        <?= $csrfField ?>

        <div class="catalog-options">
            <div class="catalog-column">
                <fieldset>
                    <legend>¿Qué incluir?</legend>
                    <label class="catalog-checkbox">
                        <input type="checkbox" name="show_ref" checked> Referencia (número)
                    </label>
                    <label class="catalog-checkbox">
                        <input type="checkbox" name="show_image" checked> Imagen
                    </label>
                    <label class="catalog-checkbox">
                        <input type="checkbox" name="show_title" checked> Título
                    </label>
                    <label class="catalog-checkbox">
                        <input type="checkbox" name="show_description" checked> Descripción
                    </label>
                </fieldset>
            </div>

            <div class="catalog-column">
                <fieldset>
                    <legend>Modo</legend>
                    <label class="catalog-radio">
                        <input type="radio" name="mode" value="todo" x-model="mode" checked
                               @change="filterPosts('todo')">
                        <div class="mode-preview">
                            <strong>Todo</strong>
                            <p>Todos los posts publicados. PDF con fondo claro.</p>
                        </div>
                    </label>
                    <label class="catalog-radio">
                        <input type="radio" name="mode" value="claro" x-model="mode"
                               @change="filterPosts('claro')">
                        <div class="mode-preview">
                            <strong>Claro</strong>
                            <p>Solo posts con temática clara. PDF con fondo claro.</p>
                        </div>
                    </label>
                    <label class="catalog-radio">
                        <input type="radio" name="mode" value="oscuro" x-model="mode"
                               @change="filterPosts('oscuro')">
                        <div class="mode-preview">
                            <strong>Oscuro</strong>
                            <p>Solo posts con temática oscura. PDF con fondo oscuro.</p>
                        </div>
                    </label>
                </fieldset>
            </div>
        </div>

        <fieldset>
            <legend>Seleccionar posts</legend>
            <p class="form-note">
                <span x-show="mode === 'todo'">Todos los posts publicados están seleccionados.</span>
                <span x-show="mode === 'claro'">Mostrando solo posts de temática clara.</span>
                <span x-show="mode === 'oscuro'">Mostrando solo posts de temática oscura. El PDF se generará con fondo oscuro.</span>
                Desmarca los que no quieras incluir.
            </p>
            <div class="posts-grid" id="posts-grid" x-ref="postsGrid">
                <?php foreach ($posts as $post): ?>
                <?php
                $postId = is_object($post) ? $post->id : $post['id'];
                $postTitle = is_object($post) ? $post->title : $post['title'];
                $postSlug = is_object($post) ? $post->slug : $post['slug'];
                $postRef = is_object($post) ? ($post->reference ?? '') : ($post['reference'] ?? '');
                $postImage = is_object($post) ? ($post->image_url ?? '') : ($post['image_url'] ?? '');
                $postTemplate = is_object($post) ? ($post->template ?? 'dark') : ($post['template'] ?? 'dark');
                ?>
                <label class="post-item" data-template="<?= $postTemplate ?>">
                    <input type="checkbox" name="post_ids[]" value="<?= $postId ?>" checked>
                    <div class="post-preview">
                        <?php if (!empty($postImage)): ?>
                        <?php
                        $imagePath = MATER_UPLOADS_DIR . '/' . $postImage;
                        if (file_exists($imagePath)): ?>
                        <img src="/media/<?= $escape($postImage) ?>" alt="<?= $escape($postTitle) ?>" loading="lazy">
                        <?php else: ?>
                        <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                        <?php endif; ?>
                        <div class="post-info">
                            <h3><?= $escape($postTitle) ?></h3>
                            <small><?= $postRef ? 'Ref: ' . $escape($postRef) : 'Ref: ' . $escape($postSlug) ?></small>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            <div x-show="loading" class="filter-loading" style="display:none;">
                <span>Cargando posts…</span>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= svg_icon('file') ?> Generar Catálogo PDF
            </button>
            <a href="/admin" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    <?php else: ?>
    <div class="empty-state">
        <p>No hay posts publicados para generar un catálogo.</p>
        <a href="/admin/posts/editar" class="btn btn-primary">Crear el primer post</a>
    </div>
    <?php endif; ?>
</div>

<script>
function catalogForm() {
    return {
        mode: 'todo',
        loading: false,
        filterPosts(mode) {
            this.loading = true;
            const grid = this.$refs.postsGrid;
            grid.style.opacity = '0.4';

            fetch('/admin/catalog/filter?mode=' + mode)
                .then(r => {
                    if (!r.ok) throw new Error('Error en la respuesta');
                    return r.text();
                })
                .then(html => {
                    grid.innerHTML = html;
                    grid.style.opacity = '1';
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Error al filtrar posts:', err);
                    grid.style.opacity = '1';
                    this.loading = false;
                });
        }
    };
}
</script>