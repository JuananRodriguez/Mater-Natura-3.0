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

/* ─── Navigation Helper (stores direction for View Transitions) ─── */
(function() {
    window.__navigateTo = function(url, direction) {
        if (!url) return;
        if (direction === 'prev' || direction === 'back') {
            sessionStorage.setItem('navDirection', 'back');
        } else {
            sessionStorage.setItem('navDirection', 'forward');
        }
        // Guardar estado del lightbox si está abierto
        var overlay = document.querySelector('.lightbox-overlay');
        if (overlay && overlay.classList.contains('active')) {
            sessionStorage.setItem('lightboxOpen', 'true');
        }
        window.location.href = url;
    };

    // Click delegation on nav links (prev/next posts)
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[aria-label="Post anterior"], a[aria-label="Siguiente post"]');
        if (!link) return;
        e.preventDefault();
        var dir = link.getAttribute('aria-label') === 'Post anterior' ? 'back' : 'forward';
        window.__navigateTo(link.href, dir);
    });
})();

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

        window.__navigateTo(link.href, isLeft ? 'back' : 'forward');
    });
})();

/* ─── Swipe Gesture Navigation (Prev/Next Post) ─── */
(function() {
    var touchStartX = 0;
    var touchStartY = 0;
    var touchStartTime = 0;
    var isHorizontalSwipe = false;

    document.addEventListener('touchstart', function(e) {
        var touch = e.changedTouches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        touchStartTime = Date.now();
        isHorizontalSwipe = false;
    }, { passive: true });

    document.addEventListener('touchmove', function(e) {
        if (isHorizontalSwipe) {
            e.preventDefault();
            return;
        }

        var touch = e.changedTouches[0];
        var deltaX = touch.clientX - touchStartX;
        var deltaY = touch.clientY - touchStartY;
        var elapsed = Date.now() - touchStartTime;

        if (elapsed > 400) return;

        // Si ya es claramente horizontal, marcar para bloquear scroll
        if (Math.abs(deltaX) > 30 && Math.abs(deltaX) > Math.abs(deltaY) * 2) {
            isHorizontalSwipe = true;
            e.preventDefault();
        }
    }, { passive: false });

    document.addEventListener('touchend', function(e) {
        // Ignorar si el lightbox está abierto
        var overlay = document.querySelector('.lightbox-overlay');
        if (overlay && overlay.classList.contains('active')) return;

        // Ignorar si el usuario está escribiendo
        var tag = e.target.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return;

        var touch = e.changedTouches[0];
        var deltaX = touch.clientX - touchStartX;
        var deltaY = touch.clientY - touchStartY;
        var elapsed = Date.now() - touchStartTime;

        // Umbral mínimo: 50px de recorrido horizontal, menos de 400ms
        if (Math.abs(deltaX) < 50 || elapsed > 400) return;

        // Debe ser claramente horizontal (horizontal > vertical * 2)
        if (Math.abs(deltaX) < Math.abs(deltaY) * 2) return;

        e.preventDefault();

        var label = deltaX > 0 ? 'Post anterior' : 'Siguiente post';
        var link = document.querySelector('a[aria-label="' + label + '"]');
        if (!link || !link.href) return;

        window.__navigateTo(link.href, deltaX > 0 ? 'back' : 'forward');
    }, { passive: false });
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
