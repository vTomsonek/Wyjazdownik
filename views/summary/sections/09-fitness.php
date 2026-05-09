<?php
/**
 * Sekcja 9: Forma fizyczna - km/dzien (najslabsze ogniwo) + aktywnosci.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$participants = $agg->participants();
$responses = $agg->allResponses();
$anonymous = $agg->isAnonymous();

$walkOpts = QuestionLabels::get('daily_walking_capacity')['options'] ?? [];
$walkOrder = ['under_3km', '3_7km', '7_15km', '15_25km', 'over_25km'];
$walkPerKm = ['under_3km' => 3, '3_7km' => 7, '7_15km' => 15, '15_25km' => 25, 'over_25km' => 30];

// Per uczestnik km
$perPerson = [];
foreach ($participants as $i => $p) {
    $key = $responses[$p->id]['daily_walking_capacity'] ?? null;
    if (!is_string($key) || !isset($walkPerKm[$key])) continue;
    $perPerson[] = [
        'name'  => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
        'key'   => $key,
        'km'    => $walkPerKm[$key],
        'label' => $walkOpts[$key] ?? $key,
    ];
}
usort($perPerson, static fn($a, $b) => $a['km'] <=> $b['km']);
$weakest = $perPerson[0]['km'] ?? 0;
$weakestPerson = $perPerson[0] ?? null;

// Aktywnosci - intersect (ktore zaznaczyla wiekszosc)
$actOpts = QuestionLabels::get('physical_activities')['options'] ?? [];
$actCounts = array_fill_keys(array_keys($actOpts), 0);
foreach ($responses as $resp) {
    $vs = $resp['physical_activities'] ?? [];
    if (is_array($vs)) {
        foreach ($vs as $v) {
            if (isset($actCounts[$v])) $actCounts[$v]++;
        }
    }
}
arsort($actCounts);
$majorityThreshold = (int) ceil($count / 2);
?>

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-600">SEKCJA 9 / 15</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🥾 Forma fizyczna
            </h2>
        </header>

        <?php if ($count === 0 || empty($perPerson)): ?>
            <p class="text-center text-mist italic">Brak danych.</p>
        <?php else: ?>

            <!-- Najslabsze ogniwo - duzy banner -->
            <div class="mb-8 rounded-2xl bg-gradient-to-br from-amber-100 to-rose-100 dark:from-amber-950/30 dark:to-rose-950/30 border-2 border-amber-400 dark:border-amber-700 p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="text-5xl md:text-6xl">🎯</div>
                    <div class="flex-1">
                        <p class="text-xs uppercase tracking-wider text-mist font-semibold mb-1">Najsłabsze ogniwo</p>
                        <h3 class="font-display font-bold text-2xl md:text-4xl text-ink dark:text-pale mb-1">
                            Max <?= $weakest ?> km / dzień
                        </h3>
                        <p class="text-sm md:text-base text-mist">
                            Planujcie z myślą o najmniej wytrzymałej osobie z ekipy
                            <?php if ($weakestPerson && !$anonymous): ?>(<?= e($weakestPerson['name']) ?>)<?php endif; ?>.
                            Inaczej ktoś polegnie po pierwszym dniu.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5 mb-6">
                <!-- Km/dzien per uczestnik -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Wytrzymałość per osoba</h3>
                    <div class="space-y-2">
                        <?php
                        // Sortuj rosnaco
                        foreach ($perPerson as $pp):
                            $ratio = $pp['km'] / 30;
                            $color = $pp['km'] <= 3 ? 'bg-rose-400' : ($pp['km'] <= 7 ? 'bg-amber-400' : ($pp['km'] <= 15 ? 'bg-secondary' : 'bg-emerald-500'));
                        ?>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-24 md:w-28 shrink-0 truncate text-ink dark:text-pale"><?= e($pp['name']) ?></span>
                            <div class="flex-1 h-7 bg-mist/15 rounded-full overflow-hidden">
                                <div class="h-full rounded-full flex items-center justify-end px-3 text-white font-mono font-semibold text-xs <?= $color ?>"
                                     style="width: <?= max(15, $ratio * 100) ?>%">
                                    <?= $pp['km'] ?> km
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Aktywnosci - tylko te ktore wiekszosc zaznaczyla -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">Co lubi większość ekipy</h3>
                    <?php
                    $majority = array_filter($actCounts, static fn($c) => $c >= $majorityThreshold);
                    if (empty($majority)): ?>
                        <p class="text-mist italic text-sm">Brak aktywności którą lubi większość - każdy ma inny pomysł.</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($majority as $key => $cnt):
                                $label = $actOpts[$key] ?? $key;
                            ?>
                            <span class="px-3 py-1.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-sm font-medium">
                                <?= e($label) ?> <span class="text-xs opacity-70"><?= $cnt ?>/<?= $count ?></span>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
