<?php
$_footerAdmin = (new \App\Services\AuthService())->currentAdmin();
?>
<footer class="mt-16 border-t border-mist/15 bg-cream dark:bg-night">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-6
                grid gap-4 sm:grid-cols-3 sm:items-center text-sm text-mist">

        <!-- Lewo: logo + rok -->
        <div class="flex items-center gap-3 justify-center sm:justify-start">
            <span class="text-base"><?php require BASE_PATH . '/views/partials/logo.php'; ?></span>
            <span class="text-mist/60">·</span>
            <span>&copy; <?= date('Y') ?></span>
        </div>

        <!-- Srodek: nawigacja -->
        <nav class="flex items-center justify-center gap-5">
            <a href="<?= e(url('/')) ?>" class="hover:text-primary transition">Start</a>
            <?php if ($_footerAdmin !== null): ?>
                <a href="<?= e(url('/admin')) ?>" class="hover:text-primary transition">Panel</a>
                <a href="<?= e(url('/admin/logout')) ?>" class="hover:text-primary transition">Wyloguj</a>
            <?php else: ?>
                <a href="<?= e(url('/admin/login')) ?>" class="hover:text-primary transition">Zaloguj</a>
            <?php endif; ?>
            <a href="mailto:<?= e(env('CONTACT_EMAIL', 'admin@wyjazdownik.pl')) ?>"
               class="hover:text-primary transition">Kontakt</a>
        </nav>

        <!-- Prawo: wersja + github -->
        <div class="flex items-center gap-4 justify-center sm:justify-end">
            <span class="font-mono text-xs underline-offset-2 underline decoration-mist/40">v<?= e(config('app.version', '1.0.0')) ?></span>
            <a href="https://github.com/vTomsonek" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 hover:text-primary transition"
               aria-label="vTomsonek na GitHub">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 0a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58v-2.04c-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.21.09 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.31-.54-1.52.12-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.29-1.55 3.3-1.23 3.3-1.23.66 1.66.25 2.87.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.62-5.49 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.7.83.58A12 12 0 0 0 12 0z"/>
                </svg>
                <span>vTomsonek</span>
            </a>
        </div>
    </div>
</footer>
