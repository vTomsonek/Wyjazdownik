<?php
/**
 * Sekcja 8: Kuchnia - dietetyka, alergie (czerwone!), styl, otwartosc.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$participants = $agg->participants();
$responses = $agg->allResponses();
$anonymous = $agg->isAnonymous();

// Dietetyki - zlicz multi
$dietOpts = QuestionLabels::get('dietary_restrictions')['options'] ?? [];
$dietCounts = array_fill_keys(array_keys($dietOpts), 0);
foreach ($responses as $resp) {
    $vs = $resp['dietary_restrictions'] ?? [];
    if (is_array($vs)) {
        foreach ($vs as $v) {
            if (isset($dietCounts[$v])) $dietCounts[$v]++;
        }
    }
}
arsort($dietCounts);

// Alergie - lista par [name, allergy_text]
$allergies = [];
foreach ($participants as $i => $p) {
    $a = $responses[$p->id]['food_allergies'] ?? '';
    if (is_string($a) && trim($a) !== '') {
        $allergies[] = [
            'name'   => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
            'text'   => trim($a),
        ];
    }
}

// Styl jedzenia
$styleOpts = QuestionLabels::get('food_style')['options'] ?? [];
$styleCounts = array_fill_keys(array_keys($styleOpts), 0);
foreach ($responses as $resp) {
    $s = $resp['food_style'] ?? null;
    if (is_string($s) && isset($styleCounts[$s])) $styleCounts[$s]++;
}

// Otwartosc kulinarna - srednia
$opennessValues = [];
foreach ($responses as $resp) {
    $o = $resp['food_openness'] ?? null;
    if (is_numeric($o)) $opennessValues[] = (int) $o;
}
$avgOpenness = empty($opennessValues) ? 0 : (array_sum($opennessValues) / count($opennessValues));
?>

<section class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-600 dark:text-accent">SEKCJA 8 / 15</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🍽️ Kuchnia
            </h2>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Brak danych.</p>
        <?php else: ?>

            <!-- Alergie - czerwone, na gorze -->
            <?php if (!empty($allergies)): ?>
            <div class="mb-8 rounded-2xl bg-red-100 dark:bg-red-950/40 border-2 border-red-300 dark:border-red-800 p-5 md:p-6">
                <h3 class="font-display font-bold text-lg md:text-xl mb-3 text-red-700 dark:text-red-300 flex items-center gap-2">
                    🚨 Alergie pokarmowe
                </h3>
                <ul class="space-y-2 text-sm md:text-base text-red-800 dark:text-red-200">
                    <?php foreach ($allergies as $a): ?>
                        <li>
                            <strong><?= e($a['name']) ?>:</strong> <?= e($a['text']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-3 text-xs text-red-700 dark:text-red-300">
                    Bezpieczeństwo ekipy - sprawdzajcie składy, pytajcie kelnerów.
                </p>
            </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-5">
                <!-- Dietetyki -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Ograniczenia diety</h3>
                    <?php
                    $hasDiet = false;
                    foreach ($dietCounts as $key => $cnt) {
                        if ($cnt > 0 && $key !== 'none') { $hasDiet = true; break; }
                    }
                    if (!$hasDiet): ?>
                        <p class="text-mist italic text-sm">Wszyscy bez ograniczeń.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($dietCounts as $key => $cnt):
                                if ($cnt === 0 || $key === 'none') continue;
                                $label = $dietOpts[$key] ?? $key;
                                $pct = $count > 0 ? (int) round($cnt / $count * 100) : 0;
                            ?>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="flex-1 text-ink dark:text-pale"><?= e($label) ?></span>
                                <span class="font-mono text-mist w-10 text-right"><?= $cnt ?></span>
                                <div class="w-32 md:w-40 h-2 bg-mist/15 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500" style="width: <?= max(8, $pct) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Styl jedzenia -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Preferowany styl jedzenia</h3>
                    <div class="space-y-2">
                        <?php foreach ($styleCounts as $key => $cnt):
                            if ($cnt === 0) continue;
                            $label = $styleOpts[$key] ?? $key;
                            $pct = $count > 0 ? (int) round($cnt / $count * 100) : 0;
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1 text-ink dark:text-pale"><?= e($label) ?></span>
                            <span class="font-mono text-mist w-10 text-right"><?= $cnt ?></span>
                            <div class="w-32 md:w-40 h-2 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: <?= max(8, $pct) ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Otwartosc kulinarna -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6 md:col-span-2">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Otwartość kulinarna ekipy</h3>
                    <?php if (empty($opennessValues)): ?>
                        <p class="text-mist italic text-sm">Brak danych.</p>
                    <?php else: ?>
                        <div class="flex items-baseline gap-3 mb-3">
                            <span class="font-display font-bold text-5xl md:text-6xl 3xl:text-7xl text-secondary"><?= e(number_format($avgOpenness, 1, ',', '')) ?></span>
                            <span class="text-mist text-lg">/ 5 średnia</span>
                        </div>
                        <p class="text-sm text-mist">
                            <?php
                            if ($avgOpenness >= 4.5)      echo '🌶️ Ekipa awanturników kulinarnych - jedzą wszystko.';
                            elseif ($avgOpenness >= 3.5)  echo '😋 Ekipa jest otwarta - można eksperymentować.';
                            elseif ($avgOpenness >= 2.5)  echo '🤔 Średnia otwartość - postawcie raczej na bezpieczne wybory.';
                            else                          echo '🦷 Ekipa wybredna - planujcie jedzenie blisko europejskiego.';
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
