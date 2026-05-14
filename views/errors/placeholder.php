<?php
/**
 * @var string $heading
 * @var string $message
 */
$heading = $heading ?? 'Wkrótce';
$message = $message ?? '';
?>
<section class="mx-auto max-w-2xl px-4 py-20 text-center">
    <div class="w-24 h-24 mx-auto mb-6">
        <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
    </div>
    <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
        Placeholder ETAPU 1
    </span>
    <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale mb-3">
        <?= e($heading) ?>
    </h1>
    <p class="text-lg text-mist mb-8">
        <?= e($message) ?>
    </p>
    <a href="<?= e(url('/')) ?>"
       class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-medium
              hover:bg-primary hover:scale-105 transition">
        Wróć na stronę główną
    </a>
</section>
