/* Wyjazdownik landing — light interactions
   Source: design_handoff_wyjazdownik/app.js (handoff 1:1).
   - Theme toggle (persisted in localStorage['wyj-theme'])
   - FAQ accordion (single-open)
   - Scroll reveal (IntersectionObserver) with subtle stagger
*/
(function () {
    var root = document.documentElement;
    var saved = null;
    try { saved = localStorage.getItem('wyj-theme'); } catch (e) {}
    if (saved === 'dark' || saved === 'light') root.setAttribute('data-theme', saved);

    // Theme toggle - obsluga obu buttonow (desktop nav + drawer mobile)
    var updateThemeLabel = function () {
        var dark = root.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('[data-theme-label]').forEach(function (el) {
            el.textContent = dark ? 'jasny' : 'ciemny';
        });
    };
    var toggleTheme = function () {
        var dark = root.getAttribute('data-theme') === 'dark';
        if (dark) { root.removeAttribute('data-theme'); }
        else { root.setAttribute('data-theme', 'dark'); }
        try { localStorage.setItem('wyj-theme', dark ? 'light' : 'dark'); } catch (e) {}
        updateThemeLabel();
    };
    ['themeToggle', 'themeToggleDrawer'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('click', toggleTheme);
    });
    updateThemeLabel();

    // FAQ accordion
    document.querySelectorAll('.qa-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var qa = btn.closest('.qa');
            var ans = qa.querySelector('.qa-a');
            var open = qa.classList.contains('open');
            document.querySelectorAll('.qa.open').forEach(function (o) {
                if (o !== qa) { o.classList.remove('open'); o.querySelector('.qa-a').style.maxHeight = null; }
            });
            if (open) { qa.classList.remove('open'); ans.style.maxHeight = null; }
            else { qa.classList.add('open'); ans.style.maxHeight = ans.scrollHeight + 'px'; }
        });
    });

    // Logo click: jezeli juz na tej samej stronie, smooth scroll do gory (y=0).
    // Inaczej natywna nawigacja na home przez href.
    document.querySelectorAll('a.logo').forEach(function (a) {
        a.addEventListener('click', function (e) {
            try {
                var href = a.getAttribute('href') || '';
                var url  = new URL(href, window.location.origin);
                if (url.pathname === window.location.pathname && url.host === window.location.host) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch (err) {}
        });
    });

    // Mobile drawer (hamburger menu)
    var burger = document.getElementById('navBurgerToggle');
    var drawer = document.getElementById('navDrawer');
    var overlay = document.getElementById('navDrawerOverlay');
    var drawerClose = document.getElementById('navDrawerClose');

    if (burger && drawer && overlay) {
        var openDrawer = function () {
            drawer.hidden = false;
            overlay.hidden = false;
            // Force reflow zeby transition zadzialal
            void drawer.offsetWidth;
            document.body.classList.add('drawer-open');
            burger.setAttribute('aria-expanded', 'true');
        };
        var closeDrawer = function () {
            document.body.classList.remove('drawer-open');
            burger.setAttribute('aria-expanded', 'false');
            // Po transition ukryj completely
            setTimeout(function () {
                if (!document.body.classList.contains('drawer-open')) {
                    drawer.hidden = true;
                    overlay.hidden = true;
                }
            }, 300);
        };

        burger.addEventListener('click', function () {
            if (document.body.classList.contains('drawer-open')) closeDrawer();
            else openDrawer();
        });

        if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);

        // Zamknij po kliknieciu w linki nawigacji
        document.querySelectorAll('[data-drawer-link]').forEach(function (link) {
            link.addEventListener('click', closeDrawer);
        });

        // ESC zamyka
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('drawer-open')) closeDrawer();
        });
    }

    // Scroll reveal
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal').forEach(function (el, i) {
            var d = (i % 6) * 60;
            el.style.transitionDelay = d + 'ms';
            io.observe(el);
        });
    } else {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
    }
})();
