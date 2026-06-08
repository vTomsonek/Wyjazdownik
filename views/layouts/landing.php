<?php
/**
 * Layout dla nowego landingu (v2).
 * Iconify CDN dla Phosphor + simple-icons.
 * Theme persisted w localStorage['wyj-theme'] (inicjalizowane w <head> przed CSS).
 *
 * @var string      $content
 * @var string|null $title
 * @var string|null $description
 */
$title       = $title       ?? 'Wyjazdownik.pl - ogarnij wakacje ze znajomymi raz na zawsze';
$description = $description ?? 'Polskie narzędzie do uzgadniania wspólnych wakacji w ekipie. Każdy wypełnia ankietę, a wy razem oglądacie wspólny plan na telewizorze.';
$canonical   = (string) url($_SERVER['REQUEST_URI'] ?? '/');
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <meta name="description" content="<?= e($description) ?>">
    <title><?= e($title) ?></title>

    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Wyjazdownik.pl">
    <meta property="og:locale" content="pl_PL">
    <meta property="og:image" content="<?= e(asset('assets/img/og-image.png')) ?>">

    <!-- Theme init przed CSS - bez flash. localStorage['wyj-theme'] (z app.js). -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('wyj-theme');
                if (t === 'dark' || t === 'light') {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://api.iconify.design" crossorigin>

    <link rel="stylesheet" href="<?= e(asset('assets/css/landing.css')) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="256x256" href="<?= e(asset('assets/img/logo-256.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(asset('assets/img/logo-256.png')) ?>">

    <!-- Iconify dla Phosphor (ph:) i simple-icons (simple-icons:) -->
    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js" defer></script>
</head>
<body>

    <?= $content ?>

    <script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
</body>
</html>
