<?php declare(strict_types=1); ?>
<article class="max-w-4xl mx-auto px-4 md:px-12 lg:px-24 py-12" x-data="{ showContent: true }">
    <?php if ($page): ?>
        <header class="mb-12">
            <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] m-0"><?= $escape($page['title']) ?></h1>
        </header>
        <div class="text-sm text-black dark:text-gray-300 leading-relaxed max-w-3xl">
            <?= renderHtml($page['content']) ?>
        </div>
    <?php else: ?>
        <div class="text-center py-16">
            <p class="text-base mb-4">Bienvenido a Mater-Natura.</p>
            <p class="text-sm opacity-70 mb-6">Un espacio donde los versos encuentran su hogar.</p>
            <br>
            <a href="/post"
               class="inline-block px-8 py-3 border border-black text-sm cursor-pointer text-center no-underline
                      bg-black text-white hover:bg-gray-800 transition-all duration-200">Leer poemas</a>
        </div>
    <?php endif; ?>
</article>
