<?php
/**
 * Sekcja 10: Klimat - heatmapa preferencji + część wspólna.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->allResponses();

$climOpts = QuestionLabels::get('climate_tolerance')['options'] ?? [];
$climOrder = ['hot_30plus', 'warm_20_30', 'mild_10_20', 'cool_under_10', 'cold_winter'];
$climCounts = array_fill_keys($climOrder, 0);
foreach ($responses as $resp) {
    $vs = $resp['climate_tolerance'] ?? [];
    if (is_array($vs)) {
        foreach ($vs as $v) {
            if (isset($climCounts[$v])) $climCounts[$v]++;
        }
    }
}

// Czesc wspolna - klimaty ktore zaznaczyli wszyscy
$intersect = null;
foreach ($responses as $resp) {
    $vs = $resp['climate_tolerance'] ?? [];
    if (!is_array($vs)) continue;
    if ($intersect === null) {
        $intersect = $vs;
    } else {
        $intersect = array_values(array_intersect($intersect, $vs));
    }
}
$intersect = $intersect ?? [];

$climColors = [
    'hot_30plus'    => 'bg-rose-500',
    'warm_20_30'    => 'bg-orange-400',
    'mild_10_20'    => 'bg-amber-300',
    'cool_under_10' => 'bg-sky-400',
    'cold_winter'   => 'bg-blue-500',
];
?>

<section class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/15 text-sky-600">SEKCJA 10 / 15</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🌡️ Klimat
            </h2>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Brak danych.</p>
        <?php else: ?>

            <div class="grid md:grid-cols-2 gap-5 mb-6">
                <!-- Wykres -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Co ekipa toleruje</h3>
                    <div class="space-y-2">
                        <?php foreach ($climOrder as $key):
                            $cnt = $climCounts[$key];
                            $label = $climOpts[$key] ?? $key;
                            $pct = $count > 0 ? (int) round($cnt / $count * 100) : 0;
                            $color = $climColors[$key] ?? 'bg-primary';
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1 text-ink dark:text-pale"><?= e($label) ?></span>
                            <span class="font-mono text-mist w-10 text-right"><?= $cnt ?></span>
                            <div class="w-32 md:w-40 h-3 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full <?= $color ?>" style="width: <?= max(8, $pct) ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Konsensus -->
                <div class="rounded-2xl bg-secondary/10 border border-secondary/30 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-3 text-secondary">
                        🎯 Wszyscy są OK z:
                    </h3>
                    <?php if (empty($intersect)): ?>
                        <p class="text-mist italic">Brak wspólnego klimatu - ekipa ma rozbieżne preferencje. Wybierzcie ten z największą ilością głosów.</p>
                    <?php else: ?>
                        <ul class="space-y-2">
                            <?php foreach ($intersect as $key):
                                $label = $climOpts[$key] ?? $key;
                            ?>
                            <li class="font-display font-bold text-lg md:text-xl text-ink dark:text-pale">
                                <?= e($label) ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-4 text-xs text-mist">
                            Ekipa pojedzie spokojnie w tych warunkach.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
