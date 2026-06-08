<?php
/**
 * Sekcja 5b: Ranking miejsc - top 10 po sredniej ocenie.
 * Pokazuje podium (top 3) + reszta do pozycji 10.
 * Pomija miejsca bez ocen.
 *
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Models\TripPlace;
use App\Models\TripPlaceVote;

$trip = $agg->trip;
$places = TripPlace::listForTrip($trip->id);
$voteStats = TripPlaceVote::statsForTrip($trip->id, 0);
$votesPerParticipant = TripPlaceVote::votesByPlaceAndParticipant($trip->id);
$participants = $agg->participants();
$colors = $agg->colorMap();
$anonymous = $agg->isAnonymous();

$nicks = [];
$avatars = [];
foreach ($participants as $i => $p) {
    $nicks[$p->id]   = $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname;
    $avatars[$p->id] = (!$anonymous && $p->avatarPath) ? $p->avatarPath : null;
}

// Helper renderujacy avatar (img jezeli jest) lub kolorowe kolko z inicjalem
$renderAvatar = static function (int $participantId, int $sizePx = 24) use ($avatars, $nicks, $colors) {
    $nick   = $nicks[$participantId] ?? '?';
    $color  = $colors[$participantId] ?? '#FF6B35';
    $avatar = $avatars[$participantId] ?? null;
    $w = $sizePx;
    $fontPx = max(8, (int) round($sizePx * 0.42));
    if ($avatar !== null) {
        return '<img src="' . e(asset($avatar)) . '" alt="" '
             . 'class="rounded-full object-cover shrink-0 border-2" '
             . 'style="width:' . $w . 'px;height:' . $w . 'px;border-color:' . e($color) . '">';
    }
    return '<span class="inline-flex items-center justify-center rounded-full text-white font-bold shrink-0" '
         . 'style="width:' . $w . 'px;height:' . $w . 'px;font-size:' . $fontPx . 'px;background:' . e($color) . '">'
         . e(mb_strtoupper(mb_substr($nick, 0, 1)))
         . '</span>';
};

// Tylko miejsca z conajmniej jedna ocena
$ranked = [];
foreach ($places as $p) {
    $stats = $voteStats[$p->id] ?? ['avg' => null, 'count' => 0];
    if ($stats['avg'] === null || (int) $stats['count'] === 0) continue;
    $ranked[] = [
        'place'   => $p,
        'avg'     => (float) $stats['avg'],
        'count'   => (int) $stats['count'],
        'author'  => $nicks[$p->participantId] ?? '?',
        'color'   => $colors[$p->participantId] ?? '#FF6B35',
    ];
}
// Sort: avg DESC, przy remisie wygrywa miejsce na ktore zarezerwujemy WIECEJ czasu
// (im wiecej godzin tym wieksza atrakcja, np. caly dzien Plitvice > 30min photo stop).
// Trzeci tiebreaker: wiecej glosow = silniejszy konsensus.
usort($ranked, static function ($a, $b) {
    if ($a['avg'] !== $b['avg']) return $b['avg'] <=> $a['avg'];
    $aVm = (int) ($a['place']->visitMinutes ?? 60);
    $bVm = (int) ($b['place']->visitMinutes ?? 60);
    if ($aVm !== $bVm) return $bVm <=> $aVm;
    return $b['count'] <=> $a['count'];
});

$totalRanked = count($ranked);
$top3        = array_slice($ranked, 0, 3);
$restAll     = array_slice($ranked, 3);          // wszystkie >= pozycja 4
$initialVisible = 7;                              // domyslnie pokazujemy 4..10
$restInitial = array_slice($restAll, 0, $initialVisible);
$restHidden  = array_slice($restAll, $initialVisible);
$hiddenCount = count($restHidden);

// Pomocnicze: format avg w polskim formacie
$fmt = static fn(float $v) => number_format($v, 1, ',', '');
// Czy "absolutny faworyt" (avg >= 5.0 z conajmniej 2 glosami)
$isFavorite = static fn(array $r) => $r['avg'] >= 5.0 && $r['count'] >= 2;
?>

<section class="section section--cream" data-summary-animate>
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow"><span class="iconify" data-icon="ph:trophy-bold"></span> Ranking</span>
            <h2 style="margin-top:18px">🏆 Top miejsca ekipy</h2>
            <p>Najlepiej ocenione atrakcje na podstawie głosów ekipy. To są miejsca, których nie chcecie przegapić.</p>
        </header>

        <?php if (empty($ranked)): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border-2 border-dashed border-mist/30 p-8 text-center">
                <div class="text-4xl mb-3">⭐</div>
                <p class="text-mist">
                    Nikt jeszcze nie ocenił żadnego miejsca. Wejdźcie na <code class="text-primary">/atrakcje/oceniaj</code> przez wasze linki uczestnika żeby zacząć.
                </p>
            </div>
        <?php else: ?>

        <!-- Podium top 3 -->
        <?php if (!empty($top3)): ?>
        <div class="grid sm:grid-cols-3 gap-4 md:gap-6 mb-8">
            <?php
            // Reorder: 2. miejsce po lewej, 1. w srodku, 3. po prawej (efekt podium)
            // Na mobile zostawiamy 1->2->3 (jeden pod drugim)
            $podiumOrder = count($top3) === 3 ? [1, 0, 2] : array_keys($top3);
            $podiumStyles = [
                0 => ['rank' => '🥇', 'gradient' => 'from-amber-200 to-amber-100 dark:from-amber-900/40 dark:to-amber-800/30', 'border' => 'border-amber-400 dark:border-amber-600', 'pill' => 'bg-amber-400 text-amber-950', 'scale' => 'sm:scale-105 sm:-mt-2'],
                1 => ['rank' => '🥈', 'gradient' => 'from-slate-200 to-slate-100 dark:from-slate-700/40 dark:to-slate-700/20', 'border' => 'border-slate-400 dark:border-slate-500', 'pill' => 'bg-slate-400 text-slate-950', 'scale' => ''],
                2 => ['rank' => '🥉', 'gradient' => 'from-orange-200 to-orange-100 dark:from-orange-900/30 dark:to-orange-900/10', 'border' => 'border-orange-400 dark:border-orange-700', 'pill' => 'bg-orange-400 text-orange-950', 'scale' => ''],
            ];
            foreach ($podiumOrder as $idx):
                $r = $top3[$idx];
                $s = $podiumStyles[$idx];
                $p = $r['place'];
            ?>
            <article class="relative rounded-3xl border-2 <?= e($s['border']) ?> bg-gradient-to-br <?= e($s['gradient']) ?> p-5 md:p-6 transition <?= e($s['scale']) ?>"
                     data-summary-animate>
                <div class="absolute -top-3 left-5 inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?= e($s['pill']) ?>">
                    <?= e($s['rank']) ?> Miejsce <?= $idx + 1 ?>
                </div>
                <?php if ($isFavorite($r)): ?>
                <div class="absolute -top-3 right-5 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-secondary text-white">
                    ⚡ ABSOLUTNY FAWORYT
                </div>
                <?php endif; ?>

                <div class="mt-3">
                    <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale leading-tight mb-1">
                        <?= e($p->name) ?>
                    </h3>
                    <?php if ($p->address): ?>
                        <p class="text-xs text-mist truncate mb-3"><?= e($p->address) ?></p>
                    <?php endif; ?>

                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-amber-500 text-2xl md:text-3xl font-bold leading-none">★</span>
                        <span class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale leading-none"><?= $fmt($r['avg']) ?></span>
                        <span class="text-sm text-mist self-end pb-0.5">/ 5,0</span>
                        <span class="ml-auto text-xs text-mist">
                            <?= $r['count'] ?> <?= $r['count'] === 1 ? 'głos' : ($r['count'] < 5 ? 'głosy' : 'głosów') ?>
                        </span>
                    </div>

                    <?php if ($p->description): ?>
                        <p class="text-sm text-ink/80 dark:text-pale/80 leading-relaxed mb-3 line-clamp-2">
                            <?= e($p->description) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Kto jak zaglosowal -->
                    <?php
                    $placeVotes = $votesPerParticipant[$p->id] ?? [];
                    if (!empty($placeVotes)):
                    ?>
                    <div class="rounded-xl bg-paper/60 dark:bg-deep/60 backdrop-blur-sm border border-ink/10 dark:border-pale/10 p-2.5 mb-3 space-y-1.5">
                        <?php foreach ($participants as $px):
                            if (!isset($placeVotes[$px->id])) continue;
                            $score = $placeVotes[$px->id];
                            $voterNick = $nicks[$px->id] ?? '?';
                            $voterColor = $colors[$px->id] ?? '#FF6B35';
                            $pct = ($score / 5.0) * 100;
                        ?>
                        <div class="flex items-center gap-2">
                            <?= $renderAvatar($px->id, 20) ?>
                            <span class="text-xs text-ink/80 dark:text-pale/80 w-16 truncate"><?= e($voterNick) ?></span>
                            <div class="flex-1 h-1.5 bg-mist/20 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width: <?= $pct ?>%; background: <?= e($voterColor) ?>"></div>
                            </div>
                            <span class="text-xs font-mono font-bold text-amber-500 shrink-0 w-8 text-right"><?= $fmt($score) ?>★</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-2 pt-3 border-t border-ink/10 dark:border-pale/10">
                        <?= $renderAvatar($p->participantId, 24) ?>
                        <span class="text-xs text-mist">dodał(a): <span class="font-medium text-ink/80 dark:text-pale/80"><?= e($r['author']) ?></span></span>
                        <span class="ml-auto inline-flex items-center gap-1 text-xs text-mist">
                            ⏱️ <?php
                                $vm = $p->visitMinutes;
                                echo $vm < 60 ? $vm . 'min' : ($vm % 60 === 0 ? ($vm / 60) . 'h' : sprintf('%.1fh', $vm / 60));
                            ?>
                        </span>
                    </div>

                    <button type="button" data-summary-detail="<?= e($p->id) ?>"
                            class="w-full mt-3 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-ink/5 dark:bg-pale/5 hover:bg-ink/10 dark:hover:bg-pale/10 text-xs font-semibold text-ink dark:text-pale transition">
                        📷 Zobacz galerię i szczegóły →
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Reszta - pozycje 4 i dalej (do 10 widoczne domyslnie, reszta po kliknieciu) -->
        <?php if (!empty($restAll)):
            // Helper renderujacy jeden wiersz
            $renderRow = static function (array $r, int $position) use ($isFavorite, $fmt, $participants, $votesPerParticipant, $nicks, $colors, $renderAvatar) {
                $p = $r['place'];
                $placeVotes = $votesPerParticipant[$p->id] ?? [];
        ?>
                <details class="group">
                    <summary class="list-none cursor-pointer flex items-center gap-3 px-5 py-3.5 hover:bg-mist/5 transition">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-mist/15 text-ink dark:text-pale text-sm font-bold shrink-0">
                            <?= $position ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-semibold text-sm text-ink dark:text-pale truncate"><?= e($p->name) ?></h4>
                                <?php if ($isFavorite($r)): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-secondary/15 text-secondary">⚡ FAWORYT</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($p->address): ?>
                                <p class="text-xs text-mist truncate"><?= e($p->address) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="text-right">
                                <div class="font-mono font-bold text-base text-amber-500 leading-none">
                                    ★ <?= $fmt($r['avg']) ?>
                                </div>
                                <div class="text-[10px] text-mist mt-0.5">
                                    <?= $r['count'] ?> <?= $r['count'] === 1 ? 'głos' : ($r['count'] < 5 ? 'głosy' : 'głosów') ?>
                                </div>
                            </div>
                            <span title="<?= e($r['author']) ?>"><?= $renderAvatar($p->participantId, 24) ?></span>
                            <button type="button" data-summary-detail="<?= e($p->id) ?>"
                                    class="inline-flex items-center justify-center w-7 h-7 rounded-full hover:bg-primary/15 text-mist hover:text-primary transition shrink-0"
                                    title="Zobacz galerię i szczegóły"
                                    onclick="event.stopPropagation()">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            </button>
                            <svg class="w-4 h-4 text-mist transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </summary>
                    <?php if (!empty($placeVotes)): ?>
                    <div class="px-5 pb-3 pt-1 space-y-1 bg-mist/[0.03]">
                        <?php foreach ($participants as $px):
                            if (!isset($placeVotes[$px->id])) continue;
                            $score = $placeVotes[$px->id];
                            $voterNick = $nicks[$px->id] ?? '?';
                            $voterColor = $colors[$px->id] ?? '#FF6B35';
                            $pct = ($score / 5.0) * 100;
                        ?>
                        <div class="flex items-center gap-2 ml-11">
                            <?= $renderAvatar($px->id, 20) ?>
                            <span class="text-xs text-ink/70 dark:text-pale/70 w-20 truncate"><?= e($voterNick) ?></span>
                            <div class="flex-1 h-1.5 bg-mist/20 rounded-full overflow-hidden max-w-[200px]">
                                <div class="h-full rounded-full" style="width: <?= $pct ?>%; background: <?= e($voterColor) ?>"></div>
                            </div>
                            <span class="text-xs font-mono font-bold text-amber-500 w-8 text-right"><?= $fmt($score) ?>★</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </details>
        <?php };
        ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 overflow-hidden" data-summary-animate>
            <div class="px-5 py-4 border-b border-mist/15 flex items-center justify-between">
                <h3 class="font-display font-bold text-base text-ink dark:text-pale">Kolejne miejsca w rankingu</h3>
                <span class="text-xs text-mist">pozycje 4–<?= 3 + count($restAll) ?></span>
            </div>
            <div class="divide-y divide-mist/10" id="ranking-rest-visible">
                <?php foreach ($restInitial as $i => $r) $renderRow($r, 4 + $i); ?>
            </div>
            <?php if ($hiddenCount > 0): ?>
            <div class="divide-y divide-mist/10 hidden" id="ranking-rest-hidden">
                <?php foreach ($restHidden as $i => $r) $renderRow($r, 4 + $initialVisible + $i); ?>
            </div>
            <div class="px-5 py-4 border-t border-mist/15 text-center" id="ranking-load-more-wrap">
                <button type="button" id="ranking-load-more"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-mist/15 hover:bg-mist/25 text-sm font-semibold text-ink dark:text-pale transition">
                    Pokaż wszystkie <?= $hiddenCount ?> kolejne miejsc
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
            <script>
            (function () {
                const btn = document.getElementById('ranking-load-more');
                const hidden = document.getElementById('ranking-rest-hidden');
                const wrap = document.getElementById('ranking-load-more-wrap');
                if (!btn || !hidden) return;
                btn.addEventListener('click', () => {
                    hidden.classList.remove('hidden');
                    wrap?.classList.add('hidden');
                });
            })();
            </script>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p class="mt-4 text-center text-xs text-mist">
            Każde miejsce kliknij żeby zobaczyć kto jak zagłosował · razem <?= $totalRanked ?> ocenionych miejsc
        </p>

        <?php endif; ?>
    </div>
</section>
