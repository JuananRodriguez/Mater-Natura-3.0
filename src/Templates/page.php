<?php declare(strict_types=1); ?>
<article class="max-w-4xl mx-auto px-4 md:px-12 lg:px-24 py-12" itemscope itemtype="https://schema.org/WebPage">
    <header class="page-header mb-8">
        <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] m-0" itemprop="name"><?= $escape($page['title']) ?></h1>
    </header>

    <div class="text-sm text-black dark:text-gray-300 leading-relaxed max-w-3xl" itemprop="text">
        <?php if (!empty($componentsHtml)): ?>
            <?= $componentsHtml ?>
        <?php else: ?>
            <?= renderHtml($page['content']) ?>
        <?php endif; ?>
    </div>
</article>
