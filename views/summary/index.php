<?php
/**
 * Strona podsumowania - 15 sekcji.
 * @var \App\Models\Trip $trip
 * @var \App\Services\SummaryAggregator $agg
 */
$dir = BASE_PATH . '/views/summary/sections/';
foreach ([
    '01-hero.php', '02-best-dates.php', '03-budget.php', '04-transport.php',
    '05-map.php', '05b-top-places.php', '06-style.php', '06b-character.php', '07-alcohol.php', '08-food.php',
    '09-fitness.php', '10-climate.php', '11-languages.php', '12-ranking.php', '12b-compatibility.php',
    '13-quotes.php', '14-weakest-link.php', '15-recommendations.php',
] as $f) {
    require $dir . $f;
}
?>
<!-- Hint nad stopka - inline padding bo Tailwind production build moze nie miec pb-24 -->
<div class="text-center text-xs text-mist space-y-1.5" style="padding: 8px 16px 96px;">
    <div>
        Wciśnij <kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">F</kbd> żeby uruchomić tryb prezentacji
    </div>
    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
        <span>
            <kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">↑</kbd>
            <kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">↓</kbd>
            nawigacja między sekcjami
        </span>
        <span class="text-mist/40">·</span>
        <span><kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">spacja</kbd> dalej</span>
        <span class="text-mist/40">·</span>
        <span><kbd class="px-1.5 py-0.5 rounded bg-mist/15 font-mono">ESC</kbd> wyjście z fullscreen</span>
    </div>
</div>
<?php /* Stopka renderowana z layout summary.php (landing v2 footer) - tu NIE dorzucamy */ ?>
