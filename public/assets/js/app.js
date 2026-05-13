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
