<?php declare(strict_types=1); ?>
<a href="/<?= $escape($post['slug']) ?>"
   class="masonry-card block relative overflow-hidden rounded-sm no-underline"
   title="<?= $escape($post['title']) ?>">
    <img src="/media/<?= $escape(ltrim($post['image_url'], '/')) ?>"
         alt="<?= $escape($post['title']) ?>"
         class="w-full block"
         width="667" height="1000"
         <?= $isFirst ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
    <div class="masonry-overlay">
        <h2 class="masonry-title text-sm md:text-base font-normal m-0 text-center leading-snug">
            <?= $escape($post['title']) ?>
        </h2>
    </div>
</a>
