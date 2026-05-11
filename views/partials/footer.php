<?php
$_footerAdmin   = (new \App\Services\AuthService())->currentAdmin();
$_footerSocials = array_filter([
    'facebook'  => trim((string) config('social.facebook', '')),
    'instagram' => trim((string) config('social.instagram', '')),
    'tiktok'    => trim((string) config('social.tiktok', '')),
]);
?>
<footer class="mt-16 border-t border-mist/15 bg-cream dark:bg-night">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-6 text-sm text-mist">

        <div class="grid gap-4 sm:grid-cols-3 sm:items-center">

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

        <?php if (!empty($_footerSocials)): ?>
        <!-- Social media - pokazane tylko gdy linki ustawione w .env -->
        <div class="mt-5 pt-5 border-t border-mist/10 flex items-center justify-center gap-5">
            <?php if (!empty($_footerSocials['facebook'])): ?>
            <a href="<?= e($_footerSocials['facebook']) ?>" target="_blank" rel="noopener"
               class="text-mist hover:text-[#1877F2] transition" aria-label="Facebook">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>
            <?php endif; ?>
            <?php if (!empty($_footerSocials['instagram'])): ?>
            <a href="<?= e($_footerSocials['instagram']) ?>" target="_blank" rel="noopener"
               class="text-mist hover:text-[#E4405F] transition" aria-label="Instagram">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
            </a>
            <?php endif; ?>
            <?php if (!empty($_footerSocials['tiktok'])): ?>
            <a href="<?= e($_footerSocials['tiktok']) ?>" target="_blank" rel="noopener"
               class="text-mist hover:text-ink dark:hover:text-pale transition" aria-label="TikTok">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.84-.1z"/>
                </svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</footer>
