(function () {
    'use strict';
    var sections = document.querySelectorAll('section');
    if (sections.length === 0) return;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!reduce && 'IntersectionObserver' in window) {
        sections.forEach(function (s) { s.setAttribute('data-summary-animate', ''); });
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('summary-visible');
                    obs.unobserve(e.target);
                }
            });
        }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });
        sections.forEach(function (s) { io.observe(s); });
    } else {
        sections.forEach(function (s) { s.classList.add('summary-visible'); });
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'present-btn';
    btn.title = 'Tryb prezentacji (F)';
    btn.className = 'present-fab';
    btn.textContent = 'Tryb prezentacji';
    document.body.appendChild(btn);

    var active = false;
    function enter() {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(function () {});
        }
        active = true;
        document.body.classList.add('presentation-mode');
        scrollToIdx(currentIdx());
    }
    function leave() {
        active = false;
        document.body.classList.remove('presentation-mode');
    }
    document.addEventListener('fullscreenchange', function () {
        if (!document.fullscreenElement) leave();
    });
    btn.addEventListener('click', enter);

    function currentIdx() {
        var idx = 0, best = Infinity;
        sections.forEach(function (s, i) {
            var d = Math.abs(s.getBoundingClientRect().top);
            if (d < best) { best = d; idx = i; }
        });
        return idx;
    }
    function scrollToIdx(i) {
        i = Math.max(0, Math.min(sections.length - 1, i));
        sections[i].scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    }

    document.addEventListener('keydown', function (e) {
        if (e.target.matches('input, textarea, select')) return;
        if (e.key === 'f' || e.key === 'F') {
            e.preventDefault();
            if (active && document.exitFullscreen) document.exitFullscreen();
            else enter();
        }
        if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ') {
            e.preventDefault(); scrollToIdx(currentIdx() + 1);
        }
        if (e.key === 'ArrowUp' || e.key === 'PageUp') {
            e.preventDefault(); scrollToIdx(currentIdx() - 1);
        }
        if (e.key === 'Home') { e.preventDefault(); scrollToIdx(0); }
        if (e.key === 'End')  { e.preventDefault(); scrollToIdx(sections.length - 1); }
    });
})();
