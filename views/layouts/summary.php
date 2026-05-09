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

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary:   { DEFAULT: '#FF6B35', dark: '#E55A2B' },
                        secondary: '#2EC4B6',
                        accent:    '#FFD23F',
                        cream:     '#FFF8F0',
                        paper:     '#FFFFFF',
                        ink:       '#1A1A2E',
                        mist:      '#6B7280',
                        night:     '#0F1419',
                        deep:      '#1A2332',
                        pale:      '#F0F4F8',
                    },
                    fontFamily: {
                        display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
                        body:    ['Inter', 'system-ui', 'sans-serif'],
                        accent:  ['Caveat', 'cursive'],
                    },
                    screens: { '3xl': '1920px', '4xl': '2560px' },
                    boxShadow: { 'pop': '0 8px 24px -8px rgba(255, 107, 53, 0.35)', 'pop-lg': '0 20px 60px -16px rgba(255, 107, 53, 0.45)' },
                    animation: {
                        'float':       'float 4s ease-in-out infinite',
                        'float-slow':  'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } },
                    },
                },
            },
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Caveat:wght@400..700&family=Inter:wght@400..700&display=swap">

    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
</head>
<body class="font-body bg-cream text-ink dark:bg-night dark:text-pale min-h-screen flex flex-col antialiased">

    <main class="flex-1">
        <?= $content ?>
    </main>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/summary.js')) ?>"></script>

    <!-- Twemoji - jednolite emoji wszedzie (Windows, Linux, mobile) - flagi tez dzialaja na PC -->
    <script src="https://cdn.jsdelivr.net/npm/@twemoji/api@latest/dist/twemoji.min.js" crossorigin="anonymous"></script>
    <script>
        if (window.twemoji) {
            twemoji.parse(document.body, {
                folder: 'svg',
                ext: '.svg',
                base: 'https://cdn.jsdelivr.net/gh/jdecked/twemoji@latest/assets/'
            });
        }
    </script>
    <style>
        /* Twemoji obrazki - inline z tekstem, naturalny rozmiar */
        img.emoji {
            height: 1em;
            width: 1em;
            margin: 0 .05em 0 .1em;
            vertical-align: -0.1em;
            display: inline-block;
        }
    </style>
</body>
</html>
