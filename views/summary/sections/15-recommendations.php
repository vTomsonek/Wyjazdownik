<?php
/**
 * Sekcja 15: Inteligentne rekomendacje + sugerowane destynacje.
 * @var \App\Services\SummaryAggregator $agg
 * @var \App\Models\Trip $trip
 */
use App\Services\RecommendationService;

$svc       = new RecommendationService($agg);
$weak      = $svc->weakestLink();
$median    = $svc->medianBudget();
$dests     = $svc->suggestedDestinations();

// Najlepszy termin - dni gdzie wszyscy moga (juz mamy w sekcji 2)
$total = $agg->completedCount();
$unavailMap = $agg->unavailableDates();
$unavailableByDate = [];
foreach ($unavailMap as $dates) {
    foreach ($dates as $d) $unavailableByDate[$d] = ($unavailableByDate[$d] ?? 0) + 1;
}
$everyone = [];
$cursor = new DateTime($trip->dateFrom);
$end    = new DateTime($trip->dateTo);
while ($cursor <= $end) {
    $iso = $cursor->format('Y-m-d');
    if (($unavailableByDate[$iso] ?? 0) === 0) $everyone[] = $iso;
    $cursor->modify('+1 day');
}
// Znajdz pierwsza ciagla sekwencje co najmniej 3 dniowa
$bestRange = null;
if (!empty($everyone)) {
    $start = $everyone[0]; $prev = $start; $len = 1;
    $maxLen = 1; $maxStart = $start; $maxEnd = $start;
    for ($i = 1; $i < count($everyone); $i++) {
        $expected = (new DateTime($prev))->modify('+1 day')->format('Y-m-d');
        if ($everyone[$i] === $expected) {
            $len++;
            if ($len > $maxLen) { $maxLen = $len; $maxStart = $start; $maxEnd = $everyone[$i]; }
        } else {
            $start = $everyone[$i]; $len = 1;
        }
        $prev = $everyone[$i];
    }
    $bestRange = ['start' => $maxStart, 'end' => $maxEnd, 'days' => $maxLen];
}

$fmt = static fn($n): string => is_numeric($n) ? number_format((int) $n, 0, ',', ' ') : '—';
?>

<section class="section">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow eyebrow--teal"><span class="iconify" data-icon="ph:target-bold"></span> Finałowe</span>
            <h2 style="margin-top:18px">🎯 Inteligentne rekomendacje</h2>
            <p>Wnioski algorytmu na bazie wszystkich odpowiedzi.</p>
        </header>

        <!-- Kluczowe rekomendacje -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
            <?php if ($bestRange): ?>
            <div class="rounded-2xl bg-secondary/10 border border-secondary/30 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-secondary font-semibold mb-2">📅 Najlepszy termin</div>
                <div class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mb-1">
                    <?= e(date('d.m', strtotime($bestRange['start']))) ?>
                    <?php if ($bestRange['start'] !== $bestRange['end']): ?>
                        – <?= e(date('d.m.Y', strtotime($bestRange['end']))) ?>
                    <?php else: ?>
                        <?= e(date('.Y', strtotime($bestRange['start']))) ?>
                    <?php endif; ?>
                </div>
                <p class="text-sm text-mist"><?= $bestRange['days'] ?> dni gdzie wszyscy są dostępni.</p>
            </div>
            <?php endif; ?>

            <?php if ($median !== null): ?>
            <div class="rounded-2xl bg-primary/10 border border-primary/30 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-primary font-semibold mb-2">💰 Sugerowany budżet</div>
                <div class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mb-1">
                    <?= $fmt($median) ?> zł / os
                </div>
                <p class="text-sm text-mist">Mediana ekipy. Realny minimum: <?= $fmt($weak['budgetReal']) ?> zł.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($weak['transportOk'])): ?>
            <div class="rounded-2xl bg-accent/15 border border-accent/40 p-5 md:p-6">
                <div class="text-xs uppercase tracking-wider text-amber-700 dark:text-accent font-semibold mb-2">🚗 Realistyczny transport</div>
                <div class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mb-1">
                    <?= e(implode(', ', array_map(static fn($k) => match($k) {
                        'car' => 'Samochód', 'plane' => 'Samolot', 'train' => 'Pociąg', 'bus' => 'Autobus',
                        default => $k,
                    }, $weak['transportOk']))) ?>
                </div>
                <p class="text-sm text-mist">Tylko to akceptują wszyscy.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sugerowane destynacje -->
        <?php if (!empty($dests)): ?>
        <h3 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale mb-5 text-center">
            🌍 Sugerowane destynacje
        </h3>
        <p class="text-center text-mist text-sm mb-8 max-w-2xl mx-auto">
            Algorytm dopasował kierunki na bazie tagów krajobrazu, klimatu i transportu. Punkt startowy do dyskusji.
        </p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($dests as $d): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6 hover:shadow-pop transition">
                <div class="font-display font-bold text-lg md:text-xl text-ink dark:text-pale mb-2">
                    <?= e($d['name']) ?>
                </div>
                <p class="text-sm text-mist leading-relaxed">
                    <?= e($d['why']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Footer "co dalej" -->
        <div class="mt-12 text-center">
            <p class="font-accent text-2xl md:text-3xl 3xl:text-4xl text-primary">
                Teraz tylko zarezerwujcie!
            </p>
        </div>
    </div>
</section>
