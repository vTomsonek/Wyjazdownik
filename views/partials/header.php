<?php
use App\Services\AuthService;

$_admin = (new AuthService())->currentAdmin();
?>
<header class="sticky top-0 z-40 bg-cream/85 dark:bg-night/85 backdrop-blur border-b border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] flex items-center justify-between px-4 sm:px-6 lg:px-8 py-3 md:py-4">
        <a href="<?= e(url('/')) ?>"
           class="flex items-center gap-2 group text-xl sm:text-2xl 3xl:text-3xl"
           aria-label="Wyjazdownik.pl - strona główna">
            <?php require BASE_PATH . '/views/partials/logo.php'; ?>
        </a>

        <nav class="flex items-center gap-2 sm:gap-3">
            <?php if ($_admin !== null): ?>
                <a href="<?= e(url('/admin')) ?>"
                   class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm
                          text-ink dark:text-pale hover:bg-primary/10 transition">
                    Panel admina
                </a>
                <a href="<?= e(url('/admin/logout')) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm
                          text-ink dark:text-pale hover:bg-primary/10 transition">
                    Wyloguj
                </a>
            <?php else: ?>
                <a href="<?= e(url('/admin/login')) ?>"
                   class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm
                          text-ink dark:text-pale hover:bg-primary/10 transition">
                    Zaloguj się
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
