<?php
/**
 * Sekcja 3: Budzet ekipy.
 * Wykres slupkowy + min/max/mediana + rozklad money_attitude.
 *
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$participants = $agg->participants();
$colors       = $agg->colorMap();
$anonymous    = $agg->isAnonymous();
$responses    = $agg->allResponses();

// Zbierz pary [participant => budget]
$budgets = [];
foreach ($participants as $i => $p) {
    $val = $responses[$p->id]['budget_range'] ?? null;
    if (is_numeric($val)) {
        $budgets[] = [
            'participant' => $p,
            'name'        => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
            'value'       => (int) $val,
            'color'       => $colors[$p->id] ?? '#FF6B35',
        ];
    }
}
$values = array_map(static fn($b) => $b['value'], $budgets);
$count  = count($values);
sort($values);
$min    = $count > 0 ? $values[0] : 0;
$max    = $count > 0 ? $values[$count - 1] : 0;
$median = 0;
if ($count > 0) {
    $mid = (int) floor($count / 2);
    $median = $count % 2 === 0 ? (int) round(($values[$mid - 1] + $values[$mid]) / 2) : $values[$mid];
}
$avg = $count > 0 ? (int) round(array_sum($values) / $count) : 0;

// Money_attitude rozklad
$attitudes = ['strict' => 0, 'balanced' => 0, 'save_food_spend_attractions' => 0, 'vacation_mode' => 0, 'unlimited' => 0];
foreach ($responses as $resp) {
    $a = $resp['money_attitude'] ?? null;
    if (is_string($a) && isset($attitudes[$a])) $attitudes[$a]++;
}
$attitudeOpts = QuestionLabels::get('money_attitude')['options'] ?? [];

$fmt = static fn(int $n): string => number_format($n, 0, ',', ' ');
?>

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">SEKCJA 3 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                💰 Budżet ekipy
            </h2>
            <p class="text-mist text-lg max-w-2xl mx-auto">
                Ile kto chce wydać na osobę. Najsłabsze ogniwo wyznacza realny budżet.
            </p>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Nikt jeszcze nie wpisał budżetu.</p>
        <?php else: ?>

            <!-- Statystyki -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-10">
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 text-center">
                    <div class="text-xs text-mist mb-1">Najniższy</div>
                    <div class="font-display font-bold text-2xl md:text-3xl 3xl:text-4xl text-red-500"><?= e($fmt($min)) ?></div>
                    <div class="text-xs text-mist mt-1">zł / os</div>
                </div>
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 text-center">
                    <div class="text-xs text-mist mb-1">Mediana</div>
                    <div class="font-display font-bold text-2xl md:text-3xl 3xl:text-4xl text-secondary"><?= e($fmt($median)) ?></div>
                    <div class="text-xs text-mist mt-1">zł / os</div>
                </div>
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 text-center">
                    <div class="text-xs text-mist mb-1">Średnia</div>
                    <div class="font-display font-bold text-2xl md:text-3xl 3xl:text-4xl text-ink dark:text-pale"><?= e($fmt($avg)) ?></div>
                    <div class="text-xs text-mist mt-1">zł / os</div>
                </div>
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 text-center">
                    <div class="text-xs text-mist mb-1">Najwyższy</div>
                    <div class="font-display font-bold text-2xl md:text-3xl 3xl:text-4xl text-primary"><?= e($fmt($max)) ?></div>
                    <div class="text-xs text-mist mt-1">zł / os</div>
                </div>
            </div>

            <!-- Wykres slupkowy: kazdy uczestnik z kolorem -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-8 mb-8">
                <h3 class="font-display font-bold text-lg md:text-xl mb-5 text-ink dark:text-pale">Per uczestnik</h3>
                <div class="space-y-3">
                    <?php foreach ($budgets as $b):
                        $pct = $max > 0 ? (int) round($b['value'] / $max * 100) : 0;
                    ?>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 md:w-28 shrink-0 truncate text-ink dark:text-pale"><?= e($b['name']) ?></span>
                        <div class="flex-1 h-7 md:h-9 bg-mist/15 rounded-full overflow-hidden">
                            <div class="h-full rounded-full flex items-center justify-end px-3 text-white font-mono font-semibold text-xs md:text-sm whitespace-nowrap"
                                 style="width: <?= max(22, $pct) ?>%; min-width: 5.5rem; background: <?= e($b['color']) ?>">
                                <?= e($fmt($b['value'])) ?> zł
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Wniosek "realny budżet" -->
            <div class="rounded-2xl bg-accent/15 border border-accent/40 p-5 md:p-6 mb-10">
                <p class="font-display font-bold text-lg md:text-xl text-ink dark:text-pale mb-1">
                    🎯 Realny budżet ekipy: <span class="text-primary"><?= e($fmt($min)) ?> zł / os</span>
                </p>
                <p class="text-sm text-mist">
                    Najsłabsze ogniwo decyduje - planujcie z myślą o najniższym budżecie, żeby nikt nie poległ.
                </p>
            </div>

            <!-- Money attitude -->
            <?php if (array_sum($attitudes) > 0): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Stosunek do wydawania</h3>
                <div class="space-y-2">
                    <?php foreach ($attitudes as $key => $cnt):
                        if ($cnt === 0) continue;
                        $label = $attitudeOpts[$key] ?? $key;
                        $pct = $count > 0 ? (int) round($cnt / $count * 100) : 0;
                    ?>
                    <div class="flex items-center gap-3 text-sm">
                        <span class="flex-1 text-ink dark:text-pale"><?= e($label) ?></span>
                        <span class="font-mono text-mist w-12 text-right"><?= $cnt ?> os.</span>
                        <div class="w-32 md:w-48 h-2 bg-mist/15 rounded-full overflow-hidden">
                            <div class="h-full bg-primary" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
