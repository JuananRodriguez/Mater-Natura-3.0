<?php declare(strict_types=1); ?>
<article class="px-4 md:px-12 lg:px-24 py-12" itemscope itemtype="https://schema.org/BlogPosting">

    <!-- Main Image -->
    <?php if (!empty($post['image_url'])): ?>
    <div class="w-full flex justify-center mb-6">
        <img src="/media/<?= $escape(ltrim($post['image_url'], '/')) ?>"
             alt="<?= $escape($post['title']) ?>"
             class="max-w-full h-auto object-contain cursor-pointer"
             style="max-height: 70vh;"
             itemprop="image"
             loading="lazy"
             data-lightbox>
    </div>
    <?php endif; ?>

    <!-- Post Meta Info: title + reference -->
    <div class="flex justify-between items-end mb-4">
        <h1 class="text-lg font-medium text-gray-800 m-0" itemprop="headline">
            <?= $escape($post['title']) ?>
        </h1>
        <span class="text-sm font-bold text-gray-500">ref. <?= $post['id'] ?></span>
    </div>

    <!-- Post Description -->
    <?php if (!empty($post['description'])): ?>
    <div class="text-sm text-gray-500 leading-relaxed mb-8 max-w-3xl font-titillium post-description" itemprop="description">
        <?php if (!empty($componentsHtml)): ?>
            <?= $componentsHtml ?>
        <?php else: ?>
            <?= renderHtml($post['description']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Interaction and Navigation Controls -->
    <div class="flex justify-between items-center text-gray-400 post-controls">
        <!-- Previous Arrow -->
        <?php if (isset($prevPost) && $prevPost): ?>
        <a href="/<?= $escape($prevPost['slug']) ?>"
           aria-label="Post anterior"
           class="hover:text-black transition-colors p-2 no-underline inline-flex text-gray-400">
            <svg fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        <?php else: ?>
        <div class="p-2 opacity-20"><!-- placeholder --></div>
        <?php endif; ?>

        <!-- Action Icons -->
        <div class="flex gap-4">
            <button class="hover:text-black transition-colors" aria-label="Compartir externo"
                    onclick="window.open('/<?= $escape($post['slug']) ?>', '_blank')">
                <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" x2="21" y1="14" y2="3"></line>
                </svg>
            </button>
            <button class="hover:text-black transition-colors" aria-label="Abrir en nueva pestaña"
                    onclick="window.open(window.location.href, '_blank')">
                <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
                    <rect height="18" rx="2" ry="2" width="18" x="3" y="3"></rect>
                    <line x1="3" x2="21" y1="9" y2="9"></line>
                    <line x1="9" x2="9" y1="21" y2="9"></line>
                </svg>
            </button>
            <button class="hover:text-black transition-colors" aria-label="Copiar enlace"
                    onclick="navigator.clipboard.writeText(window.location.href).then(() => { alert('Enlace copiado'); })">
                <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                </svg>
            </button>
            <button class="hover:text-black transition-colors" aria-label="Compartir"
                    onclick="if(navigator.share){navigator.share({url:window.location.href})}">
                <svg fill="none" height="20" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="18" cy="5" r="3"></circle>
                    <circle cx="6" cy="12" r="3"></circle>
                    <circle cx="18" cy="19" r="3"></circle>
                    <line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line>
                    <line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line>
                </svg>
            </button>
        </div>

        <!-- Next Arrow -->
        <?php if (isset($nextPost) && $nextPost): ?>
        <a href="/<?= $escape($nextPost['slug']) ?>"
           aria-label="Siguiente post"
           class="hover:text-black transition-colors p-2 no-underline inline-flex text-gray-400">
            <svg fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
        <?php else: ?>
        <div class="p-2 opacity-20"><!-- placeholder --></div>
        <?php endif; ?>
    </div>
</article>

<?php if (isset($commentsHtml)): ?>
    <?= $commentsHtml ?>
<?php endif; ?>
