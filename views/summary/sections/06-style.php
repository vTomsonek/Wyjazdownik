<?php
/**
 * Sekcja 6: Styl wyjazdu - krajobraz, tempo, nocleg, komfort.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->allResponses();

// Helper: zlicz multi-choice
$countMulti = static function (array $responses, string $key): array {
    $opts = QuestionLabels::get($key)['options'] ?? [];
    $tally = array_fill_keys(array_keys($opts), 0);
    foreach ($responses as $resp) {
        $vs = $resp[$key] ?? [];
        if (is_array($vs)) {
            foreach ($vs as $v) {
                if (isset($tally[$v])) $tally[$v]++;
            }
        }
    }
    arsort($tally);
    return $tally;
};

// Helper: zlicz single-choice
$countSingle = static function (array $responses, string $key): array {
    $opts = QuestionLabels::get($key)['options'] ?? [];
    $tally = array_fill_keys(array_keys($opts), 0);
    foreach ($responses as $resp) {
        $v = $resp[$key] ?? null;
        if (is_string($v) && isset($tally[$v])) $tally[$v]++;
    }
    arsort($tally);
    return $tally;
};

$landscapes  = $countMulti($responses, 'landscape_preferences');
$accom       = $countMulti($responses, 'accommodation');
$paces       = $countSingle($responses, 'pace');
$comforts    = $countSingle($responses, 'comfort_level');

$landOpts    = QuestionLabels::get('landscape_preferences')['options'] ?? [];
$accomOpts   = QuestionLabels::get('accommodation')['options'] ?? [];
$paceOpts    = QuestionLabels::get('pace')['options'] ?? [];
$comfortOpts = QuestionLabels::get('comfort_level')['options'] ?? [];

$bar = static function (string $label, int $votes, int $total, string $color): string {
    if ($total === 0) return '';
    $pct = (int) round($votes / $total * 100);
    $width = max(15, $pct);
    return '<div class="flex items-center gap-3 text-sm">'
         . '<span class="flex-1 text-ink dark:text-pale">' . e($label) . '</span>'
         . '<span class="font-mono text-mist w-10 text-right">' . $votes . '</span>'
         . '<div class="w-32 md:w-40 h-2 bg-mist/15 rounded-full overflow-hidden">'
         . '<div class="h-full ' . $color . '" style="width: ' . $width . '%"></div>'
         . '</div></div>';
};
?>

<section class="bg-paper dark:bg-deep py-16 md:py-24 3xl:py-32 border-t border-mist/15">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-accent/20 text-amber-700 dark:text-accent">SEKCJA 6 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🏕️ Styl wyjazdu
            </h2>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Brak danych do pokazania.</p>
        <?php else: ?>

            <div class="grid md:grid-cols-2 gap-5">
                <!-- Krajobrazy -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Co chcecie zobaczyć</h3>
                    <div class="space-y-2">
                        <?php foreach ($landscapes as $key => $votes): if ($votes === 0) continue;
                            echo $bar($landOpts[$key] ?? $key, $votes, $count, 'bg-secondary');
                        endforeach; ?>
                    </div>
                </div>

                <!-- Nocleg -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Gdzie chcą spać</h3>
                    <div class="space-y-2">
                        <?php foreach ($accom as $key => $votes): if ($votes === 0) continue;
                            echo $bar($accomOpts[$key] ?? $key, $votes, $count, 'bg-primary');
                        endforeach; ?>
                    </div>
                </div>

                <!-- Tempo -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Tempo</h3>
                    <div class="space-y-2">
                        <?php foreach ($paces as $key => $votes): if ($votes === 0) continue;
                            echo $bar($paceOpts[$key] ?? $key, $votes, $count, 'bg-accent');
                        endforeach; ?>
                    </div>
                </div>

                <!-- Komfort -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Wymagany komfort</h3>
                    <div class="space-y-2">
                        <?php foreach ($comforts as $key => $votes): if ($votes === 0) continue;
                            echo $bar($comfortOpts[$key] ?? $key, $votes, $count, 'bg-rose-400');
                        endforeach; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
