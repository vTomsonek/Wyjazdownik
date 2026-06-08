<?php
/**
 * Sekcja 12b: Kompatybilnosc ekipy - kto z kim sie dogada, kto outsiderem.
 *
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Services\CompatibilityService;
use App\Services\MapColorService;

$compat = new CompatibilityService($agg);
if (!$compat->isAvailable()) return; // <3 wypelnionych - nie ma sensu

$anonymous = $agg->isAnonymous();
$participants = $compat->participants();
$colors = $agg->colorMap();

$nick = static function ($p, $i) use ($anonymous) {
    return $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname;
};

/**
 * Render awatar: zdjecie (jesli jest), inaczej kolorowa litera/numer.
 * @param int $sizePx rozmiar w px
 * @param int $borderPx grubosc ramki
 */
$renderAvatar = static function ($p, int $i, string $color, int $sizePx, int $borderPx = 3) use ($anonymous) {
    $initial = $anonymous ? (string) ($i + 1) : mb_strtoupper(mb_substr($p->nickname, 0, 1));
    if (!$anonymous && $p->avatarPath) {
        $src = asset($p->avatarPath);
        return '<span style="display:inline-flex;width:' . $sizePx . 'px;height:' . $sizePx . 'px;border-radius:50%;overflow:hidden;border:' . $borderPx . 'px solid ' . e($color) . ';box-shadow:0 2px 6px rgba(0,0,0,.15);box-sizing:border-box">'
             . '<img src="' . e($src) . '" alt="" style="width:100%;height:100%;object-fit:cover">'
             . '</span>';
    }
    $fontSize = max(12, (int) round($sizePx * 0.42));
    return '<span style="display:inline-flex;align-items:center;justify-content:center;width:' . $sizePx . 'px;height:' . $sizePx . 'px;border-radius:50%;background:' . e($color) . ';color:#fff;font-family:var(--font-display);font-weight:800;font-size:' . $fontSize . 'px;border:' . $borderPx . 'px solid ' . e($color) . ';box-shadow:0 2px 6px rgba(0,0,0,.15);box-sizing:border-box">'
         . e($initial)
         . '</span>';
};

// Index participantów per id dla widoku
$idxById = [];
foreach ($participants as $i => $p) $idxById[$p->id] = $i;

$top    = $compat->topPairs(3);
$bottom = $compat->bottomPairs(3);
$ranking = $compat->ranking();
$outsider = $compat->outsider();
$matrix = $compat->matrix();

// Funkcja kolor heatmap z var landing
$heatColor = static function (float $score): string {
    // 0 = czerwony, 0.5 = zolty, 1 = zielony
    if ($score >= 0.80) return '#10B981';     // emerald
    if ($score >= 0.65) return '#34D399';     // emerald-400
    if ($score >= 0.50) return '#FACC15';     // yellow-400
    if ($score >= 0.35) return '#FB923C';     // orange-400
    return '#F87171';                          // red-400
};

$rankIcon = ['🥇', '🥈', '🥉'];
$badIcon  = ['💔', '🥊', '😬'];

// Pre-compute breakdowns dla wszystkich par - bedziemy embedować jako JSON dla JS
$pairData = [];
$count = count($participants);
for ($i = 0; $i < $count; $i++) {
    for ($j = $i + 1; $j < $count; $j++) {
        $a = $participants[$i];
        $b = $participants[$j];
        $breakdown = $compat->pairBreakdown($a, $b);
        // Sortuj: zgodni (high sim) na początku malejąco, niezgodni rosnąco na końcu
        usort($breakdown, static fn($x, $y) => $y['similarity'] <=> $x['similarity']);
        $score = $matrix[$a->id][$b->id] ?? 0;
        $key = $a->id . '-' . $b->id;
        $voteData = $compat->voteSimilarity($a, $b);
        $pairData[$key] = [
            'a_id'     => $a->id,
            'b_id'     => $b->id,
            'a_name'   => $nick($a, $i),
            'b_name'   => $nick($b, $j),
            'a_color'  => $colors[$a->id] ?? '#FF6B35',
            'b_color'  => $colors[$b->id] ?? '#0E9BAA',
            'a_avatar' => (!$anonymous && $a->avatarPath) ? asset($a->avatarPath) : null,
            'b_avatar' => (!$anonymous && $b->avatarPath) ? asset($b->avatarPath) : null,
            'score'    => $score,
            'rows'     => $breakdown,
            'votes'    => $voteData, // null jesli brak wspolnych glosow
        ];
    }
}
$pairDataJson = json_encode($pairData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<section class="section">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow eyebrow--teal"><span class="iconify" data-icon="ph:users-three-bold"></span> Kompatybilność</span>
            <h2 style="margin-top:18px">💞 Z kim się dogadacie</h2>
            <p>Algorytm porównał wszystkie odpowiedzi ekipy i znalazł najlepsze duety oraz najmniej dopasowanego outsider'a.</p>
        </header>

        <!-- TOP 3 NAJLEPSZE DUETY -->
        <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mt-4 mb-5 text-center">
            🤝 Bratnie dusze ekipy
        </h3>
        <div class="grid md:grid-cols-3 gap-4 mb-12">
            <?php foreach ($top as $idx => $pair):
                $a = $pair['a']; $b = $pair['b'];
                $ai = $idxById[$a->id] ?? 0;
                $bi = $idxById[$b->id] ?? 0;
                $ca = $colors[$a->id] ?? '#FF6B35';
                $cb = $colors[$b->id] ?? '#0E9BAA';
                $pct = (int) round($pair['score'] * 100);
            ?>
            <button type="button"
                    class="rounded-2xl bg-cream dark:bg-night border-2 border-mist/15 p-5 hover:border-secondary/40 transition flex flex-col items-center text-center w-full cursor-pointer"
                    data-pair-key="<?= e($a->id . '-' . $b->id) ?>" data-pair-trigger>
                <div class="text-3xl mb-2"><?= $rankIcon[$idx] ?? '⭐' ?></div>
                <div class="flex items-center justify-center -space-x-3 mb-3">
                    <?= $renderAvatar($a, $ai, $ca, 56, 3) ?>
                    <?= $renderAvatar($b, $bi, $cb, 56, 3) ?>
                </div>
                <div class="font-display font-bold text-lg text-ink dark:text-pale mb-1">
                    <?= e($nick($a, $ai)) ?> & <?= e($nick($b, $bi)) ?>
                </div>
                <div class="font-mono text-2xl font-bold" style="color: <?= e($heatColor($pair['score'])) ?>">
                    <?= $pct ?>%
                </div>
                <div class="text-xs text-mist mt-1">dopasowania</div>
                <div class="text-[11px] text-mist/70 mt-3 flex items-center gap-1 justify-center">
                    <span class="iconify" data-icon="ph:cursor-click-bold" style="font-size:11px"></span>
                    kliknij dla szczegółów
                </div>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- TOP 3 NAJGORSZE DUETY -->
        <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mt-4 mb-2 text-center">
            🔥 Niedopasowane pary
        </h3>
        <p class="text-center text-mist text-sm mb-5">Daleko sobie na osi preferencji. Lepiej w osobnych pokojach.</p>
        <div class="grid md:grid-cols-3 gap-4 mb-12">
            <?php foreach ($bottom as $idx => $pair):
                $a = $pair['a']; $b = $pair['b'];
                $ai = $idxById[$a->id] ?? 0;
                $bi = $idxById[$b->id] ?? 0;
                $ca = $colors[$a->id] ?? '#FF6B35';
                $cb = $colors[$b->id] ?? '#0E9BAA';
                $pct = (int) round($pair['score'] * 100);
            ?>
            <button type="button"
                    class="rounded-2xl bg-rose-50 dark:bg-rose-950/30 border-2 border-rose-200/60 dark:border-rose-800/40 p-5 flex flex-col items-center text-center w-full cursor-pointer hover:border-rose-400/60 transition"
                    data-pair-key="<?= e($a->id . '-' . $b->id) ?>" data-pair-trigger>
                <div class="text-3xl mb-2"><?= $badIcon[$idx] ?? '😬' ?></div>
                <div class="flex items-center justify-center -space-x-3 mb-3">
                    <?= $renderAvatar($a, $ai, $ca, 56, 3) ?>
                    <?= $renderAvatar($b, $bi, $cb, 56, 3) ?>
                </div>
                <div class="font-display font-bold text-lg text-ink dark:text-pale mb-1">
                    <?= e($nick($a, $ai)) ?> & <?= e($nick($b, $bi)) ?>
                </div>
                <div class="font-mono text-2xl font-bold" style="color: <?= e($heatColor($pair['score'])) ?>">
                    <?= $pct ?>%
                </div>
                <div class="text-xs text-mist mt-1">dopasowania</div>
                <div class="text-[11px] text-mist/70 mt-3 flex items-center gap-1 justify-center">
                    <span class="iconify" data-icon="ph:cursor-click-bold" style="font-size:11px"></span>
                    kliknij dla szczegółów
                </div>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- RANKING FIT Z GRUPĄ -->
        <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mt-6 mb-5 text-center">
            🎯 Kto najbardziej pasuje do reszty
        </h3>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 md:p-6 mb-10">
            <ol class="space-y-3">
                <?php foreach ($ranking as $rkIdx => $row):
                    $p   = $row['participant'];
                    $pIdx = $idxById[$p->id] ?? 0;
                    $col  = $colors[$p->id] ?? '#FF6B35';
                    $pct  = (int) round($row['fit'] * 100);
                ?>
                <li class="flex items-center gap-3">
                    <span class="font-mono text-mist w-6 text-center"><?= ($rkIdx + 1) ?>.</span>
                    <?= $renderAvatar($p, $pIdx, $col, 36, 2) ?>
                    <span class="font-display font-bold text-ink dark:text-pale flex-1 truncate"><?= e($nick($p, $pIdx)) ?></span>
                    <div class="flex-1 h-3 max-w-[280px] bg-mist/15 rounded-full overflow-hidden">
                        <div class="h-full rounded-full" style="width: <?= $pct ?>%; background: <?= e($heatColor($row['fit'])) ?>"></div>
                    </div>
                    <span class="font-mono font-bold text-sm w-12 text-right" style="color: <?= e($heatColor($row['fit'])) ?>"><?= $pct ?>%</span>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- OUTSIDER CARD -->
        <?php if ($outsider !== null):
            $o     = $outsider['participant'];
            $oIdx  = $idxById[$o->id] ?? 0;
            $oCol  = $colors[$o->id] ?? '#FF6B35';
            $oFit  = (int) round($outsider['fit'] * 100);
            $reasons = $outsider['reasons'];
            $oName = $nick($o, $oIdx);

            // Adaptive title + ton w zaleznosci od fit score
            $fitScore = $outsider['fit'];
            if ($fitScore >= 0.75) {
                $sectionTitle = 'Najmniej dopasowany';
                $sectionIcon = '🤔';
                $sectionSub  = 'Ale i tak dobrze pasuje do ekipy — różnice są drobne.';
                $gradFrom = 'rgba(14,155,170,.10)';
                $gradTo   = 'rgba(255,210,63,.08)';
                $borderC  = 'rgba(14,155,170,.20)';
            } elseif ($fitScore >= 0.55) {
                $sectionTitle = 'Outsider ekipy';
                $sectionIcon = '🚪';
                $sectionSub  = 'Najbardziej różni się od reszty. Warto wiedzieć gdzie ekipa idzie na kompromis.';
                $gradFrom = 'rgba(255,107,53,.10)';
                $gradTo   = 'rgba(255,210,63,.10)';
                $borderC  = 'rgba(255,107,53,.25)';
            } else {
                $sectionTitle = 'Słabe ogniwo';
                $sectionIcon = '🔥';
                $sectionSub  = 'Mocno odstaje od reszty — wymaga rozmowy zanim ekipa kupi bilety.';
                $gradFrom = 'rgba(244,63,94,.12)';
                $gradTo   = 'rgba(255,107,53,.10)';
                $borderC  = 'rgba(244,63,94,.30)';
            }
        ?>
        <div class="rounded-2xl p-6 md:p-8 mb-6 relative overflow-hidden"
             style="background: linear-gradient(135deg, <?= $gradFrom ?>, <?= $gradTo ?>); border: 2px solid <?= $borderC ?>;">
            <div class="text-center mb-5">
                <div class="text-4xl mb-2"><?= $sectionIcon ?></div>
                <h3 class="font-display font-bold text-2xl md:text-3xl text-ink dark:text-pale">
                    <?= e($sectionTitle) ?>
                </h3>
                <p class="text-mist text-sm mt-2 max-w-xl mx-auto"><?= e($sectionSub) ?></p>
            </div>

            <div class="flex flex-col items-center mb-6">
                <?= $renderAvatar($o, $oIdx, $oCol, 80, 4) ?>
                <div class="font-display font-bold text-2xl text-ink dark:text-pale mt-3">
                    <?= e($oName) ?>
                </div>
                <div class="font-mono text-xl font-bold mt-1" style="color: <?= e($heatColor($fitScore)) ?>">
                    Średnie dopasowanie: <?= $oFit ?>%
                </div>
            </div>

            <?php if (!empty($reasons)): ?>
            <div class="bg-paper/60 dark:bg-deep/60 rounded-xl p-4 md:p-6 max-w-3xl mx-auto">
                <div class="font-display font-bold text-base text-ink dark:text-pale mb-4 text-center">
                    Czym się różni od reszty
                </div>
                <ul class="space-y-3">
                    <?php foreach ($reasons as $r): ?>
                    <li class="border-b border-mist/10 last:border-0 pb-3 last:pb-0">
                        <div class="font-semibold text-ink dark:text-pale text-sm mb-1.5">
                            📌 <?= e($r['label']) ?>
                        </div>
                        <?php if ($r['type'] === 'ordinal'): ?>
                            <div class="text-sm grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-mist shrink-0"><?= e($oName) ?>:</span>
                                    <span class="text-rose-600 dark:text-rose-400 font-medium"><?= e($r['own']) ?></span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-mist shrink-0">reszta:</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium"><?= e($r['group']) ?></span>
                                </div>
                            </div>
                        <?php elseif ($r['type'] === 'only_outsider'): ?>
                            <div class="text-sm flex items-baseline gap-2 flex-wrap">
                                <span class="text-xs text-mist shrink-0">Tylko <?= e($oName) ?> wybrał:</span>
                                <span class="text-amber-600 dark:text-amber-400 font-medium"><?= e($r['own']) ?></span>
                            </div>
                        <?php elseif ($r['type'] === 'only_group'): ?>
                            <div class="text-sm flex items-baseline gap-2 flex-wrap">
                                <span class="text-xs text-mist shrink-0">Reszta wybiera, <?= e($oName) ?> pominął:</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-medium"><?= e($r['group']) ?></span>
                            </div>
                        <?php else: // mixed ?>
                            <div class="text-sm grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-mist shrink-0">Tylko on:</span>
                                    <span class="text-amber-600 dark:text-amber-400 font-medium"><?= e($r['own']) ?></span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-mist shrink-0">Tylko reszta:</span>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium"><?= e($r['group']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <p class="text-xs text-mist italic text-center mt-4">
                    Nie znaczy że to "zły" uczestnik — po prostu warto wiedzieć gdzie ekipa idzie na kompromis.
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- HEATMAP MATRIX (rozwijalne) -->
        <details class="rounded-xl bg-paper dark:bg-deep border border-mist/15 p-4 md:p-5">
            <summary class="cursor-pointer font-display font-semibold text-ink dark:text-pale hover:text-secondary transition flex items-center gap-2">
                <span class="iconify" data-icon="ph:grid-four-bold"></span>
                Pokaż pełną tabelę kompatybilności (<?= count($participants) ?>×<?= count($participants) ?>)
            </summary>
            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-sm border-separate" style="border-spacing: 4px;">
                    <thead>
                        <tr>
                            <th class="p-2"></th>
                            <?php foreach ($participants as $i => $p):
                                $c = $colors[$p->id] ?? '#FF6B35';
                            ?>
                            <th class="p-2 text-center" title="<?= e($nick($p, $i)) ?>">
                                <?= $renderAvatar($p, $i, $c, 36, 2) ?>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $i => $rowP):
                            $rc = $colors[$rowP->id] ?? '#FF6B35';
                        ?>
                        <tr>
                            <th class="p-2 text-left">
                                <span class="inline-flex items-center gap-2">
                                    <?= $renderAvatar($rowP, $i, $rc, 36, 2) ?>
                                    <span class="font-display font-semibold text-ink dark:text-pale text-xs sm:text-sm hidden sm:inline">
                                        <?= e($nick($rowP, $i)) ?>
                                    </span>
                                </span>
                            </th>
                            <?php foreach ($participants as $j => $colP):
                                $score = $matrix[$rowP->id][$colP->id] ?? 0;
                                $pct = (int) round($score * 100);
                                $bg = $i === $j ? '#9CA3AF' : $heatColor($score);
                                $sameCell = $i === $j;
                                // Klucz pary - mniejszy id pierwszy by zgadzac sie z $pairData
                                $pairKey = $sameCell ? '' : (min($rowP->id, $colP->id) . '-' . max($rowP->id, $colP->id));
                            ?>
                            <td class="text-center font-mono font-bold text-xs rounded text-white <?= $sameCell ? '' : 'cursor-pointer hover:opacity-80' ?>"
                                style="background: <?= e($bg) ?>; padding: 10px 4px; min-width: 38px;"
                                <?php if (!$sameCell): ?>data-pair-key="<?= e($pairKey) ?>" data-pair-trigger<?php endif; ?>
                                title="<?= e($nick($rowP, $i)) ?> ↔ <?= e($nick($colP, $j)) ?>: <?= $pct ?>%<?= $sameCell ? '' : ' · kliknij' ?>">
                                <?= $sameCell ? '—' : $pct . '%' ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>

    </div>
</section>

<!-- MODAL z breakdownem pary - poza section, zeby fixed pozycjonowanie nie bylo zlamane przez parents transform -->
<div id="compat-modal" class="hidden fixed inset-0 z-[70] items-end sm:items-center justify-center p-0 sm:p-4" style="background: rgba(0,0,0,0.7);">
    <div class="bg-paper dark:bg-deep w-full sm:max-w-3xl sm:rounded-2xl rounded-t-2xl shadow-pop-lg overflow-hidden flex flex-col" style="max-height: 90vh;">
        <!-- Header -->
        <div class="shrink-0 px-5 pt-5 pb-4 border-b border-mist/15 flex items-start gap-3">
            <div class="flex items-center -space-x-3 shrink-0">
                <span id="compat-modal-a" class="inline-flex"></span>
                <span id="compat-modal-b" class="inline-flex"></span>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="font-display font-bold text-lg md:text-xl text-ink dark:text-pale leading-tight">
                    <span id="compat-modal-names"></span>
                </h3>
                <p class="text-xs text-mist mt-0.5">
                    Średnie dopasowanie: <b id="compat-modal-score" class="font-mono"></b>
                </p>
            </div>
            <button type="button" id="compat-modal-close" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-mist/15 text-mist transition" aria-label="Zamknij">
                <span class="iconify" data-icon="ph:x-bold"></span>
            </button>
        </div>

        <!-- Body - scrollable -->
        <div class="flex-1 overflow-y-auto p-5 space-y-5">
            <!-- ATRAKCJE NA MAPIE (priorytet - 75% wagi w score) -->
            <div id="compat-modal-votes" class="hidden">
                <h4 class="font-display font-bold text-sm uppercase tracking-wide text-secondary mb-3 flex items-center gap-2">
                    <span class="iconify" data-icon="ph:map-pin-bold"></span>
                    Atrakcje na mapie · zgodność ocen (<span data-count></span> wspólnych)
                </h4>
                <div class="rounded-lg p-3 mb-3" style="background: rgba(14,155,170,0.10)">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <span class="font-bold text-secondary">Średnia zgodność ocen:</span>
                        <span class="font-mono font-bold text-lg" data-avg-pct></span>
                    </div>
                    <div class="text-xs text-mist">To 75% wagi w finalnym score (reszta z ankiety).</div>
                </div>
                <div class="space-y-3">
                    <div data-block="agreed">
                        <div class="font-semibold text-xs text-emerald-700 dark:text-emerald-300 mb-1 uppercase">✓ Najbardziej zgodni</div>
                        <div class="space-y-1.5" data-list></div>
                    </div>
                    <div data-block="disagreed">
                        <div class="font-semibold text-xs text-rose-700 dark:text-rose-300 mb-1 mt-3 uppercase">⚠ Najbardziej różni</div>
                        <div class="space-y-1.5" data-list></div>
                    </div>
                </div>
            </div>

            <!-- ZGODNI (ankieta) -->
            <div id="compat-modal-agree" class="hidden">
                <h4 class="font-display font-bold text-sm uppercase tracking-wide text-emerald-700 dark:text-emerald-300 mb-3 flex items-center gap-2">
                    <span class="iconify" data-icon="ph:check-circle-fill"></span>
                    Z ankiety - zgodni w (<span data-count></span>)
                </h4>
                <div class="space-y-2" data-list></div>
            </div>
            <!-- PARTIAL (ankieta) -->
            <div id="compat-modal-partial" class="hidden">
                <h4 class="font-display font-bold text-sm uppercase tracking-wide text-amber-700 dark:text-amber-300 mb-3 flex items-center gap-2">
                    <span class="iconify" data-icon="ph:scales-bold"></span>
                    Z ankiety - częściowa zgoda (<span data-count></span>)
                </h4>
                <div class="space-y-2" data-list></div>
            </div>
            <!-- DISAGREE (ankieta) -->
            <div id="compat-modal-disagree" class="hidden">
                <h4 class="font-display font-bold text-sm uppercase tracking-wide text-rose-700 dark:text-rose-300 mb-3 flex items-center gap-2">
                    <span class="iconify" data-icon="ph:x-circle-fill"></span>
                    Z ankiety - różnią się w (<span data-count></span>)
                </h4>
                <div class="space-y-2" data-list></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var data = <?= $pairDataJson ?>;
    var modal = document.getElementById('compat-modal');
    if (!modal) return;

    function colorForSim(s) {
        if (s >= 0.80) return '#10B981';
        if (s >= 0.65) return '#34D399';
        if (s >= 0.50) return '#FACC15';
        if (s >= 0.35) return '#FB923C';
        return '#F87171';
    }

    function renderAvatar(pid, color, avatar, name, size) {
        if (avatar) {
            return '<span style="display:inline-flex;width:' + size + 'px;height:' + size + 'px;border-radius:50%;overflow:hidden;border:3px solid ' + color + ';box-shadow:0 2px 6px rgba(0,0,0,.15);box-sizing:border-box">'
                 + '<img src="' + avatar + '" alt="" style="width:100%;height:100%;object-fit:cover"></span>';
        }
        var initial = (name || '?').charAt(0).toUpperCase();
        var fz = Math.max(12, Math.round(size * 0.42));
        return '<span style="display:inline-flex;align-items:center;justify-content:center;width:' + size + 'px;height:' + size + 'px;border-radius:50%;background:' + color + ';color:#fff;font-weight:800;font-size:' + fz + 'px;border:3px solid ' + color + ';box-sizing:border-box;font-family:\'Bricolage Grotesque\',sans-serif">'
             + initial + '</span>';
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function renderRow(row, aName, bName) {
        var pct = Math.round(row.similarity * 100);
        var col = colorForSim(row.similarity);
        return '<div style="display:flex;flex-direction:column;gap:6px;padding:10px 12px;border-radius:10px;background:rgba(127,127,127,0.06)">'
             + '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px">'
             +   '<span style="font-weight:700;font-size:13px;color:var(--heading)">📌 ' + escapeHtml(row.label) + '</span>'
             +   '<span style="font-family:ui-monospace,monospace;font-weight:700;font-size:12px;color:' + col + '">' + pct + '%</span>'
             + '</div>'
             + '<div style="display:flex;gap:8px;flex-wrap:wrap;font-size:12px">'
             +   '<span style="opacity:0.7">' + escapeHtml(aName) + ':</span>'
             +   '<span style="font-weight:600">' + escapeHtml(row.val_a) + '</span>'
             +   '<span style="opacity:0.4">•</span>'
             +   '<span style="opacity:0.7">' + escapeHtml(bName) + ':</span>'
             +   '<span style="font-weight:600">' + escapeHtml(row.val_b) + '</span>'
             + '</div>'
             + '</div>';
    }

    function openModal(key) {
        var p = data[key];
        if (!p) return;

        document.getElementById('compat-modal-a').innerHTML = renderAvatar(p.a_id, p.a_color, p.a_avatar, p.a_name, 56);
        document.getElementById('compat-modal-b').innerHTML = renderAvatar(p.b_id, p.b_color, p.b_avatar, p.b_name, 56);
        document.getElementById('compat-modal-names').textContent = p.a_name + ' & ' + p.b_name;
        var scoreEl = document.getElementById('compat-modal-score');
        scoreEl.textContent = Math.round(p.score * 100) + '%';
        scoreEl.style.color = colorForSim(p.score);

        // Sekcja ATRAKCJE NA MAPIE (75% wagi - na gorze)
        var votesEl = document.getElementById('compat-modal-votes');
        if (p.votes && p.votes.shared_count > 0) {
            votesEl.classList.remove('hidden');
            votesEl.querySelector('[data-count]').textContent = p.votes.shared_count;
            var avgPctEl = votesEl.querySelector('[data-avg-pct]');
            var avgPct = Math.round(p.votes.similarity * 100);
            avgPctEl.textContent = avgPct + '%';
            avgPctEl.style.color = colorForSim(p.votes.similarity);

            var renderPlaceRow = function (place) {
                var pct = Math.round(place.sim * 100);
                var col = colorForSim(place.sim);
                var stars = function (n) {
                    var s = '';
                    for (var i = 1; i <= 5; i++) s += i <= n ? '★' : '☆';
                    return s;
                };
                return '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:6px 10px;border-radius:6px;background:rgba(127,127,127,0.05);font-size:12px">'
                     + '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600">' + escapeHtml(place.name) + '</span>'
                     + '<span style="font-family:ui-monospace,monospace;color:#F59E0B" title="' + escapeHtml(p.a_name) + '">' + stars(place.a_score) + '</span>'
                     + '<span style="opacity:0.4">↔</span>'
                     + '<span style="font-family:ui-monospace,monospace;color:#F59E0B" title="' + escapeHtml(p.b_name) + '">' + stars(place.b_score) + '</span>'
                     + '<span style="font-family:ui-monospace,monospace;font-weight:700;color:' + col + ';min-width:34px;text-align:right">' + pct + '%</span>'
                     + '</div>';
            };

            var agreedBlock = votesEl.querySelector('[data-block="agreed"]');
            var disagreedBlock = votesEl.querySelector('[data-block="disagreed"]');
            if (p.votes.agreed && p.votes.agreed.length > 0) {
                agreedBlock.style.display = '';
                agreedBlock.querySelector('[data-list]').innerHTML = p.votes.agreed.map(renderPlaceRow).join('');
            } else {
                agreedBlock.style.display = 'none';
            }
            if (p.votes.disagreed && p.votes.disagreed.length > 0) {
                // Pomijamy jesli wszystkie miejsca z 100% - "rozni sie" nie ma sensu
                var hasDiff = p.votes.disagreed.some(function (d) { return d.sim < 1.0; });
                if (hasDiff) {
                    disagreedBlock.style.display = '';
                    disagreedBlock.querySelector('[data-list]').innerHTML = p.votes.disagreed.map(renderPlaceRow).join('');
                } else {
                    disagreedBlock.style.display = 'none';
                }
            } else {
                disagreedBlock.style.display = 'none';
            }
        } else {
            votesEl.classList.add('hidden');
        }

        // Podziel rows na 3 kubełki (ankieta)
        var agree = [], partial = [], disagree = [];
        p.rows.forEach(function (r) {
            if (r.similarity >= 0.66) agree.push(r);
            else if (r.similarity >= 0.34) partial.push(r);
            else disagree.push(r);
        });

        ['agree', 'partial', 'disagree'].forEach(function (kind, idx) {
            var bucket = [agree, partial, disagree][idx];
            var el = document.getElementById('compat-modal-' + kind);
            if (bucket.length === 0) {
                el.classList.add('hidden');
                return;
            }
            el.classList.remove('hidden');
            el.querySelector('[data-count]').textContent = bucket.length;
            el.querySelector('[data-list]').innerHTML = bucket.map(function (r) {
                return renderRow(r, p.a_name, p.b_name);
            }).join('');
        });

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-pair-trigger]');
        if (trigger) {
            e.preventDefault();
            openModal(trigger.getAttribute('data-pair-key'));
            return;
        }
        if (e.target.id === 'compat-modal' || e.target.id === 'compat-modal-close' || e.target.closest('#compat-modal-close')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();
</script>
