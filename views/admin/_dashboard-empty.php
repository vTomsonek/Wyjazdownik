<?php /** Pusty stan dashboardu */ ?>
<div class="rounded-3xl bg-paper dark:bg-deep border-2 border-dashed border-mist/30 p-8 md:p-12 text-center">
    <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-4 animate-float-slow">
        <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
    </div>
    <h2 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale mb-3">
        Nie masz jeszcze wyjazdów
    </h2>
    <p class="text-mist max-w-md mx-auto mb-6">
        Czas to zmienić. Stwórz pierwszy wyjazd, dodaj ekipę i wyślij im linki - reszta zrobi się sama.
    </p>
    <a href="<?= e(url('/admin/trips/new')) ?>"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-105 transition">
        Stwórz pierwszy wyjazd
    </a>
</div>
