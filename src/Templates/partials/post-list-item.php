<li class="mb-8 pb-6 border-b border-gray-200 dark:border-[#333333]">
    <?php if (!empty($post['image_url'])): ?>
    <a href="/<?= $escape($post['slug']) ?>" class="block mb-3 no-underline">
        <img src="/media/<?= $escape(ltrim($post['image_url'], '/')) ?>"
             alt="<?= $escape($post['title']) ?>"
             class="w-full rounded-sm"
             width="667" height="1000"
             <?= $isFirst ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
    </a>
    <?php endif; ?>
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
