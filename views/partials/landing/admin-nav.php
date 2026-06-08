<?php
/**
 * Sticky nav dla zalogowanego admina.
 * Logo, theme toggle, greeting + Wyloguj.
 * @var \App\Models\Admin|null $admin
 */
use App\Services\AuthService;

if (!isset($admin) || $admin === null) {
    $admin = (new AuthService())->currentAdmin();
}
$homeUrl     = url('/');
$dashboardUrl = url('/admin');
$logoutUrl   = url('/admin/logout');
?>
<header class="nav">
    <div class="wrap nav-inner">
        <a class="logo" href="<?= e($dashboardUrl) ?>">
            <span class="logo-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></span>
            <span class="logo-word">wyjazdown<span class="idot">ı</span>k<span class="tld">.pl</span></span>
        </a>
        <nav class="nav-links">
            <a href="<?= e($dashboardUrl) ?>">Twoje wyjazdy</a>
            <a href="<?= e(url('/admin/trips/new')) ?>">Nowy wyjazd</a>
            <a href="<?= e($homeUrl) ?>">Strona główna</a>
        </nav>
        <div class="nav-cta">
            <button class="theme-toggle" id="themeToggle" aria-label="Przełącz motyw">
                <span class="iconify ic-dark" data-icon="ph:moon-stars-bold"></span>
                <span class="iconify ic-light" data-icon="ph:sun-bold"></span>
            </button>
            <?php if ($admin !== null): ?>
                <span class="nav-login" style="font-weight:600;color:var(--fg-2);">
                    Cześć, <b style="color:var(--heading);"><?= e($admin->name) ?></b>
                </span>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= e($logoutUrl) ?>" style="padding:11px 20px;font-size:14px">
                <span class="iconify" data-icon="ph:sign-out-bold"></span> Wyloguj
            </a>
        </div>
    </div>
</header>
