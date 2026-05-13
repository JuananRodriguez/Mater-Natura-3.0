<?php
/**
 * Comments Plugin — Sección de comentarios (estilo WordPress)
 * Renderizada tras el contenido del post.
 *
 * Variables: $post, $comments, $flashMessage, $escape, $csrfField
 */
?>
<section class="comments-section mt-8 pt-6 font-titillium text-black dark:text-[#e0e0e0] max-w-content mx-auto">
    <h2 class="text-lg font-normal mb-4">
        <?= count($comments) ?> <?= count($comments) === 1 ? 'Comentario' : 'Comentarios' ?>
    </h2>

    <?php if ($flashMessage): ?>
        <div class="px-4 py-3 mb-4 text-sm <?= $flashMessage['type'] === 'success' ? 'bg-gray-100 dark:bg-gray-800 border border-gray-400 dark:border-gray-500' : 'bg-gray-100 dark:bg-gray-800 border border-gray-400 dark:border-gray-500' ?>">
            <?= $escape($flashMessage['text']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($comments)): ?>
        <div class="mb-6">
            <?php foreach ($comments as $comment): ?>
                <article class="comment py-3 border-b border-gray-200 dark:border-[#333333] last:border-b-0 text-sm" id="comment-<?= (int) $comment['id'] ?>">
                    <div class="font-bold text-[#111] dark:text-[#cccccc] text-sm">
                        <?= $escape($comment['author_name']) ?>
                        <?php if (!empty($comment['author_website'])): ?>
                            <span class="text-xs text-[#999] dark:text-[#777777] font-normal"> &mdash; <a href="<?= $escape($comment['author_website']) ?>" rel="nofollow" class="text-inherit hover:underline"><?= $escape($comment['author_website']) ?></a></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-[#999] dark:text-[#777777] inline-block ml-2">
                        <?= date('j F, Y', strtotime($comment['created_at'])) ?>
                    </div>
                    <div class="leading-relaxed text-sm mt-1 clear-both pt-1 text-black dark:text-gray-300">
                        <?= nl2br($escape($comment['content'])) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="respond" class="bg-white dark:bg-transparent mb-5 relative clear-both">
        <h3 class="comment-form-title text-[20px] font-normal font-titillium text-black dark:text-[#e0e0e0] mb-4">Deja una respuesta</h3>
        <form method="POST" action="/comment" class="comment-form font-titillium text-black dark:text-[#e0e0e0]" id="commentform">
            <?= $csrfField ?>
            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
            <input type="hidden" name="return_url" value="<?= $escape('/' . $post['slug']) ?>">

            <p class="text-[13px] text-gray-600 dark:text-gray-400 mb-4">
                <span class="block">Tu dirección de correo electrónico no será publicada.</span>
                <span>Los campos obligatorios están marcados con <span class="text-black dark:text-white font-bold">*</span></span>
            </p>

            <p class="mb-4">
                <label for="comment-author" class="font-bold text-sm inline">Comentario <span class="text-black dark:text-white font-bold">*</span></label>
                <textarea id="comment-author"
                          name="content"
                          cols="45"
                          rows="8"
                          required
                          maxlength="2000"
                          class="w-full px-3 py-2 border border-[#2d2d2d] dark:border-gray-500 rounded-none font-titillium text-sm bg-transparent block mt-1 resize-y min-h-[100px]
                                 focus:border-gray-400 dark:focus:border-[#999999] focus:outline-none focus:shadow-[0_1px_1px_rgba(0,0,0,0.075)_inset,0_0_8px_rgba(170,170,170,0.6)]"></textarea>
            </p>

            <p class="mb-4">
                <label for="author" class="font-bold text-sm inline">Nombre <span class="text-black dark:text-white font-bold">*</span></label>
                <input id="author"
                       type="text"
                       name="author_name"
                       value=""
                       size="30"
                       maxlength="100"
                       required
                       class="w-full px-3 py-2 border border-[#2d2d2d] dark:border-gray-500 rounded-none font-titillium text-sm bg-transparent block mt-1
                              focus:border-gray-400 dark:focus:border-[#999999] focus:outline-none focus:shadow-[0_1px_1px_rgba(0,0,0,0.075)_inset,0_0_8px_rgba(170,170,170,0.6)]">
            </p>

            <p class="mb-4">
                <label for="email" class="font-bold text-sm inline">Correo electrónico <span class="text-black dark:text-white font-bold">*</span></label>
                <input id="email"
                       type="email"
                       name="author_email"
                       value=""
                       size="30"
                       maxlength="100"
                       required
                       class="w-full px-3 py-2 border border-[#2d2d2d] dark:border-gray-500 rounded-none font-titillium text-sm bg-transparent block mt-1
                              focus:border-gray-400 dark:focus:border-[#999999] focus:outline-none focus:shadow-[0_1px_1px_rgba(0,0,0,0.075)_inset,0_0_8px_rgba(170,170,170,0.6)]">
            </p>

            <p class="mb-4">
                <label for="url" class="font-bold text-sm inline">Página web</label>
                <input id="url"
                       type="url"
                       name="author_website"
                       value=""
                       size="30"
                       maxlength="255"
                       class="w-full px-3 py-2 border border-[#2d2d2d] dark:border-gray-500 rounded-none font-titillium text-sm bg-transparent block mt-1
                              focus:border-gray-400 dark:focus:border-[#999999] focus:outline-none focus:shadow-[0_1px_1px_rgba(0,0,0,0.075)_inset,0_0_8px_rgba(170,170,170,0.6)]">
            </p>

            <p class="comment-form-cookies-consent mb-4 flex items-center gap-2 text-[13px]">
                <input id="wp-comment-cookies-consent" name="cookies_consent" type="checkbox" value="yes" class="w-auto">
                <label for="wp-comment-cookies-consent">Guarda mi nombre, correo electrónico y web en este navegador para la próxima vez que comente.</label>
            </p>

            <p class="form-submit">
                <button type="submit" id="submit"
                        class="inline-block px-8 py-3 border border-[#2d2d2d] dark:border-[#999999]
                               font-titillium text-sm cursor-pointer text-center
                               bg-[#2d2d2d] dark:bg-[#999999] text-white dark:text-[#1a1a1a]
                               hover:bg-[#444444] dark:hover:bg-[#777777]
                               transition-all duration-200">Publicar el comentario</button>
            </p>
        </form>

        <a href="javascript:history.back()"><p class="text-sm text-black dark:text-[#e0e0e0] hover:underline">Volver</p></a>
    </div>
</section>
