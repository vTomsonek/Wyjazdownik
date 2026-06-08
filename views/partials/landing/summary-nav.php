<?php
/**
 * Nav dla strony podsumowania publicznego.
 * Logo + theme toggle + share button (copy URL) + presentation mode shortcut.
 */
$homeUrl = url('/');
?>
<header class="nav">
    <div class="wrap nav-inner">
        <a class="logo" href="<?= e($homeUrl) ?>">
            <span class="logo-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></span>
            <span class="logo-word">wyjazdown<span class="idot">ı</span>k<span class="tld">.pl</span></span>
        </a>
        <nav class="nav-links">
            <span style="color:var(--fg-3);font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
                <span class="iconify" data-icon="ph:eye-bold"></span> Tryb podsumowania
            </span>
        </nav>
        <div class="nav-cta">
            <button class="theme-toggle" id="themeToggle" aria-label="Przełącz motyw">
                <span class="iconify ic-dark" data-icon="ph:moon-stars-bold"></span>
                <span class="iconify ic-light" data-icon="ph:sun-bold"></span>
            </button>
            <button id="summary-share" class="btn btn-ghost" style="padding:11px 18px;font-size:14px" type="button" aria-label="Skopiuj link">
                <span class="iconify" data-icon="ph:share-network-bold"></span>
                <span class="lbl">Udostępnij</span>
            </button>
            <a class="btn btn-primary" href="#" id="summary-tv" style="padding:11px 20px;font-size:15px" aria-label="Tryb TV / prezentacji">
                <span class="iconify" data-icon="ph:television-simple-fill"></span>
                Tryb TV
            </a>
        </div>
    </div>
</header>
<script>
(function () {
    var btn = document.getElementById('summary-share');
    if (btn) {
        btn.addEventListener('click', function () {
            var url = window.location.href;
            var lbl = btn.querySelector('.lbl');
            var original = lbl ? lbl.textContent : '';
            navigator.clipboard.writeText(url).then(function () {
                if (lbl) { lbl.textContent = 'Skopiowano'; }
                btn.style.color = 'var(--green)';
                setTimeout(function () {
                    if (lbl) lbl.textContent = original;
                    btn.style.color = '';
                }, 1800);
            }).catch(function () {
                alert('Skopiuj ręcznie: ' + url);
            });
        });
    }
    // TV mode trigger - bezposrednio wywoluje window.wyjEnterPresentation() z summary.js.
    // Zachowuje user gesture chain dla requestFullscreen API (synthetic KeyboardEvent
    // ma isTrusted=false i przegladarki blokuja zen fullscreen).
    var tv = document.getElementById('summary-tv');
    if (tv) {
        tv.addEventListener('click', function (e) {
            e.preventDefault();
            if (typeof window.wyjEnterPresentation === 'function') {
                window.wyjEnterPresentation();
            }
        });
    }
})();
</script>
