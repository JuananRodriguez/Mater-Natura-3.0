<?php declare(strict_types=1); ?>
<article class="max-w-4xl mx-auto px-4 md:px-12 lg:px-24 py-12">
    <header class="mb-8">
        <h1 class="text-[28px] font-normal text-black dark:text-[#e0e0e0] mb-1">Poemas</h1>
        <p class="text-sm text-gray-500 dark:text-[#777777]"><?= $totalPosts ?> poemas publicados</p>
    </header>

    <?php if ($posts): ?>
        <ul class="posts-list list-none p-0 m-0">
            <?php foreach ($posts as $post): ?>
                <li class="mb-8 pb-6 border-b border-gray-200 dark:border-[#333333]">
                    <?php if (!empty($post['image_url'])): ?>
                    <a href="/<?= $escape($post['slug']) ?>" class="block mb-3 no-underline">
                        <img src="/media/<?= $escape(ltrim($post['image_url'], '/')) ?>"
                             alt="<?= $escape($post['title']) ?>"
                             class="w-full h-48 object-cover rounded-sm"
                             loading="lazy">
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
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-sm text-gray-500 dark:text-[#777777] italic">No hay poemas publicados aún.</p>
    <?php endif; ?>
</article>
