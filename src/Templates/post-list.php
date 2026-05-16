<?php declare(strict_types=1); ?>
<article class="max-w-4xl mx-auto px-4 md:px-12 lg:px-24 py-12">
    <header class="mb-8">
        <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] mb-1">Poemas</h1>
        <p class="text-sm text-gray-500 dark:text-[#777777]">Lista de poemas publicados</p>
    </header>

    <?php if ($posts): ?>
        <ul class="posts-list list-none p-0 m-0">
            <?php foreach ($posts as $post): ?>
                <li class="mb-8 pb-6 border-b border-gray-200 dark:border-[#333333]">
                    <h2 class="text-lg font-normal m-0">
                        <a href="/<?= $escape($post['slug']) ?>"
                           class="text-black dark:text-[#e0e0e0] hover:text-[#666666] dark:hover:text-[#cccccc] no-underline transition-colors">
                            <?= $escape($post['title']) ?>
                        </a>
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-[#777777] mt-1 mb-2">
                        <?= date('d/m/Y', strtotime($post['published_at'])) ?>
                    </p>
                    <p class="text-sm text-[#444444] dark:text-[#aaaaaa] leading-relaxed">
                        <?= $escape(mb_strimwidth(strip_tags($post['description']), 0, 200, '...')) ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>

        <nav class="flex justify-center items-center gap-4 mt-8 pt-6 border-t border-gray-200 dark:border-[#333333] text-sm" aria-label="Paginación">
            <?php if ($currentPage > 1): ?>
                <a href="/post?<?= $currentTag ? 'tag=' . urlencode($currentTag) . '&' : '' ?>page=<?= $currentPage - 1 ?>"
                   class="inline-block px-6 py-3 border border-black no-underline text-sm cursor-pointer text-center
                          bg-black text-white hover:bg-gray-800 transition-all duration-200">
                    <?= svg_icon('chevron-left') ?> Anterior
                </a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="/post?<?= $currentTag ? 'tag=' . urlencode($currentTag) . '&' : '' ?>page=<?= $currentPage + 1 ?>"
                   class="inline-block px-6 py-3 border border-black no-underline text-sm cursor-pointer text-center
                          bg-transparent text-black hover:bg-black hover:text-white transition-all duration-200">
                    Siguiente <?= svg_icon('chevron-right') ?>
                </a>
            <?php endif; ?>
        </nav>
    <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-[#777777] italic">No hay poemas publicados aún.</p>
    <?php endif; ?>
</article>
