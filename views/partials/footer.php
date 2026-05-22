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
                <svg class="w-5 h-5" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true">
                    <path d="M7.68533 0.333252C8.43533 0.335252 8.816 0.339252 9.14466 0.348585L9.274 0.353252C9.42333 0.358585 9.57066 0.365252 9.74866 0.373252C10.458 0.406585 10.942 0.518585 11.3667 0.683252C11.8067 0.852585 12.1773 1.08192 12.548 1.45192C12.887 1.78518 13.1493 2.18831 13.3167 2.63325C13.4813 3.05792 13.5933 3.54192 13.6267 4.25192C13.6347 4.42925 13.6413 4.57659 13.6467 4.72659L13.6507 4.85592C13.6607 5.18392 13.6647 5.56459 13.666 6.31459L13.6667 6.81192V7.68525C13.6683 8.17152 13.6632 8.65779 13.6513 9.14392L13.6473 9.27325C13.642 9.42325 13.6353 9.57059 13.6273 9.74792C13.594 10.4579 13.4807 10.9413 13.3167 11.3666C13.1498 11.8118 12.8874 12.215 12.548 12.5479C12.2146 12.8868 11.8115 13.1491 11.3667 13.3166C10.942 13.4813 10.458 13.5933 9.74866 13.6266C9.59048 13.634 9.43225 13.6407 9.274 13.6466L9.14466 13.6506C8.816 13.6599 8.43533 13.6646 7.68533 13.6659L7.188 13.6666H6.31533C5.82884 13.6683 5.34235 13.6632 4.856 13.6513L4.72667 13.6473C4.56841 13.6413 4.41018 13.6344 4.252 13.6266C3.54266 13.5933 3.05866 13.4813 2.63333 13.3166C2.18844 13.1495 1.78548 12.8872 1.45266 12.5479C1.11336 12.2148 0.850814 11.8116 0.683331 11.3666C0.518665 10.9419 0.406665 10.4579 0.373331 9.74792C0.365904 9.58973 0.359237 9.43151 0.353331 9.27325L0.349998 9.14392C0.337713 8.6578 0.332157 8.17153 0.333331 7.68525V6.31459C0.331471 5.82832 0.33636 5.34205 0.347998 4.85592L0.352665 4.72659C0.357998 4.57659 0.364665 4.42925 0.372665 4.25192C0.405998 3.54192 0.517998 3.05859 0.682665 2.63325C0.850074 2.18786 1.11314 1.7846 1.45333 1.45192C1.78606 1.1129 2.18876 0.850577 2.63333 0.683252C3.05866 0.518585 3.542 0.406585 4.252 0.373252C4.42933 0.365252 4.57733 0.358585 4.72667 0.353252L4.856 0.349252C5.34213 0.337407 5.8284 0.332295 6.31466 0.333919L7.68533 0.333252ZM7 3.66659C6.11594 3.66659 5.2681 4.01778 4.64298 4.6429C4.01785 5.26802 3.66666 6.11586 3.66666 6.99992C3.66666 7.88397 4.01785 8.73182 4.64298 9.35694C5.2681 9.98206 6.11594 10.3333 7 10.3333C7.88405 10.3333 8.7319 9.98206 9.35702 9.35694C9.98214 8.73182 10.3333 7.88397 10.3333 6.99992C10.3333 6.11586 9.98214 5.26802 9.35702 4.6429C8.7319 4.01778 7.88405 3.66659 7 3.66659ZM7 4.99992C7.26264 4.99988 7.52272 5.05156 7.76539 5.15203C8.00806 5.2525 8.22856 5.39978 8.41431 5.58547C8.60006 5.77116 8.74741 5.99161 8.84796 6.23425C8.94851 6.47688 9.00029 6.73694 9.00033 6.99959C9.00038 7.26223 8.94869 7.52231 8.84822 7.76498C8.74775 8.00765 8.60047 8.22815 8.41478 8.4139C8.2291 8.59965 8.00864 8.747 7.76601 8.84755C7.52337 8.9481 7.26331 8.99988 7.00067 8.99992C6.47023 8.99992 5.96152 8.78921 5.58645 8.41413C5.21138 8.03906 5.00067 7.53035 5.00067 6.99992C5.00067 6.46949 5.21138 5.96078 5.58645 5.58571C5.96152 5.21063 6.46957 4.99992 7 4.99992ZM10.5007 2.66659C10.2797 2.66659 10.0677 2.75438 9.91141 2.91066C9.75513 3.06694 9.66733 3.27891 9.66733 3.49992C9.66733 3.72093 9.75513 3.93289 9.91141 4.08917C10.0677 4.24546 10.2797 4.33325 10.5007 4.33325C10.7217 4.33325 10.9336 4.24546 11.0899 4.08917C11.2462 3.93289 11.334 3.72093 11.334 3.49992C11.334 3.27891 11.2462 3.06694 11.0899 2.91066C10.9336 2.75438 10.7217 2.66659 10.5007 2.66659Z"/>
                </svg>
            </a>
            <?php endif; ?>
            <?php if (!empty($_footerSocials['tiktok'])): ?>
            <a href="<?= e($_footerSocials['tiktok']) ?>" target="_blank" rel="noopener"
               class="text-mist hover:text-ink dark:hover:text-pale transition" aria-label="TikTok">
                <svg class="w-5 h-5" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true">
                    <path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/>
                </svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</footer>
