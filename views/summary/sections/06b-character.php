<?php
/**
 * Sekcja 6b: Charakter wyjazdu - oczekiwania, zdjecia, dzielenie wyjazdu.
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Helpers\QuestionLabels;

$count = $agg->completedCount();
$responses = $agg->completedResponses();

// Trip expectations (multi, max 3)
$expectOpts = QuestionLabels::get('trip_expectations')['options'] ?? [];
$expectCounts = array_fill_keys(array_keys($expectOpts), 0);
foreach ($responses as $resp) {
    $vs = $resp['trip_expectations'] ?? [];
    if (is_array($vs)) {
        foreach ($vs as $v) {
            if (isset($expectCounts[$v])) $expectCounts[$v]++;
        }
    }
}
arsort($expectCounts);

// Photo attitude (single)
$photoOpts = QuestionLabels::get('photo_attitude')['options'] ?? [];
$photoCounts = array_fill_keys(array_keys($photoOpts), 0);
foreach ($responses as $resp) {
    $v = $resp['photo_attitude'] ?? null;
    if (is_string($v) && isset($photoCounts[$v])) $photoCounts[$v]++;
}

// Social preference (multi)
$socialOpts = QuestionLabels::get('social_preference')['options'] ?? [];
$socialCounts = array_fill_keys(array_keys($socialOpts), 0);
foreach ($responses as $resp) {
    $vs = $resp['social_preference'] ?? [];
    if (is_array($vs)) {
        foreach ($vs as $v) {
            if (isset($socialCounts[$v])) $socialCounts[$v]++;
        }
    }
}
arsort($socialCounts);

$bar = static function (string $label, int $votes, int $total, string $color): string {
    if ($total === 0) return '';
    $pct = (int) round($votes / $total * 100);
    $width = max(8, $pct);
    return '<div class="flex items-center gap-3 text-sm">'
         . '<span class="flex-1 text-ink dark:text-pale">' . e($label) . '</span>'
         . '<span class="font-mono text-mist w-10 text-right">' . $votes . '</span>'
         . '<div class="w-32 md:w-40 h-2 bg-mist/15 rounded-full overflow-hidden">'
         . '<div class="h-full ' . $color . '" style="width: ' . $width . '%"></div>'
         . '</div></div>';
};
?>

<section class="section section--cream">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow eyebrow--teal"><span class="iconify" data-icon="ph:sparkle-bold"></span> Charakter wyjazdu</span>
            <h2 style="margin-top:18px">✨ Czego ekipa oczekuje</h2>
            <p>Jak chcecie fotografować, ile czasu razem, jak intensywnie.</p>
        </header>

        <?php if ($count === 0): ?>
            <p class="text-center text-mist italic">Brak danych.</p>
        <?php else: ?>

            <!-- Oczekiwania - duzy blok z tagami top -->
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-8 mb-5">
                <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">
                    🎯 Czego ekipa oczekuje
                    <span class="text-sm font-normal text-mist">(top wybory)</span>
                </h3>
                <div class="flex flex-wrap items-center gap-2">
                    <?php foreach ($expectCounts as $key => $votes):
                        if ($votes === 0) continue;
                        $label = $expectOpts[$key] ?? $key;
                        // Tagi - im wiecej osob zaznaczylo, tym wiekszy
                        $isTop    = $votes >= max(1, (int) ceil($count / 2));
                        $sizeCls  = $isTop ? 'text-base md:text-lg px-4 py-2' : 'text-sm px-3 py-1.5';
                        $colorCls = $isTop ? 'bg-primary-deep text-white' : 'bg-mist/15 text-ink dark:text-pale';
                    ?>
                    <span class="inline-flex items-center gap-1.5 rounded-full font-medium <?= $sizeCls ?> <?= $colorCls ?>">
                        <?= e($label) ?>
                        <span class="opacity-70 text-xs"><?= $votes ?>/<?= $count ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <p class="mt-4 text-sm text-mist">
                    Pomarańczowe tagi = większość ekipy chce. Te tagi powinny zdefiniować plan.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <!-- Photo attitude -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">📸 Stosunek do zdjęć</h3>
                    <div class="space-y-2">
                        <?php
                        $photoOrder = ['hate_posing', 'souvenir_only', 'casual_sharing', 'influencer_mode'];
                        foreach ($photoOrder as $key):
                            $votes = $photoCounts[$key] ?? 0;
                            if ($votes === 0) continue;
                            $color = match ($key) {
                                'hate_posing'      => 'bg-mist',
                                'souvenir_only'    => 'bg-secondary',
                                'casual_sharing'   => 'bg-primary',
                                'influencer_mode'  => 'bg-fuchsia-500',
                                default            => 'bg-mist',
                            };
                            echo $bar($photoOpts[$key] ?? $key, $votes, $count, $color);
                        endforeach;
                        ?>
                    </div>
                    <?php if (($photoCounts['hate_posing'] ?? 0) > 0 && ($photoCounts['influencer_mode'] ?? 0) > 0): ?>
                    <p class="mt-3 text-xs text-mist italic">⚠️ Mieszane podejście - dogadajcie się ile czasu na sesje.</p>
                    <?php endif; ?>
                </div>

                <!-- Social preference -->
                <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6">
                    <h3 class="font-display font-bold text-lg md:text-xl mb-4 text-ink dark:text-pale">👥 Z kim dzielisz wyjazd</h3>
                    <div class="space-y-2">
                        <?php foreach ($socialCounts as $key => $votes):
                            if ($votes === 0) continue;
                            $label = $socialOpts[$key] ?? $key;
                            $color = match ($key) {
                                'always_together'         => 'bg-primary',
                                'small_group_split_ok'    => 'bg-secondary',
                                'need_alone_time'         => 'bg-amber-400',
                                'ok_with_solo_activities' => 'bg-emerald-500',
                                default                   => 'bg-mist',
                            };
                            echo $bar($label, $votes, $count, $color);
                        endforeach; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</section>
