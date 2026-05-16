<?php declare(strict_types=1); ?>
<article class="page-single" itemscope itemtype="https://schema.org/WebPage">
    <header class="page-header">
        <h1 itemprop="name"><?= $escape($page['title']) ?></h1>
    </header>

    <div class="page-content prose" itemprop="text">
        <?php if (!empty($componentsHtml)): ?>
            <?= $componentsHtml ?>
        <?php else: ?>
            <?= renderHtml($page['content']) ?>
        <?php endif; ?>
    </div>
</article>
