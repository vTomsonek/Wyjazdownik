<?php
/**
 * Sekcja 4: Transport - rozklad srodkow, paszporty, kierowcy.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->allResponses();

// Zlicz transport_modes
$transportOpts = QuestionLabels::get('transport_modes')['options'] ?? [];
$transportVotes = array_fill_keys(array_keys($transportOpts), 0);
foreach ($responses as $resp) {
    $modes = $resp['transport_modes'] ?? [];
    if (is_array($modes)) {
        foreach ($modes as $m) {
            if (isset($transportVotes[$m])) $transportVotes[$m]++;
        }
    }
}

// Czesc wspolna - kazdy zaznaczył
$intersect = null;
foreach ($responses as $resp) {
    $modes = $resp['transport_modes'] ?? [];
    if (!is_array($modes)) continue;
    if ($intersect === null) {
        $intersect = $modes;
    } else {
        $intersect = array_values(array_intersect($intersect, $modes));
    }
}
$intersect = $intersect ?? [];

// Paszporty
$hasPassport = 0; $noPassport = 0;
foreach ($responses as $resp) {
    $hp = $resp['has_passport'] ?? null;
    if ($hp === 'true' || $hp === true) $hasPassport++;
    elseif ($hp === 'false' || $hp === false) $noPassport++;
}

// Kierowcy
$drivers = 0; $sharers = 0;
foreach ($responses as $resp) {
    if (($resp['has_driving_license'] ?? null) === 'true' || ($resp['has_driving_license'] ?? null) === true) $drivers++;
    $share = $resp['can_share_car'] ?? null;
    if ($share === 'yes' || $share === 'maybe') $sharers++;
}
?>

<section class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/15 text-secondary">SEKCJA 4 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🚗 Transport i logistyka
            </h2>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Nikt jeszcze nie wypełnił sekcji transportu.</p>
        <?php else: ?>

            <!-- Wykres srodkow transportu -->
            <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-8 mb-8">
                <h3 class="font-display font-bold text-lg md:text-xl mb-5 text-ink dark:text-pale">Czym ekipa może jechać</h3>
                <div class="space-y-3">
                    <?php foreach ($transportVotes as $key => $votes):
                        if ($votes === 0) continue;
                        $label = $transportOpts[$key] ?? $key;
                        $pct = $count > 0 ? (int) round($votes / $count * 100) : 0;
                        $isFull = $votes === $count;
                    ?>
                    <div class="flex items-center gap-3 text-sm md:text-base">
                        <span class="w-32 md:w-40 shrink-0 text-ink dark:text-pale"><?= e($label) ?></span>
                        <div class="flex-1 h-7 md:h-8 bg-mist/15 rounded-full overflow-hidden">
                            <div class="h-full rounded-full flex items-center justify-end px-3 font-mono text-xs md:text-sm <?= $isFull ? 'bg-secondary text-white' : 'bg-primary text-white' ?>"
                                 style="width: <?= max(15, $pct) ?>%">
                                <?= $votes ?>/<?= $count ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Wnioski -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5">
                    <div class="text-xs text-mist mb-1">Wszyscy się zgodzą</div>
                    <?php if (empty($intersect)): ?>
                        <div class="font-display font-bold text-lg text-red-500">Brak konsensusu 😬</div>
                        <p class="text-xs text-mist mt-1">Trzeba znaleźć kompromis.</p>
                    <?php else: ?>
                        <div class="font-display font-bold text-lg md:text-xl text-secondary">
                            <?= e(implode(', ', array_map(static fn($k) => $transportOpts[$k] ?? $k, $intersect))) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5">
                    <div class="text-xs text-mist mb-1">Paszporty</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">
                        <?= $hasPassport ?> / <?= $count ?>
                    </div>
                    <p class="text-xs text-mist mt-1">
                        <?php if ($noPassport > 0): ?>
                            ⚠️ <?= $noPassport ?> os. bez paszportu - tylko UE.
                        <?php else: ?>
                            ✓ wszyscy mają, można poza UE.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5">
                    <div class="text-xs text-mist mb-1">Kierowcy</div>
                    <div class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">
                        <?= $drivers ?>
                    </div>
                    <p class="text-xs text-mist mt-1">
                        <?= $sharers ?> może udostępnić auto.
                    </p>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
