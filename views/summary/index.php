<?php
/**
 * Strona podsumowania - 15 sekcji.
 * @var \App\Models\Trip $trip
 * @var \App\Services\SummaryAggregator $agg
 */
$dir = BASE_PATH . '/views/summary/sections/';
foreach ([
    '01-hero.php', '02-best-dates.php', '03-budget.php', '04-transport.php',
    '05-map.php', '06-style.php', '07-alcohol.php', '08-food.php',
    '09-fitness.php', '10-climate.php', '11-languages.php', '12-ranking.php',
    '13-quotes.php', '14-weakest-link.php', '15-recommendations.php',
] as $f) {
    require $dir . $f;
}
?>
<footer class="text-center py-10 text-mist text-sm">
    Wyjazdownik.pl &middot; podsumowanie &middot; <?= e(date('d.m.Y H:i')) ?>
    <br>
    <span class="text-xs">Wciśnij <kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">F</kbd> żeby uruchomić tryb prezentacji</span>
</footer>
