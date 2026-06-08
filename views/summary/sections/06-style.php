<?php
/**
 * Sekcja 6: Styl wyjazdu - krajobraz, tempo, nocleg, komfort.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->completedResponses();

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

<section class="section">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow"><span class="iconify" data-icon="ph:tent-bold"></span> Styl wyjazdu</span>
            <h2 style="margin-top:18px">🏕️ Jak chcecie spędzić czas</h2>
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

            <!-- Doswiadczenie podroznicze + Dzielenie pokoju (drugi rzad) -->
            <?php
            $expOpts = QuestionLabels::get('travel_experience')['options'] ?? [];
            $expOrder = ['first_time', 'europe_some', 'worldwide_some', 'globetrotter'];
            $expCounts = array_fill_keys($expOrder, 0);
            foreach ($responses as $resp) {
                $v = $resp['travel_experience'] ?? null;
                if (is_string($v) && isset($expCounts[$v])) $expCounts[$v]++;
            }
            $expRank = ['first_time' => 1, 'europe_some' => 2, 'worldwide_some' => 3, 'globetrotter' => 4];
            $values = [];
            foreach ($responses as $r) {
                $v = $r['travel_experience'] ?? null;
                if (is_string($v) && isset($expRank[$v])) $values[] = $expRank[$v];
            }
            $minRank = !empty($values) ? min($values) : 0;
            if ($minRank === 1) {
                $verdict = '🐣 W ekipie ktoś mało jeździł. Postawcie raczej na sprawdzone, blisko (UE).';
                $verdictColor = 'bg-amber-500/15 border-amber-400 dark:border-amber-700';
            } elseif ($minRank >= 3) {
                $verdict = '🌍 Cała ekipa to doświadczeni podróżnicy. Możecie ruszać w dalekie kierunki.';
                $verdictColor = 'bg-emerald-500/15 border-emerald-400 dark:border-emerald-700';
            } else {
                $verdict = '✈️ Mieszany skład. Europa to bezpieczny wybór, dalsze kraje OK ze wsparciem dla mniej obytych.';
                $verdictColor = 'bg-secondary/15 border-secondary/40';
            }

            // Room sharing
            $roomOpts = QuestionLabels::get('room_sharing')['options'] ?? [];
            $roomOrder = ['private_only', 'share_with_friends', 'dormitory_ok', 'no_bed_sharing'];
            $roomCounts = array_fill_keys($roomOrder, 0);
            foreach ($responses as $resp) {
                $v = $resp['room_sharing'] ?? null;
                if (is_string($v) && isset($roomCounts[$v])) $roomCounts[$v]++;
            }
            $privateOnly = $roomCounts['private_only'] ?? 0;
            $roomVerdict = '';
            $roomColor   = 'bg-mist/10 border-mist/30';
            if ($privateOnly > 0) {
                $roomVerdict = '🚪 ' . $privateOnly . ' os. wymaga osobnego pokoju - rezerwujcie noclegi z odpowiednią liczbą pokoi.';
                $roomColor   = 'bg-rose-500/10 border-rose-400 dark:border-rose-700';
            } elseif (($roomCounts['dormitory_ok'] ?? 0) === $count) {
                $roomVerdict = '🛏️ Cała ekipa OK z dormitorium - hostele to opcja, można sporo zaoszczędzić.';
                $roomColor   = 'bg-emerald-500/15 border-emerald-400 dark:border-emerald-700';
            } else {
                $roomVerdict = '👥 Ekipa OK z dzieleniem pokoju ze znajomymi - klasyczne apartamenty, jeden duży dom.';
                $roomColor   = 'bg-secondary/15 border-secondary/40';
            }
            ?>
            <div class="mt-5 grid md:grid-cols-2 gap-5">
                <!-- Doświadczenie podroznicze -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">🎒 Doświadczenie podróżnicze</h3>
                    <div class="space-y-2 mb-4">
                        <?php foreach ($expOrder as $key):
                            $votes = $expCounts[$key];
                            if ($votes === 0) continue;
                            $color = match ($key) {
                                'first_time'      => 'bg-amber-400',
                                'europe_some'     => 'bg-secondary',
                                'worldwide_some'  => 'bg-primary',
                                'globetrotter'    => 'bg-emerald-500',
                                default           => 'bg-mist',
                            };
                            echo $bar($expOpts[$key] ?? $key, $votes, $count, $color);
                        endforeach; ?>
                    </div>
                    <div class="rounded-xl <?= $verdictColor ?> border p-4 md:p-5 text-sm text-ink dark:text-pale leading-relaxed">
                        <?= e($verdict) ?>
                    </div>
                </div>

                <!-- Dzielenie pokoju -->
                <div class="rounded-2xl bg-cream dark:bg-night border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">🛏️ Dzielenie pokoju</h3>
                    <div class="space-y-2 mb-4">
                        <?php foreach ($roomOrder as $key):
                            $votes = $roomCounts[$key];
                            if ($votes === 0) continue;
                            $color = match ($key) {
                                'private_only'       => 'bg-rose-400',
                                'share_with_friends' => 'bg-secondary',
                                'dormitory_ok'       => 'bg-emerald-500',
                                'no_bed_sharing'     => 'bg-amber-400',
                                default              => 'bg-mist',
                            };
                            echo $bar($roomOpts[$key] ?? $key, $votes, $count, $color);
                        endforeach; ?>
                    </div>
                    <div class="rounded-xl <?= $roomColor ?> border p-4 md:p-5 text-sm text-ink dark:text-pale leading-relaxed">
                        <?= e($roomVerdict) ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
