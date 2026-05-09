<?php
/**
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 */
?>
<section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-12 md:py-20 3xl:py-28 text-center">

    <!-- Konfetti canvas -->
    <canvas id="confetti-canvas" class="fixed inset-0 pointer-events-none z-0" aria-hidden="true"></canvas>

    <div class="relative z-10">
        <div class="w-32 h-32 md:w-40 md:h-40 mx-auto mb-6 animate-float-slow">
            <?php require BASE_PATH . '/views/partials/mascot.php'; ?>
        </div>

        <span class="inline-block mb-4 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/15 text-secondary">
            ✓ Gotowe
        </span>

        <h1 class="font-display font-bold text-4xl md:text-6xl 3xl:text-7xl text-ink dark:text-pale mb-4">
            Dzięki, <?= e($participant->nickname) ?>!
        </h1>

        <p class="text-lg md:text-xl text-mist max-w-xl mx-auto mb-8 leading-relaxed">
            Twoja ankieta dla <strong class="text-ink dark:text-pale"><?= e($trip->name) ?></strong> została wysłana.
            Reszta ekipy wypełnia swoje, a potem włączacie razem podsumowanie na TV.
        </p>

        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6 max-w-md mx-auto mb-6 text-left">
            <p class="text-sm text-mist mb-2">📌 Możesz wrócić tu w każdej chwili</p>
            <p class="text-xs font-mono text-ink dark:text-pale break-all bg-cream dark:bg-night px-3 py-2 rounded-lg">
                <?= e(url('/p/' . $participant->accessToken)) ?>
            </p>
            <p class="mt-2 text-xs text-mist">
                Zachowaj ten link - możesz edytować odpowiedzi gdy dojdzie ci coś do głowy.
            </p>
        </div>

        <a href="<?= e(url('/p/' . $participant->accessToken)) ?>"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
            Zobacz moje odpowiedzi
        </a>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        if (typeof confetti !== 'function') return;

        const burst = (origin) => confetti({
            particleCount: 80,
            spread: 70,
            startVelocity: 35,
            origin: origin,
            colors: ['#FF6B35', '#FFD23F', '#2EC4B6', '#FFFFFF']
        });

        burst({ x: 0.5, y: 0.4 });
        setTimeout(() => burst({ x: 0.2, y: 0.5 }), 250);
        setTimeout(() => burst({ x: 0.8, y: 0.5 }), 500);
    });
</script>
