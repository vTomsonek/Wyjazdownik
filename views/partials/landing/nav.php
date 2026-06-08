<?php
/** Landing v2 — sticky top nav */
$loginUrl = url('/admin/login');
$homeUrl  = url('/');
?>
<header class="nav">
    <div class="wrap nav-inner">
        <a class="logo" href="<?= e($homeUrl) ?>">
            <span class="logo-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></span>
            <span class="logo-word">wyjazdown<span class="idot">ı</span>k<span class="tld">.pl</span></span>
        </a>
        <nav class="nav-links">
            <a href="#problem">Problem</a>
            <a href="#jak-dziala">Jak działa</a>
            <a href="#funkcje">Funkcje</a>
            <a href="#odznaki">Odznaki</a>
            <a href="#faq">FAQ</a>
        </nav>
        <div class="nav-cta">
            <button class="theme-toggle nav-theme-desktop" id="themeToggle" aria-label="Przełącz motyw">
                <span class="iconify ic-dark" data-icon="ph:moon-stars-bold"></span>
                <span class="iconify ic-light" data-icon="ph:sun-bold"></span>
            </button>
            <a class="nav-login" href="<?= e($loginUrl) ?>">Zaloguj się</a>
            <a class="btn btn-primary nav-cta-btn" href="<?= e($loginUrl) ?>" style="padding:11px 20px;font-size:15px">Załóż wyjazd</a>
            <!-- Hamburger - widoczny tylko na mobile -->
            <button class="nav-burger" id="navBurgerToggle" aria-label="Menu" aria-expanded="false" aria-controls="mobile-drawer">
                <span class="iconify nav-burger-open" data-icon="ph:list-bold"></span>
                <span class="iconify nav-burger-close" data-icon="ph:x-bold"></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile drawer - slide-in z prawej -->
<div class="nav-drawer-overlay" id="navDrawerOverlay" hidden></div>
<aside class="nav-drawer" id="navDrawer" hidden aria-label="Menu mobilne">
    <div class="nav-drawer-header">
        <a class="logo" href="<?= e($homeUrl) ?>">
            <span class="logo-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></span>
            <span class="logo-word">wyjazdown<span class="idot">ı</span>k<span class="tld">.pl</span></span>
        </a>
        <button class="nav-drawer-close" id="navDrawerClose" aria-label="Zamknij menu">
            <span class="iconify" data-icon="ph:x-bold"></span>
        </button>
    </div>
    <nav class="nav-drawer-links">
        <a href="#problem" data-drawer-link><span class="iconify" data-icon="ph:warning-circle-bold"></span> Problem</a>
        <a href="#jak-dziala" data-drawer-link><span class="iconify" data-icon="ph:lightbulb-bold"></span> Jak działa</a>
        <a href="#funkcje" data-drawer-link><span class="iconify" data-icon="ph:sparkle-bold"></span> Funkcje</a>
        <a href="#odznaki" data-drawer-link><span class="iconify" data-icon="ph:trophy-bold"></span> Odznaki</a>
        <a href="#faq" data-drawer-link><span class="iconify" data-icon="ph:question-bold"></span> FAQ</a>
    </nav>
    <div class="nav-drawer-theme">
        <button class="nav-drawer-theme-btn" id="themeToggleDrawer" aria-label="Przełącz motyw">
            <span class="iconify nav-drawer-theme-icon ic-dark" data-icon="ph:moon-stars-bold"></span>
            <span class="iconify nav-drawer-theme-icon ic-light" data-icon="ph:sun-bold"></span>
            <span class="nav-drawer-theme-label">Tryb <span data-theme-label>jasny</span></span>
        </button>
    </div>
    <div class="nav-drawer-cta">
        <a class="btn btn-ghost" href="<?= e($loginUrl) ?>" data-drawer-link>
            <span class="iconify" data-icon="ph:sign-in-bold"></span>
            Zaloguj się
        </a>
        <a class="btn btn-primary" href="<?= e($loginUrl) ?>" data-drawer-link>
            <span class="iconify" data-icon="ph:plus-bold"></span>
            Załóż wyjazd
        </a>
    </div>
</aside>
