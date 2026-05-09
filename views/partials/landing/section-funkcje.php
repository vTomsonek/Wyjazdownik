<?php
/** Sekcja 4: Funkcje - "Wszystko czego potrzebuje ekipa" */
$features = [
    ['📅', 'Inteligentny kalendarz', 'Znajdzie terminy gdzie wszyscy mogą - heatmapa pokazuje gdzie ekipa się pokrywa.', 'primary'],
    ['💰', 'Wspólny budżet',         'Zobacz na ile stać ekipę realnie. Mediana, najwyższy, najniższy - wszystko na jednym wykresie.', 'secondary'],
    ['🗺️', 'Mapa pomysłów',          'Rysujcie trasy i pinezki, każdy widzi pomysły reszty - bez wymiany screenów.', 'accent'],
    ['🏆', 'Rankingi ekipy',         'Kto jest Kebab Masterem? Kto Rybką? Kto Krezusem? Algorytm przyznaje odznaki na podstawie odpowiedzi.', 'primary'],
    ['🎯', 'Brutalne wnioski',       '"Najsłabsze ogniwo" pokaże do czego trzeba się dostosować. Realistycznie, bez owijania w bawełnę.', 'secondary'],
    ['📺', 'Tryb prezentacji',       'Włączcie na TV i obejrzyjcie razem. Pełnoekranowy slideshow, nawigacja strzałkami.', 'accent'],
];
$colorMap = [
    'primary'   => 'bg-primary/10 text-primary',
    'secondary' => 'bg-secondary/10 text-secondary',
    'accent'    => 'bg-accent/20 text-amber-700 dark:text-accent',
];
?>
<section id="funkcje" class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 md:mb-16" data-animate>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-4">
                Wszystko czego potrzebuje ekipa
            </h2>
            <p class="text-lg md:text-xl text-mist max-w-2xl mx-auto">
                Sześć narzędzi, które razem zamieniają chaos w plan.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
            <?php foreach ($features as $i => [$icon, $title, $desc, $color]): ?>
            <div class="rounded-2xl bg-cream dark:bg-night border border-mist/10 p-6 hover:border-primary/40 hover:-translate-y-1 transition" data-animate data-animate-delay="<?= e(min(5, $i + 1)) ?>">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl text-2xl mb-4 <?= e($colorMap[$color]) ?>"><?= e($icon) ?></div>
                <h3 class="font-display font-bold text-lg md:text-xl mb-2 text-ink dark:text-pale"><?= e($title) ?></h3>
                <p class="text-mist text-sm md:text-base leading-relaxed"><?= e($desc) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
