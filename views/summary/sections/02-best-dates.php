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

// TOP 3 dni z najwyższą dostepnoscia (i wszyscy moga gdy available == total)
$everyoneDays = array_filter($days, static fn($d) => $total > 0 && $d['available'] === $total);
$topDays      = $days;
usort($topDays, static fn($a, $b) => $b['available'] <=> $a['available']);
$topDays = array_slice($topDays, 0, 5);

$intensityClass = static function (int $avail, int $total): string {
    if ($total === 0)            return 'bg-mist/10';
    $r = $avail / $total;
    if ($r >= 1.0)               return 'bg-secondary text-white';
    if ($r >= 0.85)              return 'bg-secondary/70 text-white';
    if ($r >= 0.65)              return 'bg-accent/70 text-ink';
    if ($r >= 0.4)               return 'bg-orange-300 text-ink';
    if ($r > 0)                  return 'bg-red-200 text-red-700';
    return 'bg-red-300 text-red-800 line-through';
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

<section class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/15 text-secondary">SEKCJA 2 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                📅 Najlepsze terminy
            </h2>
            <p class="text-mist text-lg max-w-2xl mx-auto">
                Im ciemniejszy zielony - tym więcej osób z ekipy może w danym dniu. Czerwony = ktoś jest niedostępny.
            </p>
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
                        ⭐ TOP 5 najlepszych dni
                    </div>
                    <ol class="space-y-1.5 text-sm">
                        <?php foreach ($topDays as $i => $d): ?>
                        <li class="flex items-center gap-3">
                            <span class="font-mono text-mist w-6"><?= ($i + 1) ?>.</span>
                            <span class="font-mono font-medium text-ink dark:text-pale flex-1">
                                <?= sprintf('%02d.%02d.%d', $d['d'], $d['m'], $d['y']) ?>
                            </span>
                            <span class="text-mist text-xs"><?= $d['available'] ?>/<?= $total ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>

        <?php else: ?>
            <p class="text-center text-mist italic">Tryb "preferowane tygodnie" - heatmapa na bazie tygodni planowana w kolejnej iteracji.</p>
        <?php endif; ?>
    </div>
</section>
