<?php
/**
 * Layout panelu admina - landing v2 design.
 * Uzywa landing.css + landing.js (z theme toggle FAQ accordion scroll reveal),
 * + dedicated admin-nav z greeting + logout.
 *
 * @var string      $content
 * @var string|null $title
 * @var string|null $description
 */
use App\Services\AuthService;

$title       = $title       ?? 'Panel admina - Wyjazdownik.pl';
$description = $description ?? 'Panel administratora Wyjazdownik.pl';
$admin       = (new AuthService())->currentAdmin();
$canonical   = (string) url($_SERVER['REQUEST_URI'] ?? '/');
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <meta name="robots" content="noindex, nofollow">

    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">

    <!-- Theme init przed CSS - anti-FOUC -->
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

    <script src="https://code.iconify.design/3/3.1.1/iconify.min.js" defer></script>
</head>
<body>

    <?php require BASE_PATH . '/views/partials/landing/admin-nav.php'; ?>

    <main>
        <?= $content ?>
    </main>

    <?php require BASE_PATH . '/views/partials/landing/footer.php'; ?>

    <script src="<?= e(asset('assets/js/landing.js')) ?>" defer></script>
</body>
</html>
