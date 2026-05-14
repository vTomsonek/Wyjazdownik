<?php
/**
 * Layout wizarda uczestnika.
 *
 * @var string $content
 * @var string|null $title
 * @var string|null $description
 */
$title       = $title       ?? 'Wyjazdownik.pl';
$description = $description ?? 'Wypełnij ankietę wyjazdu.';
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

    <!-- Critical CSS inline + Tailwind async -->
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
    <link rel="preload" as="style" href="<?= e(asset('assets/css/tailwind.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>"></noscript>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    <link rel="stylesheet" media="print" onload="this.media='all'"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    <noscript>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700&family=Inter:wght@400;600;700&display=swap">
    </noscript>

    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
</head>
<body class="font-body bg-cream text-ink dark:bg-night dark:text-pale min-h-screen flex flex-col antialiased">

    <header class="sticky top-0 z-40 bg-cream/85 dark:bg-night/85 backdrop-blur border-b border-mist/15">
        <div class="mx-auto max-w-5xl 3xl:max-w-6xl flex items-center justify-between px-4 sm:px-6 lg:px-8 py-3 md:py-4">
            <a href="<?= e(url('/')) ?>"
               class="flex items-center gap-2 text-xl sm:text-2xl 3xl:text-3xl"
               aria-label="Wyjazdownik.pl">
                <?php require BASE_PATH . '/views/partials/logo.php'; ?>
            </a>
            <button type="button" id="theme-toggle"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full text-ink dark:text-pale hover:bg-primary/10 transition"
                    aria-label="Przełącz tryb jasny/ciemny">
                <svg class="w-5 h-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                </svg>
                <svg class="w-5 h-5 hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>
        </div>
    </header>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/wizard.js')) ?>"></script>

    <!-- Twemoji - asynchronicznie, parse po idle -->
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
