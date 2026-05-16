// Mater-Natura — Alpine.js Public Components

document.addEventListener('alpine:init', () => {
    // Theme preview component (for post-single page)
    Alpine.data('themePreview', () => ({
        theme: document.body.classList.contains('theme-dark') ? 'dark' : 'light',
        toggle() {
            this.theme = this.theme === 'dark' ? 'light' : 'dark';
            document.body.className = 'theme-' + this.theme;
        }
    }));

    // Lazy image load with fade-in
    Alpine.data('lazyImage', () => ({
        loaded: false,
        init() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loaded = true;
                        observer.disconnect();
                    }
                });
            });
            observer.observe(this.$el);
        }
    }));
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            document.querySelector(href)?.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

/* ─── Lightbox ─── */
(function() {
    const overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.innerHTML = '<button class="lightbox-close" aria-label="Cerrar">&times;</button><img src="" alt="">';
    document.body.appendChild(overlay);

    const lbImg = overlay.querySelector('img');
    const lbClose = overlay.querySelector('.lightbox-close');

    function open(src, alt) {
        lbImg.src = src;
        lbImg.alt = alt || '';
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        lbImg.src = '';
    }

    lbClose.addEventListener('click', close);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) close();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) close();
    });

    // Bind all images with data-lightbox attribute
    document.querySelectorAll('img[data-lightbox]').forEach(function(img) {
        img.addEventListener('click', function() {
            open(this.src, this.alt);
        });
        img.style.cursor = 'pointer';
    });

    // Also bind images inside rendered content (.post-description)
    document.querySelectorAll('.post-description img').forEach(function(img) {
        if (!img.hasAttribute('data-lightbox')) {
            img.addEventListener('click', function() {
                open(this.src, this.alt);
            });
            img.style.cursor = 'pointer';
        }
    });

    // Expose for dynamic content
    window.lightbox = { open: open, close: close };
})();

/* ─── Keyboard Navigation (Prev/Next Post) ─── */
(function() {
    function getNavLink(direction) {
        var label = direction === 'prev' ? 'Post anterior' : 'Siguiente post';
        return document.querySelector('a[aria-label="' + label + '"]');
    }

    document.addEventListener('keydown', function(e) {
        // Ignorar si el usuario está escribiendo
        var tag = e.target.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) {
            return;
        }

        var isLeft = e.key === 'ArrowLeft';
        var isRight = e.key === 'ArrowRight';
        if (!isLeft && !isRight) return;

        e.preventDefault();

        var link = getNavLink(isLeft ? 'prev' : 'next');
        if (!link || !link.href) return;

        // Si el lightbox está abierto, guardar estado antes de navegar
        var overlay = document.querySelector('.lightbox-overlay');
        if (overlay && overlay.classList.contains('active')) {
            sessionStorage.setItem('lightboxOpen', 'true');
        }

        window.location.href = link.href;
    });
})();

/* ─── Restaurar lightbox tras navegación por teclado ─── */
(function() {
    if (sessionStorage.getItem('lightboxOpen') === 'true') {
        sessionStorage.removeItem('lightboxOpen');

        document.addEventListener('DOMContentLoaded', function() {
            var img = document.querySelector('img[data-lightbox]');
            if (img && window.lightbox) {
                window.lightbox.open(img.src, img.alt);
            }
        });

        // Si el DOM ya está cargado (caso raro)
        if (document.readyState !== 'loading' && !document.querySelector('.lightbox-overlay.active')) {
            var img = document.querySelector('img[data-lightbox]');
            if (img && window.lightbox) {
                window.lightbox.open(img.src, img.alt);
            }
        }
    }
})();
