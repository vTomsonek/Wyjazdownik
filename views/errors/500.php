<?php /** @var string $title */ ?>
<section class="mx-auto max-w-2xl px-4 py-20 text-center">
    <div class="w-32 h-32 mx-auto mb-6 opacity-60">
        <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
    </div>
    <h1 class="font-display font-bold text-5xl text-ink dark:text-pale mb-3">500</h1>
    <p class="text-lg text-mist mb-6">
        Coś się posypało po naszej stronie. Spróbuj za chwilę albo daj znać administratorowi.
    </p>
    <a href="<?= e(url('/')) ?>"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-medium
              hover:bg-primary hover:scale-105 transition">
        Wróć na stronę główną
    </a>
</section>
