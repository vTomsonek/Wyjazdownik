<?php
/**
 * Layout strony podsumowania - landing v2 design.
 * Tailwind nadal wczytany dla utility classes uzywanych w sekcjach,
 * ale dominujace style/tokeny pochodza z landing.css.
 *
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 */
$title       = $title       ?? 'Podsumowanie - Wyjazdownik';
$description = $description ?? 'Wspolny plan ekipy.';
$canonical   = (string) url($_SERVER['REQUEST_URI'] ?? '/');
?><!DOCTYPE html>
<html lang="pl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <meta name="robots" content="noindex, nofollow">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Wyjazdownik.pl">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:image" content="<?= e(asset('assets/img/og-image.png')) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e(asset('assets/img/og-image.png')) ?>">

    <!-- Theme init - dual: starszy 'theme' + nowy 'wyj-theme'. Anti-FOUC. -->
    <script>
        (function () {
            try {
                var wyj = localStorage.getItem('wyj-theme');
                var old = localStorage.getItem('theme');
                var prefers = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var isDark = wyj === 'dark' || old === 'dark' || (!wyj && !old && prefers);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Critical CSS inline - landing v2 palette, zapobiega FOUC -->
    <style>
        *,::before,::after{box-sizing:border-box;border:0 solid #e5e7eb}
        html{line-height:1.5;-webkit-text-size-adjust:100%;text-size-adjust:100%;scroll-behavior:smooth}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;
             font-family:"Plus Jakarta Sans",system-ui,sans-serif;-webkit-font-smoothing:antialiased;
             background:#FFF8F3;color:#2C2440}
        html.dark body{background:#14101F;color:#E9E3F3}
        main{flex:1 1 0%}
        h1,h2,h3,h4{font-family:"Bricolage Grotesque",system-ui,sans-serif;font-weight:700;letter-spacing:-0.02em;margin:0;color:#211733}
        html.dark h1,html.dark h2,html.dark h3,html.dark h4{color:#FBF4EE}
        img,svg{display:block;max-width:100%;height:auto}
        ::selection{background:#FF6B35;color:#fff}
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://api.iconify.design" crossorigin>

    <!-- Tailwind production - dla utility classes uzywanych w 17 sekcjach (grid/spacing/responsive) -->
    <link rel="preload" as="style" href="<?= e(asset('assets/css/tailwind.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>"></noscript>

    <!-- Landing v2 system - tokeny, komponenty, motywy. LADUJE SIE PO Tailwind = wygrywa specificity. -->
    <link rel="stylesheet" href="<?= e(asset('assets/css/landing.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <!-- Cienki overlay - mapowanie pozostalych Tailwind classes na landing tokeny -->
    <link rel="stylesheet" href="<?= e(asset('assets/css/summary-overlay.css')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(asset('assets/img/logo-256.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/img/logo-256.png')) ?>">

    <!-- Iconify - dla Phosphor + simple-icons w nav/footer -->
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js" defer></script>
</head>
<body>

    <?php require BASE_PATH . '/views/partials/landing/summary-nav.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require BASE_PATH . '/views/partials/landing/footer.php'; ?>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/summary.js')) ?>"></script>
    <!-- Landing v2 interactions (theme toggle, FAQ accordion - drugi nie wystepuje w summary, ale OK) -->
    <script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>

    <!-- Twemoji - asynchronicznie -->
    <style>
        img.emoji { height: 1em; width: 1em; margin: 0 .05em 0 .1em; vertical-align: -0.1em; display: inline-block; }
    </style>
    <script>
        (function () {
            const run = () => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js';
                s.crossOrigin = 'anonymous';
                s.async = true;
                s.onload = () => window.twemoji && twemoji.parse(document.body, {
                    folder: 'svg', ext: '.svg',
                    base: 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/'
                });
                document.head.appendChild(s);
            };
            if (document.readyState === 'complete') {
                ('requestIdleCallback' in window) ? requestIdleCallback(run, { timeout: 2000 }) : setTimeout(run, 1);
            } else {
                window.addEventListener('load', () => {
                    ('requestIdleCallback' in window) ? requestIdleCallback(run, { timeout: 2000 }) : setTimeout(run, 1);
                }, { once: true });
            }
        })();
    </script>

    <!-- Sync starsza klasa .dark <-> data-theme na zmiany z landing.js -->
    <script>
    (function () {
        var html = document.documentElement;
        var mo = new MutationObserver(function () {
            if (html.getAttribute('data-theme') === 'dark') html.classList.add('dark');
            else html.classList.remove('dark');
        });
        mo.observe(html, { attributes: true, attributeFilter: ['data-theme'] });
    })();
    </script>
</body>
</html>
