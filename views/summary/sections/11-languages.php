<?php
/**
 * Sekcja 11: Jezyki - ile osob mowi communicative+ + sugestie krajow.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->allResponses();

$meta = QuestionLabels::get('languages') ?? [];
$names = $meta['languages'] ?? [];

// Zlicz: per jezyk ile osob ma communicative+ albo fluent
$langStats = []; // lang_key => ['comm'=>X, 'fluent'=>Y]
foreach ($names as $key => $name) {
    $langStats[$key] = ['name' => $name, 'comm' => 0, 'fluent' => 0];
}
foreach ($responses as $resp) {
    $langs = $resp['languages'] ?? [];
    if (!is_array($langs)) continue;
    foreach ($langs as $lang => $level) {
        if (!isset($langStats[$lang])) continue;
        if ($level === 'fluent')        { $langStats[$lang]['fluent']++; $langStats[$lang]['comm']++; }
        elseif ($level === 'communicative') $langStats[$lang]['comm']++;
    }
}
// Filtruj te ktorych nikt nie zna communicative+
$langStats = array_filter($langStats, static fn($s) => $s['comm'] > 0);
uasort($langStats, static fn($a, $b) => $b['comm'] <=> $a['comm']);

// Sugestie krajow - mapa jezyka na liste krajow
$countriesByLang = [
    'english'    => ['UK', 'Irlandia', 'Malta', '+ większość świata jako lingua franca'],
    'german'     => ['Niemcy', 'Austria', 'Szwajcaria niemiecka'],
    'spanish'    => ['Hiszpania', 'Ameryka Łacińska (oprócz Brazylii)'],
    'french'     => ['Francja', 'Belgia', 'Quebec', 'Maroko/Algieria/Tunezja', 'częściowo Afryka Zach.'],
    'italian'    => ['Włochy', 'San Marino', 'Tessyn (Szwajcaria włoska)'],
    'russian'    => ['Rosja', 'Białoruś', 'Kazachstan', 'częściowo Azja Środkowa'],
    'ukrainian'  => ['Ukraina'],
    'czech'      => ['Czechy'],
    'slovak'     => ['Słowacja'],
    'portuguese' => ['Portugalia', 'Brazylia', 'Mozambik', 'Angola'],
    'dutch'      => ['Holandia', 'Belgia (Flandria)'],
    'swedish'    => ['Szwecja', 'częściowo Finlandia'],
    'japanese'   => ['Japonia'],
    'mandarin'   => ['Chiny', 'Tajwan', 'częściowo Singapur'],
];

// Najlepszy jezyk - z najwiekszą liczba mowiących communicative+
$best = !empty($langStats) ? array_key_first($langStats) : null;
?>

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-violet-500/15 text-violet-600">SEKCJA 11 / 15</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🌐 Języki
            </h2>
        </header>

        <?php if (empty($langStats)): ?>
            <p class="text-center text-mist italic">Ekipa nie zna żadnego języka komunikatywnie. Polski + gestykulacja!</p>
        <?php else: ?>

            <div class="grid md:grid-cols-2 gap-5">
                <!-- Wykres -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">
                        Kto się dogada
                        <span class="text-sm font-normal text-mist">(komunikatywnie+)</span>
                    </h3>
                    <div class="space-y-2">
                        <?php foreach ($langStats as $key => $s):
                            $pct = $count > 0 ? (int) round($s['comm'] / $count * 100) : 0;
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1 text-ink dark:text-pale"><?= e($s['name']) ?></span>
                            <?php if ($s['fluent'] > 0): ?>
                                <span class="px-2 py-0.5 rounded-full bg-secondary/15 text-secondary text-xs font-medium"><?= $s['fluent'] ?> biegle</span>
                            <?php endif; ?>
                            <span class="font-mono text-mist w-10 text-right"><?= $s['comm'] ?>/<?= $count ?></span>
                            <div class="w-32 md:w-40 h-2 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full bg-violet-500" style="width: <?= max(8, $pct) ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sugestie krajow -->
                <div class="rounded-2xl bg-violet-500/5 border border-violet-300 dark:border-violet-800 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-3 text-ink dark:text-pale">🎯 Gdzie ekipa się dogada</h3>
                    <?php if ($best && isset($countriesByLang[$best])): ?>
                        <p class="text-sm text-mist mb-3">Najwięcej osób mówi w <strong class="text-ink dark:text-pale"><?= e($langStats[$best]['name']) ?></strong>:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($countriesByLang[$best] as $country): ?>
                                <span class="px-3 py-1.5 rounded-full bg-violet-500/15 text-violet-700 dark:text-violet-300 text-sm font-medium">
                                    <?= e($country) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-4 text-xs text-mist">
                            W innych krajach przyda się Google Translate lub gestykulacja.
                        </p>
                    <?php else: ?>
                        <p class="text-mist italic">Brak danych do sugestii.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
