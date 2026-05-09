<?php /** @var string $title */ ?>
<section class="mx-auto max-w-2xl px-4 py-20 text-center">
    <div class="w-32 h-32 mx-auto mb-6">
        <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
    </div>
    <h1 class="font-display font-bold text-5xl text-ink dark:text-pale mb-3">404</h1>
    <p class="text-lg text-mist mb-6">
        Tej strony nie ma. Może wpisałeś zły adres albo link wygasł.
    </p>
    <a href="<?= e(url('/')) ?>"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary text-white font-medium
              hover:bg-primary-dark hover:scale-105 transition">
        Wróć na stronę główną
    </a>
</section>
