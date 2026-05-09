<?php
/**
 * Layout panelu admina.
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
<html lang="pl" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    },
                    screens: { '3xl': '1920px', '4xl': '2560px' },
                    boxShadow: { 'pop': '0 8px 24px -8px rgba(255, 107, 53, 0.35)' },
                },
            },
        };
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=Inter:wght@400..700&display=swap">

    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('assets/img/favicon.svg')) ?>">
</head>
<body class="font-body bg-cream text-ink dark:bg-night dark:text-pale min-h-screen flex flex-col antialiased">

    <header class="sticky top-0 z-40 bg-cream/85 dark:bg-night/85 backdrop-blur border-b border-mist/15">
        <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] flex items-center justify-between px-4 sm:px-6 lg:px-8 py-3 md:py-4">
            <a href="<?= e(url($admin ? '/admin' : '/')) ?>"
               class="flex items-center gap-2 text-xl sm:text-2xl 3xl:text-3xl">
                <?php require BASE_PATH . '/views/partials/logo.php'; ?>
            </a>

            <nav class="flex items-center gap-2 sm:gap-3">
                <?php if ($admin !== null): ?>
                    <span class="hidden sm:inline text-sm text-mist">
                        Cześć, <strong class="text-ink dark:text-pale"><?= e($admin->name) ?></strong>
                    </span>
                    <a href="<?= e(url('/admin/logout')) ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm
                              text-ink dark:text-pale hover:bg-primary/10 transition">
                        Wyloguj
                    </a>
                <?php else: ?>
                    <a href="<?= e(url('/')) ?>"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm
                              text-ink dark:text-pale hover:bg-primary/10 transition">
                        ← Strona główna
                    </a>
                <?php endif; ?>

                <button type="button" id="theme-toggle"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full
                               text-ink dark:text-pale hover:bg-primary/10 transition"
                        aria-label="Przełącz tryb jasny/ciemny">
                    <svg class="w-5 h-5 dark:hidden" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                    </svg>
                    <svg class="w-5 h-5 hidden dark:block" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <footer class="border-t border-mist/15 py-6 mt-12">
        <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 text-sm text-mist text-center">
            Wyjazdownik.pl &middot; panel admina &middot; <?= date('Y') ?>
        </div>
    </footer>

    <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
