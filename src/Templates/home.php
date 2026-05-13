<article class="max-w-content mx-auto font-titillium" x-data="{ showContent: true }">
    <?php if ($page): ?>
        <header class="mb-8">
            <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] mb-2"><?= $escape($page['title']) ?></h1>
        </header>
        <div class="text-sm text-black dark:text-gray-300 leading-relaxed">
            <?= renderHtml($page['content']) ?>
        </div>
    <?php else: ?>
        <div class="text-center py-16">
            <p class="text-base mb-4">Bienvenido a Mater-Natura.</p>
            <p class="text-sm opacity-70 mb-6">Un espacio donde los versos encuentran su hogar.</p>
            <br>
            <a href="/post"
               class="inline-block px-8 py-3 border border-[#2d2d2d] dark:border-[#999999]
                      font-titillium text-sm cursor-pointer text-center no-underline
                      bg-[#2d2d2d] dark:bg-[#999999] text-white dark:text-[#1a1a1a]
                      hover:bg-[#444444] dark:hover:bg-[#777777]
                      transition-all duration-200">Leer poemas</a>
        </div>
    <?php endif; ?>
</article>
