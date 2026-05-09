/**
 * Wyjazdownik.pl - global JS.
 * - Dark mode toggle
 * - Reduced-motion detection
 * - Intersection Observer dla [data-animate] (animacje wjazdu sekcji)
 * - Accordion FAQ ([data-faq-trigger] / [data-faq-panel])
 * - Smooth scroll dla linków [data-smooth-scroll]
 */

(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // 1. Dark mode toggle
    // -----------------------------------------------------------------------
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const root = document.documentElement;
            const isDark = root.classList.toggle('dark');
            try {
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            } catch (e) { /* noop */ }
        });
    }

    // -----------------------------------------------------------------------
    // 2. Reduced-motion detection
    // -----------------------------------------------------------------------
    const prefersReduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduce) {
        document.documentElement.setAttribute('data-reduced-motion', 'true');
    }

    // -----------------------------------------------------------------------
    // 3. Intersection Observer - sekcje wjeżdżają od dołu przy scrollowaniu
    //    Każdy element z atrybutem [data-animate] startuje niewidoczny
    //    (klasy w CSS) i dostaje klasę .is-visible gdy wchodzi w viewport.
    // -----------------------------------------------------------------------
    const animatables = document.querySelectorAll('[data-animate]');
    if (animatables.length > 0 && 'IntersectionObserver' in window && !prefersReduce) {
        const io = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.1,
        });
        animatables.forEach(function (el) { io.observe(el); });
    } else {
        // Fallback: pokazujemy od razu
        animatables.forEach(function (el) { el.classList.add('is-visible'); });
    }

    // -----------------------------------------------------------------------
    // 4. Accordion FAQ
    //    Struktura HTML:
    //      <div data-faq>
    //        <button data-faq-trigger>...</button>
    //        <div data-faq-panel>...</div>
    //      </div>
    // -----------------------------------------------------------------------
    document.querySelectorAll('[data-faq-trigger]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const wrapper = btn.closest('[data-faq]');
            if (!wrapper) return;
            const panel = wrapper.querySelector('[data-faq-panel]');
            const isOpen = wrapper.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (panel) {
                panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            }
        });
    });

    // -----------------------------------------------------------------------
    // 5. Smooth scroll dla linków [href^="#"]
    //    Uzupełnia scroll-behavior:smooth z CSS dla starszych przeglądarek
    //    i pozwala na customowy offset (np. żeby header nie zasłaniał targetu).
    // -----------------------------------------------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            const id = link.getAttribute('href');
            if (!id || id === '#') return;
            const target = document.querySelector(id);
            if (!target) return;
            e.preventDefault();
            const headerHeight = document.querySelector('header')?.offsetHeight ?? 0;
            const top = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 12;
            window.scrollTo({
                top: top,
                behavior: prefersReduce ? 'auto' : 'smooth',
            });
        });
    });

    // -----------------------------------------------------------------------
    // 6. Globalny obiekt - dla pluginów z kolejnych etapów
    // -----------------------------------------------------------------------
    window.Wyjazdownik = window.Wyjazdownik || {
        version: '0.2.0',
    };
})();
