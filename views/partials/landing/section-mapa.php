<?php
/** Sekcja dedykowana wspolnej mapie atrakcji - feature highlight */
$mapFeatures = [
    [
        'icon'  => '🔍',
        'title' => 'Wyszukiwanie z Google',
        'desc'  => 'Każdy dodaje miejsca przez autocomplete - pełna baza Google Places. Adres, zdjęcia, kategoria - wszystko podstawia się samo.',
        'color' => 'primary',
    ],
    [
        'icon'  => '📸',
        'title' => 'Zdjęcia, wideo, linki',
        'desc'  => 'Każde miejsce ma swoją galerię. Wrzuć swoje zdjęcia z wcześniejszej wizyty, link do bloga, wideo z drona. Wszyscy widzą.',
        'color' => 'secondary',
    ],
    [
        'icon'  => '⭐',
        'title' => 'Oceny ekipy (półgwiazdki)',
        'desc'  => 'Każdy ocenia pomysły reszty 0,5-5,0★. Mini-wizard do szybkiego oceniania serii miejsc. Od razu widać konsensus.',
        'color' => 'accent',
    ],
    [
        'icon'  => '🚗',
        'title' => 'AI propozycje tras',
        'desc'  => 'Algorytm klastruje miejsca po lokalizacji, liczy round-trip od punktu startowego, uwzględnia czas zwiedzania każdej atrakcji.',
        'color' => 'primary',
    ],
];
$colorMap = [
    'primary'   => 'bg-primary/10 text-primary',
    'secondary' => 'bg-secondary/10 text-secondary',
    'accent'    => 'bg-accent/20 text-amber-700 dark:text-accent',
];
?>
<section id="mapa-atrakcji" class="py-16 md:py-24 3xl:py-32 bg-gradient-to-br from-cream via-cream to-primary/5 dark:from-night dark:via-night dark:to-primary/10">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 md:mb-16" data-animate>
            <span class="inline-block mb-4 px-3 py-1 rounded-full text-xs font-semibold bg-primary/15 text-primary-deep dark:text-primary">Najnowsza funkcja</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-4">
                🗺️ Wspólna mapa atrakcji
            </h2>
            <p class="text-lg md:text-xl text-mist max-w-3xl mx-auto leading-relaxed">
                Koniec z wymianą screenshotów z Google Maps na grupie. Każdy z ekipy dodaje miejsca, wrzuca zdjęcia i ocenia pomysły reszty - a algorytm sam zaproponuje optymalne trasy.
            </p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 mb-10">
            <?php foreach ($mapFeatures as $i => $f): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/10 p-6 hover:border-primary/40 hover:-translate-y-1 transition" data-animate data-animate-delay="<?= e(min(4, $i + 1)) ?>">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl text-2xl mb-4 <?= e($colorMap[$f['color']]) ?>"><?= e($f['icon']) ?></div>
                <h3 class="font-display font-bold text-lg md:text-xl mb-2 text-ink dark:text-pale"><?= e($f['title']) ?></h3>
                <p class="text-mist text-sm leading-relaxed"><?= e($f['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-6 md:p-8" data-animate>
            <div class="flex flex-col md:flex-row md:items-center gap-4 md:gap-6">
                <div class="text-5xl shrink-0">💡</div>
                <div class="flex-1">
                    <h3 class="font-display font-bold text-xl md:text-2xl mb-1.5 text-ink dark:text-pale">
                        Praktyczny przykład
                    </h3>
                    <p class="text-mist leading-relaxed text-sm md:text-base">
                        Jedziecie w 8 osób na Bałkany. Każdy dodaje 5-10 miejsc - Plitvice (8h), photo stop nad jeziorem (30min), fort w Omiš (1h). Po ocenach algorytm tworzy 3 propozycje tras: <span class="text-primary font-medium">Chorwacja Północ (12 miejsc, 4 dni)</span>, <span class="text-primary font-medium">Dalmacja (8 miejsc, 3 dni)</span>, <span class="text-primary font-medium">Bonus: Słowacja (3 miejsca, 1 dzień)</span>. Klik - widzicie wszystko na mapie z trasą drogową.
                    </p>
                </div>
            </div>
        </div>

    </div>
</section>
