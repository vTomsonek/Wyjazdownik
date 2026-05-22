<?php
/**
 * Layout dla trybu trasy (live route) - full-screen mobile-first.
 * Bez headera/footera/menu - ma byc 100vh dla mapy + bottom sheet.
 *
 * @var string      $content
 * @var string|null $title
 * @var string|null $description
 */
$title       = $title       ?? 'Tryb trasy - Wyjazdownik';
$description = $description ?? 'Mapa atrakcji w trasie.';
?><!DOCTYPE html>
<html lang="pl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#FF6B35">
    <meta name="robots" content="noindex, nofollow">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">

    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="stylesheet" href="<?= e(asset('assets/css/tailwind.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700&family=Inter:wght@400;600;700&display=swap">

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">

    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; overscroll-behavior: none; }
        body {
            font-family: Inter, system-ui, sans-serif;
            background: #FFF8F0;
            color: #1A1A2E;
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
        }
        html.dark body { background: #0F1419; color: #F0F4F8; }
    </style>
</head>
<body>
    <?= $content ?>
</body>
</html>
