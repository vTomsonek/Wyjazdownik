<?php
/**
 * Layout strony podsumowania - TV friendly.
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

    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) { /* noop */ }
        })();
    </script>

    <!-- Critical CSS inline - zapobiega FOUC do czasu zaladowania tailwind.css -->
    <style>
        *,::before,::after{box-sizing:border-box;border:0 solid #e5e7eb}
        html{line-height:1.5;-webkit-text-size-adjust:100%;text-size-adjust:100%;scroll-behavior:smooth}
        body{margin:0;min-height:100vh;display:flex;flex-direction:column;
             font-family:Inter,system-ui,sans-serif;-webkit-font-smoothing:antialiased;
             background:#FFF8F0;color:#1A1A2E}
        html.dark body{background:#0F1419;color:#F0F4F8}
        main{flex:1 1 0%}
        h1,h2,h3{font-family:"Bricolage Grotesque",system-ui,sans-serif;font-weight:700;margin:0}
        img,svg{display:block;max-width:100%;height:auto}
    </style>

    <!-- Tailwind - production build async -->
    <link rel="preload" as="style" href="<?= e(asset('assets/css/tailwind.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>"></noscript>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Caveat:wght@500;700&family=Inter:wght@400;600;700&display=swap">
    <link rel="stylesheet" media="print" onload="this.media='all'"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Caveat:wght@500;700&family=Inter:wght@400;600;700&display=swap">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Caveat:wght@500;700&family=Inter:wght@400;600;700&display=swap">
    </noscript>

    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
</head>
<body class="font-body bg-cream text-ink dark:bg-night dark:text-pale min-h-screen flex flex-col antialiased">

    <main class="flex-1">
        <?= $content ?>
    </main>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/summary.js')) ?>"></script>

    <!-- Twemoji - asynchronicznie, parse po idle (nie blokuje FCP/LCP) -->
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
</body>
</html>
