<?php declare(strict_types=1); ?>
<div id="header" x-data="{ overlayOpen: false }" class="relative text-center">
    <!-- Logo centrado -->
    <div class="pt-4">
        <h1 class="my-4 leading-10">
            <a href="/">
                <img id="logo_header" src="/assets/img/Mater-Natura.gif"
                     title="Logotipo de Mater Natura" alt="Mater Natura"
                     class="inline-block max-w-[280px] sm:max-w-[400px]">
            </a>
        </h1>
    </div>

    <!-- Botón menú hamburguesa -->
    <div class="absolute top-[30px] right-0 cursor-pointer z-[9999]"
         @click="overlayOpen = true">
        <img src="/assets/img/menu-mater-natura.jpg" alt="Menú" class="w-[30px] h-[30px]">
    </div>

    <!-- Overlay de navegación (full-screen) -->
    <div class="overlay bg-white/95 dark:bg-[rgba(26,26,26,0.95)]"
         :class="{ 'open': overlayOpen }">
        <a href="javascript:void(0)" class="closebtn inline-flex items-center justify-center no-underline" @click="overlayOpen = false"><?= svg_icon('x') ?></a>
        <div class="relative w-full text-center mt-[30px] top-[25%]">
            <a href="/"
               class="block uppercase text-[20px] text-black dark:text-[#e0e0e0] no-underline py-2 mx-[25px]
                      hover:text-[#666666] dark:hover:text-[#cccccc] transition-colors duration-400"
               @click="overlayOpen = false">Inicio</a>
            <a href="/post"
               class="block uppercase text-[20px] text-black dark:text-[#e0e0e0] no-underline py-2 mx-[25px]
                      hover:text-[#666666] dark:hover:text-[#cccccc] transition-colors duration-400"
               @click="overlayOpen = false">Poemas</a>
            <a href="/admin"
               class="block uppercase text-[20px] text-black dark:text-[#e0e0e0] no-underline py-2 mx-[25px]
                      hover:text-[#666666] dark:hover:text-[#cccccc] transition-colors duration-400"
               @click="overlayOpen = false">Admin</a>
            <?php if ($isAuthenticated): ?>
                <a href="/logout"
                   class="block uppercase text-[20px] text-black dark:text-[#e0e0e0] no-underline py-2 mx-[25px]
                          hover:text-[#666666] dark:hover:text-[#cccccc] transition-colors duration-400"
                   @click="overlayOpen = false">Salir</a>
            <?php endif; ?>
            <div class="flex justify-center mt-5">
                <a href="https://www.instagram.com/mater_natura/" target="_blank" class="mx-0 p-0">
                    <img class="w-[25px] h-[25px] pl-[25px] hover:opacity-20 transition-opacity duration-400"
                         src="/assets/img/icono-instagram-mater-natura.png" alt="Instagram">
                </a>
                <a href="https://www.pinterest.es/maternatura/" target="_blank" class="mx-0 p-0">
                    <img class="w-[25px] h-[25px] pl-[25px] hover:opacity-20 transition-opacity duration-400"
                         src="/assets/img/icono-pinterest-mater-natura.png" alt="Pinterest">
                </a>
            </div>
        </div>
    </div>
</div>
