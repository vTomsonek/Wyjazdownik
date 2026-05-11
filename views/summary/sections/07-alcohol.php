<?php
/**
 * Sekcja 7: Alkohol i imprezy - rozklad + ostrzezenia o konfliktach.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->completedResponses();

// Rozklad alcohol_attitude
$alcOpts  = QuestionLabels::get('alcohol_attitude')['options']  ?? [];
$partyOpts = QuestionLabels::get('party_style')['options']      ?? [];

$alcOrder   = ['none', 'wine_with_dinner', 'social', 'likes_drinking', 'full_party'];
$partyOrder = ['quiet', 'moderate', 'party_hard'];

$alcCounts = array_fill_keys($alcOrder, 0);
$partyCounts = array_fill_keys($partyOrder, 0);
foreach ($responses as $resp) {
    $a = $resp['alcohol_attitude'] ?? null;
    if (is_string($a) && isset($alcCounts[$a])) $alcCounts[$a]++;
    $p = $resp['party_style'] ?? null;
    if (is_string($p) && isset($partyCounts[$p])) $partyCounts[$p]++;
}

// Ostrzezenia
$warnings = [];
if ($alcCounts['none'] > 0 && $alcCounts['full_party'] > 0) {
    $warnings[] = '⚠️ ' . $alcCounts['none'] . ' os. nie pije w ogóle, a ' . $alcCounts['full_party'] . ' chce chlać - znajdźcie kompromis.';
}
if ($alcCounts['none'] > 0) {
    $warnings[] = '🚫 ' . $alcCounts['none'] . ' os. nie pije - upewnijcie się że są opcje bezalkoholowe.';
}
if ($partyCounts['party_hard'] > 0 && $partyCounts['quiet'] > 0) {
    $warnings[] = '⚠️ Mieszane oczekiwania imprezowe - nie planujcie głośnych klubów co wieczór.';
}
?>

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-500">SEKCJA 7 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🍻 Alkohol i imprezy
            </h2>
            <p class="text-mist text-lg max-w-2xl mx-auto">Bez owijania w bawełnę.</p>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Brak danych.</p>
        <?php else: ?>

            <div class="grid md:grid-cols-2 gap-5 mb-8">
                <!-- Alkohol skala -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Stosunek do alkoholu</h3>
                    <div class="space-y-2">
                        <?php foreach ($alcOrder as $key):
                            $votes = $alcCounts[$key];
                            $label = $alcOpts[$key] ?? $key;
                            $pct = $count > 0 ? (int) round($votes / $count * 100) : 0;
                            $width = max(8, $pct);
                            $color = match ($key) {
                                'none'             => 'bg-secondary',
                                'wine_with_dinner' => 'bg-emerald-400',
                                'social'           => 'bg-accent',
                                'likes_drinking'   => 'bg-orange-400',
                                'full_party'       => 'bg-rose-500',
                                default            => 'bg-mist',
                            };
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1 text-ink dark:text-pale text-xs md:text-sm"><?= e($label) ?></span>
                            <span class="font-mono text-mist w-10 text-right"><?= $votes ?></span>
                            <div class="w-32 md:w-40 h-3 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full <?= $color ?>" style="width: <?= $width ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Party style -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Styl imprezy</h3>
                    <div class="space-y-2">
                        <?php foreach ($partyOrder as $key):
                            $votes = $partyCounts[$key];
                            $label = $partyOpts[$key] ?? $key;
                            $pct = $count > 0 ? (int) round($votes / $count * 100) : 0;
                            $width = max(8, $pct);
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1 text-ink dark:text-pale"><?= e($label) ?></span>
                            <span class="font-mono text-mist w-10 text-right"><?= $votes ?></span>
                            <div class="w-32 md:w-40 h-3 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: <?= $width ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($warnings)): ?>
            <div class="rounded-2xl bg-accent/15 border border-accent/40 p-5 md:p-6">
                <h3 class="font-display font-bold text-base md:text-lg mb-3 text-ink dark:text-pale">Uwagi dla ekipy</h3>
                <ul class="space-y-2 text-sm md:text-base text-ink dark:text-pale">
                    <?php foreach ($warnings as $w): ?>
                        <li><?= e($w) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</section>
