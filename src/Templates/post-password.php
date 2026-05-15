<?php declare(strict_types=1); ?>
<div class="mx-auto max-w-md px-4 py-16 text-center">
    <h1 class="text-3xl font-bold mb-2"><?= $escape($post['title']) ?></h1>
    <p class="text-sm text-gray-500 mb-8">Este contenido está protegido con contraseña.</p>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 border border-red-300 bg-red-50 text-red-700 text-sm rounded">
            <?= $escape($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/<?= $escape($post['slug']) ?>" class="space-y-4">
        <input type="password" name="post_password" required
               placeholder="Introduce la contraseña"
               class="w-full border border-gray-300 px-4 py-2 text-center text-sm focus:outline-none focus:border-black transition-colors"
               autocomplete="off">
        <button type="submit"
                class="w-full border border-black bg-black text-white px-6 py-2 text-sm uppercase tracking-widest hover:bg-gray-800 transition-colors">
            Acceder
        </button>
    </form>

    <p class="mt-8">
        <a href="/" class="text-sm text-gray-500 hover:text-black underline underline-offset-2">Volver al inicio</a>
    </p>
</div>
