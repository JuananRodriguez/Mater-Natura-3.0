<?php declare(strict_types=1); ?>
<article class="max-w-4xl mx-auto px-4 md:px-12 lg:px-24 py-12"
         x-data="{
    offset: 6,
    limit: 6,
    total: <?= (int)$totalPosts ?>,
    tag: '<?= $escape($tag ?? '') ?>',
    loading: false,
    error: false,
    get hasMore() { return this.offset < this.total; },

    init() {
        const sentinel = this.$refs.sentinel;
        if (!sentinel) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.hasMore && !this.loading) {
                    this.loadMore();
                }
            });
        }, { rootMargin: '200px' });

        observer.observe(sentinel);
    },

    loadMore() {
        if (this.loading || !this.hasMore) return;

        this.loading = true;
        this.error = false;

        const params = new URLSearchParams({
            offset: this.offset,
            limit: this.limit,
        });
        if (this.tag) params.set('tag', this.tag);

        fetch('/post/fragment?' + params.toString())
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(html => {
                if (!html.trim()) {
                    this.hasMore = false;
                    return;
                }
                this.$refs.list.insertAdjacentHTML('beforeend', html);
                this.offset += this.limit;
                this.loading = false;
            })
            .catch(err => {
                console.error('Infinite scroll error:', err);
                this.error = true;
                this.loading = false;
            });
    }
}">
    <header class="mb-8">
        <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] mb-1">Poemas</h1>
        <p class="text-sm text-gray-500 dark:text-[#777777]"><?= $totalPosts ?> poemas publicados</p>
    </header>

    <?php if ($posts): ?>
        <ul class="posts-list list-none p-0 m-0" x-ref="list">
            <?php foreach ($posts as $i => $post): ?>
                <?= $this->renderPostItem($post, $i === 0) ?>
            <?php endforeach; ?>
        </ul>

        <!-- Sentinela para IntersectionObserver -->
        <div x-ref="sentinel"
             class="flex justify-center py-8 text-sm text-gray-500 dark:text-[#777777]"
             x-show="hasMore">
            <span x-show="loading">Cargando...</span>
            <span x-show="!loading && !error">↓ Desplázate para más poemas</span>
            <span x-show="error" class="text-red-500">Error al cargar. <button @click="loadMore" class="underline bg-transparent border-none cursor-pointer text-inherit">Reintentar</button></span>
        </div>

    <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-[#777777] italic">No hay poemas publicados aún.</p>
    <?php endif; ?>
</article>
