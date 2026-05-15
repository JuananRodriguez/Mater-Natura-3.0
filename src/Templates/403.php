<?php declare(strict_types=1); ?>
<div class="mx-auto max-w-md px-4 py-16 text-center">
    <h1 class="text-3xl font-bold mb-2">Acceso restringido</h1>
    <p class="text-gray-500 mb-8"><?= $escape($message ?? 'No tienes permiso para acceder a este contenido.') ?></p>

    <?php if (!empty($loginUrl)): ?>
        <a href="<?= $escape($loginUrl) ?>"
           class="inline-block border border-black bg-black text-white px-6 py-2 text-sm uppercase tracking-widest hover:bg-gray-800 transition-colors">
            Iniciar sesión
        </a>
    <?php endif; ?>

    <p class="mt-8">
        <a href="/" class="text-sm text-gray-500 hover:text-black underline underline-offset-2">Volver al inicio</a>
    </p>
</div>
