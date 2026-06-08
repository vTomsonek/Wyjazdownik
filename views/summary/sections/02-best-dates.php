<?php
/**
 * Sekcja 2: Najlepsze terminy.
 * Heatmapa: per dzien w oknie wyjazdu liczy ile osob moze (= total - niedostepni).
 * Wyroznia TOP 3 dni z najwyzsza dostepnoscia + lista "Wszyscy moga".
 *
 * @var \App\Models\Trip $trip
 * @var \App\Services\SummaryAggregator $agg
 */
$total      = $agg->completedCount();
$unavailMap = $agg->unavailableDates();
$dateFrom   = new DateTime($trip->dateFrom);
$dateTo     = new DateTime($trip->dateTo);

// Zbierz set niedostepnych dat per dzien.
$unavailableByDate = [];
foreach ($unavailMap as $participantId => $dates) {
    foreach ($dates as $date) {
        $unavailableByDate[$date] = ($unavailableByDate[$date] ?? 0) + 1;
    }
}

// Tylko sensowne dla trybu block_unavailable.
$mode = $trip->calendarMode;

// Iteruj po dniach okna i licz available.
$days = [];
$cursor = clone $dateFrom;
while ($cursor <= $dateTo) {
    $iso = $cursor->format('Y-m-d');
    $unavail = $unavailableByDate[$iso] ?? 0;
    $available = max(0, $total - $unavail);
    $days[] = [
        'date'      => $iso,
        'd'         => (int) $cursor->format('d'),
        'm'         => (int) $cursor->format('n'),
        'y'         => (int) $cursor->format('Y'),
        'wday'      => (int) $cursor->format('N'), // 1=pon, 7=nd
        'available' => $available,
        'unavail'   => $unavail,
    ];
    $cursor->modify('+1 day');
}

// "Wszyscy moga" - dni gdzie available == total
$everyoneDays = array_filter($days, static fn($d) => $total > 0 && $d['available'] === $total);

// TOP 5 ciaglych okresow gdzie wszyscy moga (sekwencje, nie pojedyncze dni)
$runs = [];
$current = null;
foreach ($days as $d) {
    $isFull = $total > 0 && $d['available'] === $total;
    if ($isFull) {
        if ($current === null) {
            $current = ['start' => $d['date'], 'end' => $d['date'], 'length' => 1];
        } else {
            $current['end']    = $d['date'];
            $current['length']++;
        }
    } else {
        if ($current !== null) { $runs[] = $current; $current = null; }
    }
}
if ($current !== null) $runs[] = $current;
// Sort po dlugosci malejaco, potem po dacie rosnaco (tie-break)
usort($runs, static fn($a, $b) => $b['length'] <=> $a['length'] ?: strcmp($a['start'], $b['start']));
$topRuns = array_slice($runs, 0, 5);

// Skala dostepnosci - kazdy poziom ma WLASNY hue (nie tylko opacity)
// Klasy .cal-* definiowane w summary-overlay.css - gwarantowane kolory
$intensityClass = static function (int $avail, int $total): string {
    if ($total === 0)            return 'cal-empty';
    if ($avail === $total)       return 'cal-7';   // 100% = emerald (zielony)
    $r = $avail / $total;
    if ($r >= 0.83)              return 'cal-6';   // ~85%+ = teal solid
    if ($r >= 0.65)              return 'cal-5';   // ~70%+ = aqua jasny
    if ($r >= 0.4)               return 'cal-4';   // ~50%+ = sun yellow
    if ($r > 0)                  return 'cal-3';   // <50% = orange
    return 'cal-0';                                // 0 = red przekreslony
};

$monthNames = ['', 'Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec','Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];

// Pogrupuj dni per miesiac
$months = [];
foreach ($days as $d) {
    $key = sprintf('%04d-%02d', $d['y'], $d['m']);
    $months[$key]['label'] = $monthNames[$d['m']] . ' ' . $d['y'];
    $months[$key]['days'][] = $d;
}
?>

<section class="section section--cream">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow eyebrow--teal"><span class="iconify" data-icon="ph:calendar-blank-bold"></span> Najlepsze terminy</span>
            <h2 style="margin-top:18px">Kiedy ekipa się pokrywa</h2>
            <p>Zielony = wszyscy mogą. Turkusowy = prawie wszyscy. Żółty/pomarańczowy = połowa. Czerwony = ktoś jest niedostępny.</p>
        </header>

        <?php if ($mode === 'block_unavailable'): ?>
            <!-- Heatmapa miesiecy -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <?php foreach ($months as $month): ?>
                <div>
                    <h3 class="font-display font-bold text-lg md:text-xl mb-3 text-ink dark:text-pale">
                        <?= e($month['label']) ?>
                    </h3>
                    <div class="grid grid-cols-7 gap-1 text-xs">
                        <?php foreach (['Pn','Wt','Śr','Cz','Pt','So','Nd'] as $dn): ?>
                            <div class="text-center text-mist py-1 font-medium"><?= $dn ?></div>
                        <?php endforeach; ?>
                        <?php
                        // Padding przed pierwszym dniem
                        $first = $month['days'][0];
                        $pad = $first['wday'] - 1;
                        for ($i = 0; $i < $pad; $i++) echo '<div></div>';
                        foreach ($month['days'] as $d):
                            $cls = $intensityClass($d['available'], $total);
                        ?>
                            <div class="aspect-square flex items-center justify-center rounded-md font-medium <?= $cls ?>"
                                 title="<?= $d['available'] ?>/<?= $total ?> dostępnych">
                                <?= $d['d'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- TOP 3 / lista "Wszyscy moga" -->
            <div class="grid md:grid-cols-2 gap-6">
                <div class="rounded-2xl bg-secondary/10 border border-secondary/30 p-5 md:p-6">
                    <div class="text-secondary font-display font-bold text-xl md:text-2xl mb-3">
                        🏆 Wszyscy mogą:
                    </div>
                    <?php if (empty($everyoneDays)): ?>
                        <p class="text-mist italic">Niestety, ekipa nie ma wspólnego dnia w którym wszyscy są dostępni.</p>
                    <?php else: ?>
                        <p class="font-mono text-sm md:text-base text-ink dark:text-pale leading-relaxed">
                            <?= e(implode(', ', array_map(static fn($d) => sprintf('%02d.%02d', $d['d'], $d['m']), $everyoneDays))) ?>
                        </p>
                        <p class="mt-3 text-xs text-mist">
                            Łącznie <?= count($everyoneDays) ?> dni wspólnej dostępności.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <div class="font-display font-bold text-xl md:text-2xl mb-3 text-ink dark:text-pale">
                        ⭐ TOP 5 dłuższych okresów
                    </div>
                    <?php if (empty($topRuns)): ?>
                        <p class="text-sm text-mist italic">Brak ciągłych okresów wspólnej dostępności.</p>
                    <?php else: ?>
                        <ol class="space-y-2 text-sm">
                            <?php foreach ($topRuns as $i => $r):
                                $start = new DateTime($r['start']);
                                $end   = new DateTime($r['end']);
                                $rangeText = $r['length'] === 1
                                    ? $start->format('d.m.Y')
                                    : $start->format('d.m') . ' – ' . $end->format('d.m.Y');
                            ?>
                            <li class="flex items-center gap-3">
                                <span class="font-mono text-mist w-6"><?= ($i + 1) ?>.</span>
                                <span class="font-mono font-medium text-ink dark:text-pale flex-1">
                                    <?= e($rangeText) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded-full bg-secondary/15 text-secondary text-xs font-mono shrink-0">
                                    <?= $r['length'] ?> dni
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <p class="text-center text-mist italic">Tryb "preferowane tygodnie" - heatmapa na bazie tygodni planowana w kolejnej iteracji.</p>
        <?php endif; ?>

        <!-- Długość wyjazdu -->
        <?php
        $durations = $agg->valuesFor('trip_duration_days');
        $durations = array_values(array_map('intval', array_filter($durations, 'is_numeric')));
        sort($durations);
        $durCount = count($durations);
        if ($durCount > 0):
            $minD = $durations[0];
            $maxD = $durations[$durCount - 1];
            $medD = $durCount % 2 === 0
                ? (int) round(($durations[(int) ($durCount/2 - 1)] + $durations[(int) ($durCount/2)]) / 2)
                : $durations[(int) floor($durCount / 2)];
            $avgD = (int) round(array_sum($durations) / $durCount);

            // Per uczestnik z kolorami
            $participants = $agg->participants();
            $colors = $agg->colorMap();
            $anonymous = $agg->isAnonymous();
            $responses = $agg->allResponses();
            $perPerson = [];
            foreach ($participants as $i => $p) {
                $v = $responses[$p->id]['trip_duration_days'] ?? null;
                if (is_numeric($v)) {
                    $perPerson[] = [
                        'name'  => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
                        'days'  => (int) $v,
                        'color' => $colors[$p->id] ?? '#FF6B35',
                    ];
                }
            }
        ?>
        <div class="mt-10 rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale">⏱️ Długość wyjazdu</h3>
                <span class="text-sm text-mist">ile dni ekipa chce</span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
                <div class="text-center">
                    <div class="text-xs text-mist mb-1">Min</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-rose-500"><?= $minD ?></div>
                    <div class="text-xs text-mist mt-1">dni</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-mist mb-1">Mediana</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-secondary"><?= $medD ?></div>
                    <div class="text-xs text-mist mt-1">dni</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-mist mb-1">Średnia</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale"><?= $avgD ?></div>
                    <div class="text-xs text-mist mt-1">dni</div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-mist mb-1">Max</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-primary"><?= $maxD ?></div>
                    <div class="text-xs text-mist mt-1">dni</div>
                </div>
            </div>

            <div class="space-y-2">
                <?php foreach ($perPerson as $pp):
                    $pct = $maxD > 0 ? (int) round($pp['days'] / $maxD * 100) : 0;
                ?>
                <div class="flex items-center gap-3 text-sm">
                    <span class="w-24 md:w-28 shrink-0 truncate text-ink dark:text-pale"><?= e($pp['name']) ?></span>
                    <div class="flex-1 h-7 bg-mist/15 rounded-full overflow-hidden">
                        <div class="h-full rounded-full flex items-center justify-end px-3 text-white font-mono font-semibold text-xs"
                             style="width: <?= max(15, $pct) ?>%; background: <?= e($pp['color']) ?>">
                            <?= $pp['days'] ?> dni
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 pt-4 border-t border-mist/15 text-sm text-mist">
                🎯 Realny czas wyjazdu: <strong class="text-primary"><?= $minD ?> dni</strong>
                — najkrótszy okres jaki ekipa może poświęcić.
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
