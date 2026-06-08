<?php /** Pusty stan dashboardu - landing v2 design */ ?>
<div class="admin-empty">
    <div class="empty-mark"><span class="iconify" data-icon="ph:airplane-tilt-fill"></span></div>
    <h2>Nie masz jeszcze wyjazdów</h2>
    <p>Czas to zmienić. Stwórz pierwszy wyjazd, dodaj ekipę i wyślij im linki. Reszta zrobi się sama.</p>
    <a href="<?= e(url('/admin/trips/new')) ?>" class="btn btn-primary btn-lg">
        <span class="iconify" data-icon="ph:plus-bold"></span> Stwórz pierwszy wyjazd
    </a>
</div>
