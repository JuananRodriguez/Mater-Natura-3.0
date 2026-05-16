<?php declare(strict_types=1); ?>
<div class="post-entry font-titillium text-sm text-black dark:text-[#e0e0e0] pt-10 pr-5 pb-0 pl-0 clear-both max-w-content mx-auto" itemscope itemtype="https://schema.org/BlogPosting">
    <?= $this->renderBreadcrumbs() ?>
    <?php if (!empty($post['image_url'])): ?>
        <div class="post-image clear-both block float-none mb-[10px]">
            <img class="image-image"
                 src="/media/<?= $escape(ltrim($post['image_url'], '/')) ?>"
                 alt="<?= $escape($post['title']) ?>"
                 <?php if ($imageWidth && $imageHeight): ?>
                 width="<?= $imageWidth ?>"
                 height="<?= $imageHeight ?>"
                 <?php endif; ?>
                 itemprop="image"
                 style="display: block; max-width: 100%; height: auto;">
        </div>
    <?php endif; ?>

    <div class="post-titulo text-[20px] mb-[10px] font-normal font-titillium text-black dark:text-[#e0e0e0]" itemprop="headline">
        <?= $escape($post['title']) ?>
    </div>

    <div class="estilo-fecha text-[#888888] dark:text-[#777777] font-open text-[11px] leading-[27px]"><?= date('d/m/Y', strtotime($post['published_at'])) ?></div>

    <div class="post-texto" itemprop="articleBody">
        <?php if (!empty($componentsHtml)): ?>
            <?= $componentsHtml ?>
        <?php else: ?>
            <?= renderHtml($post['description']) ?>
        <?php endif; ?>
    </div>
</div>

<div class="post-navigation mt-12 pt-6 border-t border-gray-200 dark:border-[#333333] flex justify-between font-titillium text-sm max-w-content mx-auto">
    <?php if (isset($prevPost) && $prevPost): ?>
        <a href="/<?= $escape($prevPost['slug']) ?>"
           class="inline-block px-6 py-3 border border-[#2d2d2d] dark:border-[#999999]
                  no-underline text-sm font-titillium cursor-pointer text-center
                  bg-transparent text-[#2d2d2d] dark:text-[#999999]
                  hover:bg-[#2d2d2d] dark:hover:bg-[#999999] hover:text-white dark:hover:text-[#1a1a1a]
                  transition-all duration-200">
            <?= svg_icon('chevron-left') ?> <?= $escape($prevPost['title']) ?>
        </a>
    <?php endif; ?>
    <?php if (isset($nextPost) && $nextPost): ?>
        <a href="/<?= $escape($nextPost['slug']) ?>"
           class="inline-block px-6 py-3 border border-[#2d2d2d] dark:border-[#999999]
                  no-underline text-sm font-titillium cursor-pointer text-center
                  bg-transparent text-[#2d2d2d] dark:text-[#999999]
                  hover:bg-[#2d2d2d] dark:hover:bg-[#999999] hover:text-white dark:hover:text-[#1a1a1a]
                  transition-all duration-200">
            <?= $escape($nextPost['title']) ?> <?= svg_icon('chevron-right') ?>
        </a>
    <?php endif; ?>
</div>

<?php if (isset($commentsHtml)): ?>
    <?= $commentsHtml ?>
<?php endif; ?>
