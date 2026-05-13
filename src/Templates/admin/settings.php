<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Ajustes</h1>
    </div>

    <form method="POST" action="/admin/ajustes" class="admin-form">
        <?= $csrfField ?>

        <fieldset>
            <legend>Página de inicio</legend>
            <div class="form-group">
                <label for="home_page_id">¿Qué página se muestra en la raíz?</label>
                <select id="home_page_id" name="home_page_id">
                    <option value="">Selecciona una página…</option>
                    <?php foreach ($pages as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($currentHome['id'] ?? null) == $p['id'] ? 'selected' : '' ?>>
                        <?= $escape($p['title']) ?> (<?= $escape($p['slug']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </fieldset>

        <fieldset>
            <legend>Sitemap</legend>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="regenerate_sitemap" value="1">
                    Regenerar sitemap.xml ahora
                </label>
                <small class="form-hint">El sitemap se regenera automáticamente con cada cambio.</small>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar ajustes</button>
        </div>
    </form>
</div>
