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
