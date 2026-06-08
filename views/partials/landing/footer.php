<?php
/** Landing v2 — dark footer */
$loginUrl     = url('/admin/login');
$homeUrl      = url('/');
$contactEmail = (string) config('mail.contact', 'admin@wyjazdownik.pl');
?>
<footer class="footer">
    <div class="wrap">
        <div class="foot-top">
            <a class="logo" href="<?= e($homeUrl) ?>" style="color:var(--on-dark)">
                <span class="logo-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></span>
                <span class="logo-word">wyjazdown<span class="idot">ı</span>k<span class="tld">.pl</span></span>
            </a>
            <nav class="foot-links">
                <a href="<?= e($homeUrl) ?>">Start</a>
                <a href="<?= e($loginUrl) ?>">Zaloguj</a>
                <a href="mailto:<?= e($contactEmail) ?>">Kontakt</a>
            </nav>
            <div class="foot-social">
                <a href="https://www.facebook.com/wyjazdownik" target="_blank" rel="noopener" aria-label="Facebook"><span class="iconify" data-icon="simple-icons:facebook"></span></a>
                <a href="https://www.instagram.com/wyjazdownik.pl/" target="_blank" rel="noopener" aria-label="Instagram"><span class="iconify" data-icon="simple-icons:instagram"></span></a>
                <a href="https://www.tiktok.com/@wyjazdownik.pl" target="_blank" rel="noopener" aria-label="TikTok"><span class="iconify" data-icon="simple-icons:tiktok"></span></a>
            </div>
        </div>
        <div class="foot-base">
            <span class="foot-copy">© 2026 <b>wyjazdownik.pl</b> - zrobione dla ekip, nie dla korpo<span class="iconify foot-heart" data-icon="ph:heart-fill"></span></span>
            <a class="foot-author" href="https://github.com/vTomsonek" target="_blank" rel="noopener">
                <svg class="gh-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0a12 12 0 0 0-3.79 23.39c.6.11.82-.26.82-.58v-2.04c-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.21.09 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.66-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.31-.54-1.52.12-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6 0c2.29-1.55 3.3-1.23 3.3-1.23.66 1.66.25 2.87.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.62-5.49 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.7.83.58A12 12 0 0 0 12 0z"></path></svg>
                vTomsonek
            </a>
        </div>
    </div>
</footer>
