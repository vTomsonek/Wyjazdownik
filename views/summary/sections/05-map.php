<?php
/**
 * Sekcja 5: Mapa atrakcji + propozycje tras (Etap 5 nowej funkcji).
 * Zastapuje stara mape pomyslow (participant_map_pins).
 *
 * @var \App\Services\SummaryAggregator $agg
 */
use App\Database\Connection;
use App\Models\TripPlace;
use App\Models\TripPlaceVote;
use App\Services\RouteSuggestionService;

$trip = $agg->trip;
$places = TripPlace::listForTrip($trip->id);
$participants = $agg->participants();
$colors = $agg->colorMap();
$anonymous = $agg->isAnonymous();

// Oceny - statystyki per miejsce (zero participant_id = ignoruje my_score)
$voteStats = TripPlaceVote::statsForTrip($trip->id, 0);

// Mapa: participant_id => nick
$nicks = [];
foreach ($participants as $i => $p) {
    $nicks[$p->id] = $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname;
}

// Per uczestnik - ile ocenił z dostępnych miejsc
$totalPlaces = count($places);
$voteCountByParticipant = [];
if ($totalPlaces > 0) {
    $stmt = Connection::get()->prepare(
        'SELECT v.participant_id, COUNT(*) AS cnt
         FROM trip_place_votes v
         JOIN trip_places p ON p.id = v.place_id
         WHERE p.trip_id = :tid
         GROUP BY v.participant_id'
    );
    $stmt->execute(['tid' => $trip->id]);
    foreach ($stmt->fetchAll() as $row) {
        $voteCountByParticipant[(int) $row['participant_id']] = (int) $row['cnt'];
    }
}

// Per uczestnik - ile miejsc dodal
$placesAddedByParticipant = [];
foreach ($places as $p) {
    $pid = $p->participantId;
    $placesAddedByParticipant[$pid] = ($placesAddedByParticipant[$pid] ?? 0) + 1;
}

// Top miejsc po srednia ocenie (jeśli są oceny)
$ranked = [];
foreach ($places as $p) {
    $stats = $voteStats[$p->id] ?? ['avg' => null, 'count' => 0];
    $ranked[] = [
        'place'      => $p,
        'avg'        => $stats['avg'],
        'vote_count' => $stats['count'],
        'author'     => $nicks[$p->participantId] ?? '?',
        'color'      => $colors[$p->participantId] ?? '#FF6B35',
    ];
}
usort($ranked, static function ($a, $b) {
    $av = $a['avg'] ?? 0;
    $bv = $b['avg'] ?? 0;
    return $bv <=> $av;
});

// Propozycje tras - z budzetem czasowym z odpowiedzi 'trip_duration_days'
// short = min (bezpieczna trasa dla wszystkich), full = mediana (realistyczna dla wiekszosci)
$durations = $agg->valuesFor('trip_duration_days');
$durations = array_values(array_map('intval', array_filter($durations, 'is_numeric')));
sort($durations);
$shortDays = null;
$fullDays  = null;
if (!empty($durations)) {
    $shortDays = $durations[0];
    $n = count($durations);
    $fullDays = $n % 2 === 0
        ? (int) round(($durations[(int) ($n/2 - 1)] + $durations[(int) ($n/2)]) / 2)
        : $durations[(int) floor($n / 2)];
}
$routes = (new RouteSuggestionService())->suggest($trip->id, $shortDays, $fullDays);

// Per-participant votes - "kto jak zaglosowal" w popupie mapy
$votesPerParticipantMap = TripPlaceVote::votesByPlaceAndParticipant($trip->id);
// Lista uczestnikow do popupu (zachowuje kolejnosc)
$participantsForJs = array_map(static function ($p) use ($nicks, $colors, $anonymous) {
    return [
        'id'     => $p->id,
        'nick'   => $nicks[$p->id] ?? '?',
        'color'  => $colors[$p->id] ?? '#FF6B35',
        'avatar' => (!$anonymous && $p->avatarPath) ? asset($p->avatarPath) : null,
    ];
}, $participants);

// JSON dla JS
$avatarsByParticipantId = [];
foreach ($participants as $p) {
    $avatarsByParticipantId[$p->id] = (!$anonymous && $p->avatarPath) ? asset($p->avatarPath) : null;
}
$placesForJs = array_map(static function ($r) use ($votesPerParticipantMap, $avatarsByParticipantId) {
    $votes = $votesPerParticipantMap[$r['place']->id] ?? [];
    return [
        'id'              => $r['place']->id,
        'name'            => $r['place']->name,
        'lat'             => $r['place']->lat,
        'lng'             => $r['place']->lng,
        'address'         => $r['place']->address,
        'description'     => $r['place']->description,
        'visit_minutes'   => $r['place']->visitMinutes,
        'google_place_id' => $r['place']->googlePlaceId,
        'avg'             => $r['avg'],
        'count'           => $r['vote_count'],
        'author'          => $r['author'],
        'author_avatar'   => $avatarsByParticipantId[$r['place']->participantId] ?? null,
        'color'           => $r['color'],
        'votes'           => $votes, // {participant_id => score}
    ];
}, $ranked);
$placesJson = json_encode($placesForJs, JSON_UNESCAPED_UNICODE);
$routesJson = json_encode($routes, JSON_UNESCAPED_UNICODE);
$startJson = ($trip->startLat !== null && $trip->startLng !== null)
    ? json_encode([
        'name' => $trip->startName ?? 'Punkt startowy',
        'lat'  => $trip->startLat,
        'lng'  => $trip->startLng,
    ], JSON_UNESCAPED_UNICODE)
    : '';
$googleMapsApiKey = (string) config('google.maps_api_key', '');
?>

<section class="section">
    <div class="wrap">

        <header class="sec-head">
            <span class="eyebrow"><span class="iconify" data-icon="ph:map-trifold-bold"></span> Atrakcje ekipy</span>
            <h2 style="margin-top:18px">🗺️ Dokąd chcecie pojechać</h2>
            <p>Konkretne miejsca dodane przez ekipę. Algorytm zaproponował też możliwe trasy samochodowe.</p>
        </header>

        <?php if (empty($places)): ?>
            <div class="rounded-2xl bg-paper dark:bg-deep border-2 border-dashed border-mist/30 p-8 text-center">
                <div class="text-4xl mb-3">📍</div>
                <p class="text-mist">Nikt jeszcze nie dodał miejsca. Wejdźcie na <code class="text-primary">/atrakcje</code> przez wasz link uczestnika żeby zacząć.</p>
            </div>
        <?php elseif ($googleMapsApiKey === ''): ?>
            <div class="rounded-2xl bg-amber-100 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 p-6 text-center">
                <p class="text-amber-900 dark:text-amber-200">⚠️ Brak klucza Google Maps API w konfiguracji.</p>
            </div>
        <?php else: ?>

        <!-- CTA: Tryb trasy (geolocation, mobile, in-trip) -->
        <a href="<?= e(url('/summary/' . $trip->summaryPublicToken . '/trasa')) ?>"
           class="group block mb-6 rounded-2xl bg-gradient-to-r from-primary to-primary-deep text-white p-5 md:p-6 shadow-pop hover:shadow-pop-lg hover:scale-[1.01] transition">
            <div class="flex items-center gap-4">
                <div class="text-4xl md:text-5xl shrink-0">🚗</div>
                <div class="flex-1">
                    <div class="text-xs uppercase tracking-wide opacity-80 font-semibold mb-0.5">Już w trasie?</div>
                    <h3 class="font-display font-bold text-lg md:text-xl mb-0.5">Otwórz tryb trasy</h3>
                    <p class="text-sm opacity-90">Mapa z atrakcjami + Twoja pozycja na żywo. Działa na telefonie w aucie.</p>
                </div>
                <svg class="w-6 h-6 shrink-0 group-hover:translate-x-1 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </div>
        </a>

        <!-- Aktywnosc uczestnikow: dodane miejsca + ocenione + brakuje (peer pressure) -->
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 mb-6">
            <h3 class="font-display font-bold text-base text-ink dark:text-pale mb-1 flex items-center gap-2">
                🎯 Aktywność uczestników
            </h3>
            <p class="text-xs text-mist mb-4">Ile kto miejsc dodał, ocenił i ile mu zostało.</p>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <?php foreach ($participants as $i => $p):
                    $voted   = $voteCountByParticipant[$p->id] ?? 0;
                    $added   = $placesAddedByParticipant[$p->id] ?? 0;
                    $missing = $totalPlaces - $voted;
                    $fullDone = $missing === 0 && $totalPlaces > 0;
                    $nick    = $nicks[$p->id] ?? '?';
                    $color   = $colors[$p->id] ?? '#FF6B35';
                    // Procent ukonczenia oceniania (do paska)
                    $votedPct = $totalPlaces > 0 ? ($voted / $totalPlaces * 100) : 0;
                ?>
                <div class="rounded-xl p-3 <?= $fullDone ? 'bg-secondary/10 border border-secondary/30' : ($missing > 0 ? 'bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40' : 'bg-mist/10') ?>">
                    <div class="flex items-center gap-2 mb-2">
                        <?php if (!$anonymous && $p->avatarPath): ?>
                            <img src="<?= e(asset($p->avatarPath)) ?>" alt=""
                                 class="w-9 h-9 rounded-full object-cover shrink-0 border-2"
                                 style="border-color: <?= e($color) ?>">
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full text-white text-sm font-bold shrink-0" style="background:<?= e($color) ?>">
                                <?= e(mb_strtoupper(mb_substr($nick, 0, 1))) ?>
                            </span>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-ink dark:text-pale truncate"><?= e($nick) ?></div>
                            <?php if ($fullDone): ?>
                                <div class="text-[10px] font-bold text-secondary uppercase tracking-wide">✓ Komplet</div>
                            <?php elseif ($missing > 0): ?>
                                <div class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase tracking-wide">⚠ Do nadrobienia</div>
                            <?php else: ?>
                                <div class="text-[10px] text-mist">brak miejsc</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 3 wartosci: dodał / ocenił / brakuje -->
                    <div class="grid grid-cols-3 gap-1 text-center mb-2">
                        <div class="rounded-lg bg-paper/60 dark:bg-deep/60 py-1.5">
                            <div class="font-display font-bold text-base text-ink dark:text-pale leading-none"><?= $added ?></div>
                            <div class="text-[9px] text-mist uppercase tracking-wide mt-1">dodał(a)</div>
                        </div>
                        <div class="rounded-lg bg-paper/60 dark:bg-deep/60 py-1.5">
                            <div class="font-display font-bold text-base text-amber-500 leading-none"><?= $voted ?></div>
                            <div class="text-[9px] text-mist uppercase tracking-wide mt-1">ocenił(a)</div>
                        </div>
                        <div class="rounded-lg bg-paper/60 dark:bg-deep/60 py-1.5">
                            <div class="font-display font-bold text-base <?= $missing > 0 ? 'text-red-500' : 'text-secondary' ?> leading-none"><?= $missing ?></div>
                            <div class="text-[9px] text-mist uppercase tracking-wide mt-1">brakuje</div>
                        </div>
                    </div>

                    <!-- Pasek postępu (jak duża czesc miejsc ocenil) -->
                    <?php if ($totalPlaces > 0): ?>
                    <div class="h-1.5 bg-mist/15 rounded-full overflow-hidden" title="<?= $voted ?> / <?= $totalPlaces ?> miejsc ocenionych">
                        <div class="h-full rounded-full transition-all" style="width: <?= $votedPct ?>%; background: <?= e($color) ?>"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Mapa + lista top miejsc -->
        <div class="grid lg:grid-cols-[1fr_360px] gap-4 mb-8">
            <div class="rounded-2xl overflow-hidden border-2 border-mist/15 bg-paper dark:bg-deep">
                <div id="summary-places-map"
                     data-places='<?= e($placesJson) ?>'
                     data-routes='<?= e($routesJson) ?>'
                     data-start='<?= e($startJson) ?>'
                     data-participants='<?= e(json_encode($participantsForJs, JSON_UNESCAPED_UNICODE)) ?>'
                     style="height: 70vh; min-height: 520px;"></div>
            </div>

            <!-- Lista top miejsc (sortowane po ocenie) -->
            <aside class="rounded-2xl border border-mist/15 bg-paper dark:bg-deep p-5 max-h-[70vh] overflow-y-auto">
                <h3 class="font-display font-bold text-lg text-ink dark:text-pale mb-4">
                    Top miejsca <span class="text-mist font-normal text-sm">(<?= count($ranked) ?>)</span>
                </h3>
                <div class="space-y-2.5">
                    <?php foreach ($ranked as $i => $r): ?>
                    <article class="rounded-xl border border-mist/15 p-3 hover:border-primary/30 transition">
                        <div class="flex items-start gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold shrink-0 mt-0.5 bg-primary">
                                <?= $i + 1 ?>
                            </span>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm text-ink dark:text-pale leading-tight"><?= e($r['place']->name) ?></h4>
                                <?php if ($r['place']->address): ?>
                                    <p class="text-xs text-mist truncate"><?= e($r['place']->address) ?></p>
                                <?php endif; ?>
                                <div class="mt-1 flex items-center gap-2 text-xs">
                                    <?php if ($r['avg'] !== null): ?>
                                        <span class="text-amber-500 font-semibold">★ <?= number_format($r['avg'], 1, ',', '') ?></span>
                                        <span class="text-mist">(<?= $r['vote_count'] ?>)</span>
                                    <?php else: ?>
                                        <span class="text-mist italic">brak ocen</span>
                                    <?php endif; ?>
                                    <span class="ml-auto text-mist truncate max-w-[100px]" title="<?= e($r['author']) ?>">
                                        — <?= e($r['author']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>

        <!-- Propozycje tras (jeśli są) -->
        <?php if (!empty($routes)): ?>
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-6">
            <h3 class="font-display font-bold text-xl md:text-2xl text-ink dark:text-pale mb-2 flex items-center gap-2">
                🚗 Propozycje tras samochodowych
            </h3>
            <p class="text-mist text-sm mb-2">
                Algorytm pogrupował miejsca po regionach i dopasował trasy do długości jaką ekipa wpisała w ankiecie.
                <?php if ($shortDays !== null && $fullDays !== null && $shortDays !== $fullDays): ?>
                <br><span class="text-ink/80 dark:text-pale/80">Pokazujemy 2 warianty per region: <b>krótka</b> (<?= $shortDays ?> dni) i <b>pełna</b> (<?= $fullDays ?> dni).</span>
                <?php elseif ($fullDays !== null): ?>
                <br><span class="text-ink/80 dark:text-pale/80">Budżet czasowy: <b><?= $fullDays ?> dni</b>.</span>
                <?php endif; ?>
            </p>
            <p class="text-mist text-xs mb-5 italic">Kliknij propozycję żeby zobaczyć na mapie.</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $routeColors = ['#FF6B35', '#2EC4B6', '#FFD23F', '#C2410C', '#0EA5E9'];
                foreach ($routes as $idx => $route):
                    $color = $routeColors[$idx % count($routeColors)];
                    $variant = $route['variant'] ?? null;
                    $excluded = $route['excluded'] ?? [];
                    $excludedCount = $route['excluded_count'] ?? 0;
                ?>
                <div class="rounded-2xl border-2 border-mist/15 bg-cream dark:bg-night p-5 hover:border-primary/30 transition flex flex-col" data-summary-route-idx="<?= $idx ?>">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h4 class="font-display font-bold text-lg text-ink dark:text-pale leading-snug"><?= e($route['name']) ?></h4>
                        <span class="w-4 h-4 rounded-full shrink-0 mt-1.5" style="background:<?= e($color) ?>"></span>
                    </div>
                    <?php if ($variant !== null): ?>
                    <div class="mb-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide"
                              style="background: <?= $variant === 'pełna' ? 'rgba(46,196,182,0.18)' : 'rgba(255,210,63,0.20)' ?>;
                                     color: <?= $variant === 'pełna' ? '#0B7B87' : '#A66800' ?>">
                            <?= $variant === 'pełna' ? '✓ Pełna trasa' : '⚡ Krótsza opcja' ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <div class="flex flex-wrap gap-3 text-xs text-mist mb-3">
                        <span>📍 <?= count($route['places']) ?> miejsc</span>
                        <span>🛣️ ~<?= (int) $route['distance_km'] ?> km</span>
                        <span>⏱ <?= (int) $route['days_est'] ?> dni</span>
                        <span>⭐ <?= number_format($route['avg_score'], 1, ',', '') ?></span>
                    </div>
                    <ol class="text-sm text-ink/80 dark:text-pale/80 space-y-1 mb-3 flex-1">
                        <?php foreach (array_slice($route['places'], 0, 5) as $i => $rp): ?>
                            <li><?= ($i + 1) ?>. <?= e($rp['name']) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($route['places']) > 5): ?>
                            <li class="text-mist italic">+ <?= count($route['places']) - 5 ?> więcej</li>
                        <?php endif; ?>
                    </ol>

                    <?php if ($excludedCount > 0): ?>
                    <details class="mb-3 text-xs">
                        <summary class="cursor-pointer text-rose-600 dark:text-rose-400 font-semibold hover:underline">
                            ⚠ +<?= $excludedCount ?> top miejsc poza budżetem
                        </summary>
                        <div class="mt-2 p-3 rounded-lg bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/50">
                            <p class="text-rose-800 dark:text-rose-200 mb-2 leading-snug">
                                Te miejsca dostały dobre oceny ale nie zmieściły się w budżecie <?= (int) ($route['budget_days'] ?? 0) ?> dni:
                            </p>
                            <ul class="space-y-0.5 text-rose-700 dark:text-rose-300">
                                <?php foreach (array_slice($excluded, 0, 8) as $ex): ?>
                                    <li>• <?= e($ex['name']) ?> <span class="opacity-60">(★<?= number_format($ex['avg'], 1, ',', '') ?>)</span></li>
                                <?php endforeach; ?>
                                <?php if (count($excluded) > 8): ?>
                                    <li class="italic opacity-70">…i <?= count($excluded) - 8 ?> więcej</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </details>
                    <?php endif; ?>

                    <button type="button" data-summary-show-route="<?= $idx ?>" data-color="<?= e($color) ?>"
                            class="w-full px-3 py-2 rounded-full text-white font-semibold text-sm hover:scale-105 transition mt-auto"
                            style="background:<?= e($color) ?>">
                        Pokaż na mapie
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <!-- Modal szczegolow miejsca - shared dla popupu mapy i rankingu 05b -->
    <div id="summary-detail-modal" class="hidden fixed inset-0 z-[60] bg-black/75 items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-paper dark:bg-deep w-full sm:max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-pop-lg overflow-hidden flex flex-col" style="max-height: 92vh;">
            <div id="sdm-hero" class="hidden relative w-full bg-mist/20" style="height: 220px;">
                <img id="sdm-hero-img" alt="" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-paper dark:from-deep to-transparent"></div>
            </div>
            <div class="shrink-0 px-5 pt-4 pb-3 border-b border-mist/15 flex items-start gap-3">
                <span id="sdm-author-avatar" class="shrink-0"></span>
                <div class="flex-1 min-w-0">
                    <h2 id="sdm-name" class="font-display font-bold text-lg text-ink dark:text-pale leading-tight"></h2>
                    <p id="sdm-address" class="text-xs text-mist mt-0.5"></p>
                </div>
                <button type="button" id="sdm-close" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-mist/15 text-mist transition" aria-label="Zamknij">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-4">
                <div class="flex items-center gap-2 flex-wrap mb-3">
                    <span id="sdm-rating" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/30 border border-amber-300/40 text-xs font-semibold text-amber-700 dark:text-amber-200"></span>
                    <span id="sdm-visit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 border border-primary/30 text-xs font-medium text-primary"></span>
                </div>
                <p id="sdm-description" class="text-sm text-ink dark:text-pale leading-relaxed mb-4 whitespace-pre-line"></p>
                <div id="sdm-votes" class="rounded-xl bg-mist/5 border border-mist/15 p-3 mb-4 space-y-1.5"></div>
                <div id="sdm-media" class="space-y-4">
                    <div class="text-center text-xs text-mist py-3 italic">Ładuję media...</div>
                </div>
            </div>
        </div>
    </div>

    <div id="sdm-lightbox" class="hidden fixed inset-0 z-[70] bg-black/95 flex items-center justify-center">
        <button type="button" id="sdm-lb-close" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white" aria-label="Zamknij">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <button type="button" id="sdm-lb-prev" class="absolute left-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white" aria-label="Poprzednie">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <button type="button" id="sdm-lb-next" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white" aria-label="Następne">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
        <div id="sdm-lb-stage" class="max-w-[92vw] max-h-[88vh] flex items-center justify-center"></div>
        <div id="sdm-lb-caption" class="absolute bottom-4 left-0 right-0 text-center text-white/90 text-sm px-4"></div>
    </div>

    <script>
        // Modal szczegolow miejsca - SAMODZIELNY skrypt, niezalezny od mapy.
        // Dziala nawet gdy nie ma google maps key / nie ma places.
        window.__SUMMARY_DETAIL_CTX__ = {
            mediaUrlTemplate: <?= json_encode(url('/summary/' . $trip->summaryPublicToken . '/places/ID/media'), JSON_UNESCAPED_SLASHES) ?>,
            assetBase: <?= json_encode(rtrim((string) env('APP_URL', ''), '/') . '/', JSON_UNESCAPED_SLASHES) ?>,
            places: <?= str_replace('</', '<\/', $placesJson) ?>,
            participants: <?= str_replace('</', '<\/', json_encode($participantsForJs, JSON_UNESCAPED_UNICODE)) ?>,
        };

        (function () {
            const ctx = window.__SUMMARY_DETAIL_CTX__;
            const modal = document.getElementById('summary-detail-modal');
            const lbEl  = document.getElementById('sdm-lightbox');
            if (!modal) { console.warn('[sdm] modal HTML missing'); return; }

            // CRITICAL: przeniesc modal/lightbox do <body> zeby ucieknąć z transformed
            // kontekstu rodzica (section[data-summary-animate] ma transform ktore lamie
            // position:fixed - element jest pozycjonowany wzgledem sekcji nie viewportu).
            if (modal.parentElement !== document.body) document.body.appendChild(modal);
            if (lbEl && lbEl.parentElement !== document.body) document.body.appendChild(lbEl);

            console.log('[sdm] init OK · places:', (ctx.places || []).length, '· participants:', (ctx.participants || []).length);

            const $ = id => document.getElementById(id);
            const sdm = {
                hero: $('sdm-hero'), heroImg: $('sdm-hero-img'),
                authorAvatar: $('sdm-author-avatar'), name: $('sdm-name'),
                address: $('sdm-address'), rating: $('sdm-rating'),
                visit: $('sdm-visit'), description: $('sdm-description'),
                votes: $('sdm-votes'), media: $('sdm-media'),
                close: $('sdm-close'),
            };
            const lb = { root: $('sdm-lightbox'), stage: $('sdm-lb-stage'), caption: $('sdm-lb-caption') };
            let lbItems = [], lbIdx = 0;

            const escapeHtml = s => String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
            const formatVisit = vm => {
                vm = parseInt(vm, 10) || 60;
                if (vm < 60) return vm + 'min';
                if (vm % 60 === 0) return (vm / 60) + 'h';
                return (vm / 60).toFixed(1).replace('.', ',') + 'h';
            };
            const absUrl = path => {
                if (!path) return '';
                if (/^https?:\/\//.test(path)) return path;
                return (ctx.assetBase || '/') + path.replace(/^\//, '');
            };
            const avatarHtml = (pa, sizePx) => {
                if (pa.avatar) {
                    return `<img src="${escapeHtml(pa.avatar)}" alt="" style="width:${sizePx}px;height:${sizePx}px;border-radius:50%;object-fit:cover;border:1.5px solid ${escapeHtml(pa.color)};flex-shrink:0">`;
                }
                const init = (pa.nick || '?').charAt(0).toUpperCase();
                const fpx = Math.max(8, Math.round(sizePx * 0.45));
                return `<span style="display:inline-flex;align-items:center;justify-content:center;width:${sizePx}px;height:${sizePx}px;border-radius:50%;background:${escapeHtml(pa.color)};color:#fff;font-size:${fpx}px;font-weight:700;flex-shrink:0">${escapeHtml(init)}</span>`;
            };

            // Event delegation - capture phase by ubiec <summary> default toggle
            document.addEventListener('click', (e) => {
                const t = e.target.closest('[data-summary-detail]');
                if (!t) return;
                const id = parseInt(t.getAttribute('data-summary-detail'), 10);
                if (!id) return;
                e.preventDefault();
                e.stopPropagation();
                console.log('[sdm] open detail for place', id);
                openDetail(id);
            }, true);

            sdm.close?.addEventListener('click', closeDetail);
            modal.addEventListener('click', e => { if (e.target === modal) closeDetail(); });
            $('sdm-lb-close')?.addEventListener('click', closeLb);
            $('sdm-lb-prev')?.addEventListener('click', () => lbNav(-1));
            $('sdm-lb-next')?.addEventListener('click', () => lbNav(1));
            lb.root?.addEventListener('click', e => { if (e.target === lb.root) closeLb(); });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') {
                    if (lb.root && !lb.root.classList.contains('hidden')) { closeLb(); return; }
                    if (!modal.classList.contains('hidden')) closeDetail();
                } else if (e.key === 'ArrowLeft' && lb.root && !lb.root.classList.contains('hidden')) lbNav(-1);
                else if (e.key === 'ArrowRight' && lb.root && !lb.root.classList.contains('hidden')) lbNav(1);
            });

            function openDetail(placeId) {
                const p = (ctx.places || []).find(pl => pl.id === placeId);
                if (!p) { console.warn('[sdm] Place not found:', placeId, 'in', ctx.places); return; }

                sdm.name.textContent = p.name || '';
                sdm.address.textContent = p.address || '';
                sdm.authorAvatar.innerHTML = avatarHtml({ nick: p.author, color: p.color, avatar: p.author_avatar }, 36);

                if (p.avg !== null && p.count > 0) {
                    sdm.rating.innerHTML = '★ ' + Number(p.avg).toFixed(1).replace('.', ',') + ' <span style="opacity:.7">(' + p.count + ')</span>';
                    sdm.rating.classList.remove('hidden');
                } else {
                    sdm.rating.classList.add('hidden');
                }
                sdm.visit.textContent = '⏱️ ' + formatVisit(p.visit_minutes);

                if (p.description && p.description.trim() !== '') {
                    sdm.description.textContent = p.description;
                    sdm.description.classList.remove('hidden');
                } else {
                    sdm.description.classList.add('hidden');
                }

                const voteRows = [];
                if (p.votes && (ctx.participants || []).length > 0) {
                    for (const pa of ctx.participants) {
                        const sc = p.votes[pa.id];
                        if (sc === undefined) continue;
                        const pct = (sc / 5.0) * 100;
                        voteRows.push(`
                            <div style="display:flex;align-items:center;gap:8px">
                                ${avatarHtml(pa, 22)}
                                <span class="text-xs" style="width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(pa.nick)}</span>
                                <div style="flex:1;height:6px;background:rgba(107,114,128,0.2);border-radius:3px;overflow:hidden;max-width:200px">
                                    <div style="width:${pct}%;height:100%;background:${escapeHtml(pa.color)}"></div>
                                </div>
                                <span class="text-xs font-mono font-bold text-amber-500" style="width:36px;text-align:right">${sc.toFixed(1).replace('.', ',')}★</span>
                            </div>`);
                    }
                }
                if (voteRows.length > 0) {
                    sdm.votes.innerHTML = `<div class="text-[10px] font-bold text-mist uppercase tracking-wide mb-1">Kto jak zagłosował</div>${voteRows.join('')}`;
                    sdm.votes.classList.remove('hidden');
                } else {
                    sdm.votes.classList.add('hidden');
                }

                sdm.hero.classList.add('hidden');
                sdm.heroImg.src = '';

                // Inline display - bypass Tailwind 'hidden' specificity issues
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';

                loadMedia(p);
            }

            function closeDetail() {
                modal.classList.add('hidden');
                modal.style.display = '';
                document.body.style.overflow = '';
            }

            async function loadMedia(p) {
                sdm.media.innerHTML = '<div class="text-center text-xs text-mist py-3 italic">Ładuję media...</div>';
                const [umRes, gpRes] = await Promise.allSettled([
                    fetchUserMedia(p.id),
                    fetchGooglePhotos(p.google_place_id),
                ]);
                const userMedia = umRes.status === 'fulfilled' ? umRes.value : [];
                const googlePhotos = gpRes.status === 'fulfilled' ? gpRes.value : [];

                let heroUrl = null;
                if (googlePhotos.length > 0) heroUrl = googlePhotos[0];
                else {
                    const fimg = userMedia.find(m => m.type === 'image');
                    if (fimg) heroUrl = absUrl(fimg.file_path);
                }
                if (heroUrl) {
                    sdm.heroImg.src = heroUrl;
                    sdm.hero.classList.remove('hidden');
                }
                renderMedia(userMedia, googlePhotos, p.name);
            }

            async function fetchUserMedia(placeId) {
                if (!ctx.mediaUrlTemplate) return [];
                try {
                    const r = await fetch(ctx.mediaUrlTemplate.replace('ID', placeId));
                    const data = await r.json();
                    return data.ok ? (data.media || []) : [];
                } catch (e) { return []; }
            }
            // Poczekaj az Google Maps API sie zaladuje (do 8s)
            async function waitForGoogleMaps(timeoutMs = 8000) {
                if (window.google && google.maps && google.maps.places && google.maps.places.Place) return true;
                const start = Date.now();
                return new Promise(resolve => {
                    const tick = () => {
                        if (window.google && google.maps && google.maps.places && google.maps.places.Place) return resolve(true);
                        if (Date.now() - start > timeoutMs) return resolve(false);
                        setTimeout(tick, 100);
                    };
                    tick();
                });
            }

            async function fetchGooglePhotos(googlePlaceId) {
                if (!googlePlaceId) { console.log('[sdm] no google_place_id'); return []; }
                const ready = await waitForGoogleMaps();
                if (!ready) { console.warn('[sdm] Google Maps API not loaded after 8s'); return []; }
                try {
                    const place = new google.maps.places.Place({ id: googlePlaceId });
                    await place.fetchFields({ fields: ['photos'] });
                    const photos = (place.photos || []).slice(0, 6).map(ph => ph.getURI({ maxWidth: 800, maxHeight: 600 }));
                    console.log('[sdm] Google photos:', photos.length);
                    return photos;
                } catch (e) {
                    console.warn('[sdm] Google photos fetch error:', e);
                    return [];
                }
            }

            function renderMedia(userMedia, googlePhotos, placeName) {
                let html = '';
                lbItems = [];
                if (googlePhotos.length > 0) {
                    html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">📷 Zdjęcia z Google</div><div class="grid grid-cols-3 gap-1.5">';
                    googlePhotos.forEach(url => {
                        const idx = lbItems.length;
                        lbItems.push({ type: 'image', url, caption: '📷 ' + placeName + ' · Google' });
                        html += `<button type="button" data-sdm-lb="${idx}" class="block aspect-[4/3] rounded-lg overflow-hidden bg-mist/10"><img src="${escapeHtml(url)}" alt="" class="w-full h-full object-cover" loading="lazy"></button>`;
                    });
                    html += '</div></div>';
                }
                const userImages = userMedia.filter(m => m.type === 'image');
                if (userImages.length > 0) {
                    html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">📸 Zdjęcia ekipy</div><div class="grid grid-cols-3 gap-1.5">';
                    userImages.forEach(m => {
                        const url = absUrl(m.file_path);
                        const idx = lbItems.length;
                        lbItems.push({ type: 'image', url, caption: '📸 ' + (m.caption || placeName) });
                        html += `<button type="button" data-sdm-lb="${idx}" class="block aspect-[4/3] rounded-lg overflow-hidden bg-mist/10"><img src="${escapeHtml(url)}" alt="" class="w-full h-full object-cover" loading="lazy"></button>`;
                    });
                    html += '</div></div>';
                }
                const userVideos = userMedia.filter(m => m.type === 'video');
                if (userVideos.length > 0) {
                    html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">🎬 Wideo</div><div class="grid grid-cols-2 gap-1.5">';
                    userVideos.forEach(m => {
                        const url = absUrl(m.file_path);
                        const idx = lbItems.length;
                        lbItems.push({ type: 'video', url, caption: '🎬 ' + (m.caption || placeName) });
                        html += `<button type="button" data-sdm-lb="${idx}" class="relative block aspect-video rounded-lg overflow-hidden bg-mist/20"><video src="${escapeHtml(url)}" class="w-full h-full object-cover" preload="metadata"></video><span class="absolute inset-0 flex items-center justify-center text-3xl text-white/90">▶</span></button>`;
                    });
                    html += '</div></div>';
                }
                const userLinks = userMedia.filter(m => m.type === 'link');
                if (userLinks.length > 0) {
                    html += '<div><div class="text-[10px] font-semibold text-mist uppercase tracking-wide mb-2">🔗 Linki</div><div class="space-y-1.5">';
                    userLinks.forEach(m => {
                        const cap = m.caption || m.url;
                        html += `<a href="${escapeHtml(m.url)}" target="_blank" rel="noopener" class="block px-3 py-2 rounded-lg bg-mist/10 hover:bg-mist/20 text-sm text-primary truncate">↗ ${escapeHtml(cap)}</a>`;
                    });
                    html += '</div></div>';
                }
                if (html === '') html = '<div class="text-center text-xs text-mist py-3 italic">Brak dodanych mediów.</div>';
                sdm.media.innerHTML = html;
                sdm.media.querySelectorAll('[data-sdm-lb]').forEach(btn => {
                    btn.addEventListener('click', () => openLb(parseInt(btn.getAttribute('data-sdm-lb'), 10)));
                });
            }

            function openLb(idx) {
                if (!lbItems.length) return;
                lbIdx = idx;
                renderLb();
                if (lb.root) { lb.root.classList.remove('hidden'); lb.root.style.display = 'flex'; }
            }
            function closeLb() {
                if (lb.root) { lb.root.classList.add('hidden'); lb.root.style.display = ''; }
                if (lb.stage) lb.stage.innerHTML = '';
            }
            function renderLb() {
                if (!lb.stage || !lbItems.length) return;
                const it = lbItems[lbIdx];
                const counter = (lbIdx + 1) + ' / ' + lbItems.length;
                if (it.type === 'video') lb.stage.innerHTML = `<video src="${escapeHtml(it.url)}" controls autoplay class="max-w-[92vw] max-h-[88vh] rounded-lg"></video>`;
                else lb.stage.innerHTML = `<img src="${escapeHtml(it.url)}" alt="" class="max-w-[92vw] max-h-[88vh] object-contain rounded-lg">`;
                lb.caption.textContent = counter + ' · ' + (it.caption || '');
            }
            function lbNav(d) { if (!lbItems.length) return; lbIdx = (lbIdx + d + lbItems.length) % lbItems.length; renderLb(); }
        })();
    </script>

        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=places,marker&language=pl&region=PL&loading=async&callback=initSummaryPlacesMap"></script>
        <script>
        (function () {
            const mapEl = document.getElementById('summary-places-map');
            if (!mapEl) return;
            let places = [];
            let routes = [];
            let tripStart = null;
            let participantsList = [];
            try {
                places = JSON.parse(mapEl.getAttribute('data-places') || '[]');
                routes = JSON.parse(mapEl.getAttribute('data-routes') || '[]');
                const startRaw = mapEl.getAttribute('data-start') || '';
                tripStart = startRaw !== '' ? JSON.parse(startRaw) : null;
                participantsList = JSON.parse(mapEl.getAttribute('data-participants') || '[]');
            } catch (e) {}

            let map = null;
            let markers = [];
            let permanentStartMarker = null;
            let routeMarkers = [];
            let routePolyline = null;
            let infoWindow = null;

            window.initSummaryPlacesMap = function () {
                map = new google.maps.Map(mapEl, {
                    center: { lat: 52.0, lng: 19.0 },
                    zoom: 5,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: true,
                    gestureHandling: 'greedy',
                });
                infoWindow = new google.maps.InfoWindow({ maxWidth: 320 });

                const bounds = new google.maps.LatLngBounds();

                // Permanent marker startu wyjazdu (jak admin ustawil)
                if (tripStart) {
                    permanentStartMarker = new google.maps.Marker({
                        position: { lat: parseFloat(tripStart.lat), lng: parseFloat(tripStart.lng) },
                        map: map,
                        title: '🏠 Start wyjazdu: ' + tripStart.name,
                        icon: makeHomeIcon('#1A1A2E'),
                        zIndex: 100,
                    });
                    permanentStartMarker.addListener('click', () => {
                        infoWindow.setContent(`<div style="min-width:220px"><h4 style="font-weight:700;margin:0 0 6px;font-size:15px">🏠 Start wyjazdu</h4><p style="font-size:14px;margin:4px 0"><strong>${escapeHtml(tripStart.name)}</strong></p><p style="font-size:12px;color:#666;margin-top:6px">Punkt z którego ekipa wyjeżdża i wraca.</p></div>`);
                        infoWindow.open(map, permanentStartMarker);
                    });
                    bounds.extend(permanentStartMarker.getPosition());
                }

                places.forEach((p, i) => {
                    const marker = new google.maps.Marker({
                        position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
                        map: map,
                        title: p.name,
                        icon: makeMarkerIcon(p.color || '#FF6B35', String(i + 1)),
                    });
                    marker._place = p;
                    marker.addListener('click', () => openInfo(marker, p));
                    markers.push(marker);
                    bounds.extend(marker.getPosition());
                });
                if (markers.length > 0 || permanentStartMarker) {
                    try { map.fitBounds(bounds, 80); } catch (e) {}
                }

                setupRouteButtons();
            };

            function makeMarkerIcon(color, label) {
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="48" viewBox="0 0 40 48">
                    <path d="M20 0C9 0 0 9 0 20c0 13 20 28 20 28s20-15 20-28C40 9 31 0 20 0z" fill="${color}"/>
                    <circle cx="20" cy="20" r="11" fill="white"/>
                    <text x="20" y="25" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="${color}">${label}</text>
                </svg>`;
                return {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    scaledSize: new google.maps.Size(40, 48),
                    anchor: new google.maps.Point(20, 48),
                };
            }

            function formatVisitMin(vm) {
                vm = parseInt(vm, 10) || 60;
                if (vm < 60) return vm + 'min';
                if (vm % 60 === 0) return (vm / 60) + 'h';
                return (vm / 60).toFixed(1).replace('.', ',') + 'h';
            }

            function openInfo(marker, p) {
                const cardBg = '#FFFFFF';
                const textInk = '#1A1A2E';
                const textMist = '#6B7280';

                let scoreBlock = '';
                if (p.avg !== null && p.count > 0) {
                    scoreBlock = `
                        <div style="display:flex;align-items:baseline;gap:6px;margin:8px 0 4px">
                            <span style="color:#F59E0B;font-size:18px;line-height:1">★</span>
                            <span style="color:${textInk};font-size:22px;font-weight:800;line-height:1">${p.avg.toFixed(1).replace('.', ',')}</span>
                            <span style="color:${textMist};font-size:12px">/ 5,0 · ${p.count} ${p.count === 1 ? 'głos' : (p.count < 5 ? 'głosy' : 'głosów')}</span>
                        </div>`;
                } else {
                    scoreBlock = `<p style="margin:6px 0;font-size:12px;color:${textMist};font-style:italic">Brak ocen</p>`;
                }

                // Helper - awatar z fallbackiem na inicjał
                const avatarHtml = (pa, sizePx) => {
                    if (pa.avatar) {
                        return `<img src="${escapeHtml(pa.avatar)}" alt="" style="width:${sizePx}px;height:${sizePx}px;border-radius:50%;object-fit:cover;border:1.5px solid ${escapeHtml(pa.color)};flex-shrink:0">`;
                    }
                    const initial = (pa.nick || '?').charAt(0).toUpperCase();
                    const fontPx = Math.max(8, Math.round(sizePx * 0.45));
                    return `<span style="display:inline-flex;align-items:center;justify-content:center;width:${sizePx}px;height:${sizePx}px;border-radius:50%;background:${escapeHtml(pa.color)};color:#fff;font-size:${fontPx}px;font-weight:700;flex-shrink:0">${escapeHtml(initial)}</span>`;
                };

                // Per-participant votes
                let votesHtml = '';
                if (p.votes && Object.keys(p.votes).length > 0 && participantsList.length > 0) {
                    const rows = [];
                    for (const pa of participantsList) {
                        const score = p.votes[pa.id];
                        if (score === undefined) continue;
                        const pct = (score / 5.0) * 100;
                        rows.push(`
                            <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
                                ${avatarHtml(pa, 18)}
                                <span style="color:${textInk};font-size:11px;width:55px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escapeHtml(pa.nick)}</span>
                                <div style="flex:1;height:5px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:60px">
                                    <div style="width:${pct}%;height:100%;background:${escapeHtml(pa.color)}"></div>
                                </div>
                                <span style="color:#F59E0B;font-size:11px;font-weight:700;font-family:monospace;width:26px;text-align:right">${score.toFixed(1).replace('.', ',')}★</span>
                            </div>`);
                    }
                    if (rows.length > 0) {
                        votesHtml = `
                            <div style="background:#f9fafb;border-radius:8px;padding:6px 8px;margin:8px 0">
                                <div style="font-size:9px;font-weight:700;color:${textMist};text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Kto jak zagłosował</div>
                                ${rows.join('')}
                            </div>`;
                    }
                }

                const descHtml = p.description
                    ? `<p style="color:${textInk};font-size:12px;line-height:1.4;margin:6px 0;max-height:60px;overflow:hidden">${escapeHtml(p.description)}</p>`
                    : '';

                const visitChip = `<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:999px;background:#fff7ed;color:#c2410c;font-size:10px;font-weight:600">⏱ ${formatVisitMin(p.visit_minutes || 60)}</span>`;
                const authorAvatar = p.author_avatar
                    ? `<img src="${escapeHtml(p.author_avatar)}" alt="" style="width:14px;height:14px;border-radius:50%;object-fit:cover;border:1px solid ${escapeHtml(p.color)}">`
                    : `<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:${escapeHtml(p.color)}"></span>`;
                const authorChip = `<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:999px;background:#f3f4f6;color:${textMist};font-size:10px">
                    ${authorAvatar}${escapeHtml(p.author)}</span>`;

                // Photo placeholder (lazy load - Google Places API async)
                const photoSlot = p.google_place_id
                    ? `<div data-sdm-popup-photo style="height:110px;margin:0 0 8px;background:#e5e7eb;border-radius:6px;background-size:cover;background-position:center"></div>`
                    : '';

                const galleryBtn = `<button type="button" data-summary-detail="${p.id}" style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;margin-top:10px;padding:8px 12px;border-radius:8px;background:#FF6B35;color:#fff;font-size:12px;font-weight:600;border:none;cursor:pointer;box-sizing:border-box">📷 Pokaż galerię i szczegóły →</button>`;

                infoWindow.setContent(`<div style="width:260px;max-width:100%;background:${cardBg};color:${textInk};font-family:Inter,system-ui,sans-serif;padding:0;overflow:hidden;box-sizing:border-box">
                    ${photoSlot}
                    <h4 style="font-weight:800;margin:0 0 4px;font-size:15px;line-height:1.25;color:${textInk}">${escapeHtml(p.name)}</h4>
                    ${p.address ? `<p style="color:${textMist};font-size:11px;margin:0 0 6px;line-height:1.3">${escapeHtml(p.address)}</p>` : ''}
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">${visitChip}${authorChip}</div>
                    ${scoreBlock}
                    ${descHtml}
                    ${votesHtml}
                    ${galleryBtn}
                </div>`);
                infoWindow.open(map, marker);

                // Lazy load photo Google
                if (p.google_place_id && google.maps.places && google.maps.places.Place) {
                    setTimeout(async () => {
                        try {
                            const place = new google.maps.places.Place({ id: p.google_place_id });
                            await place.fetchFields({ fields: ['photos'] });
                            const photoUrl = place.photos && place.photos[0]
                                ? place.photos[0].getURI({ maxWidth: 600, maxHeight: 240 })
                                : null;
                            if (photoUrl) {
                                document.querySelectorAll('[data-sdm-popup-photo]').forEach(el => {
                                    el.style.backgroundImage = `url("${photoUrl}")`;
                                    el.style.background = `url("${photoUrl}") center/cover`;
                                });
                            }
                        } catch (e) {}
                    }, 100);
                }
            }

            function setupRouteButtons() {
                document.querySelectorAll('[data-summary-show-route]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const idx = parseInt(btn.getAttribute('data-summary-show-route'), 10);
                        const color = btn.getAttribute('data-color') || '#FF6B35';
                        showRoute(routes[idx], color);
                    });
                });
            }

            async function showRoute(route, color) {
                clearRoute();
                if (!route) return;
                markers.forEach(m => m.setOpacity(0.2));
                if (permanentStartMarker) permanentStartMarker.setMap(null);

                const bounds = new google.maps.LatLngBounds();

                // Marker startu (jak istnieje)
                if (route.start) {
                    const startMarker = new google.maps.Marker({
                        position: { lat: parseFloat(route.start.lat), lng: parseFloat(route.start.lng) },
                        map: map,
                        icon: makeHomeIcon(color),
                        title: '🏠 Start: ' + route.start.name,
                        zIndex: 999,
                    });
                    routeMarkers.push(startMarker);
                    bounds.extend(startMarker.getPosition());
                }

                route.places.forEach((p, i) => {
                    const m = new google.maps.Marker({
                        position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
                        map: map,
                        icon: makeMarkerIcon(color, String(i + 1)),
                        zIndex: 1000 + i,
                    });
                    routeMarkers.push(m);
                    bounds.extend(m.getPosition());
                });

                // Polyline z startem + powrót (round trip jeśli start jest)
                const pathPoints = route.start
                    ? [route.start, ...route.places, route.start]
                    : route.places;
                if (pathPoints.length >= 2) {
                    const coords = pathPoints.map(p => `${p.lng},${p.lat}`).join(';');
                    try {
                        const r = await fetch(`https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`);
                        const data = await r.json();
                        if (data.code === 'Ok' && data.routes && data.routes[0]) {
                            const path = data.routes[0].geometry.coordinates.map(c => ({ lat: c[1], lng: c[0] }));
                            routePolyline = new google.maps.Polyline({
                                path, strokeColor: color, strokeOpacity: 0.85, strokeWeight: 4, map: map,
                            });
                        }
                    } catch (e) {
                        const path = pathPoints.map(p => ({ lat: parseFloat(p.lat), lng: parseFloat(p.lng) }));
                        routePolyline = new google.maps.Polyline({
                            path, strokeColor: color, strokeOpacity: 0.6, strokeWeight: 3, map: map, geodesic: true,
                        });
                    }
                }

                try { map.fitBounds(bounds, 80); } catch (e) {}
                document.getElementById('summary-places-map')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function makeHomeIcon(color) {
                const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="56" viewBox="0 0 44 56">
                    <path d="M22 0C10 0 0 10 0 22c0 16 22 34 22 34s22-18 22-34C44 10 34 0 22 0z" fill="${color}" stroke="white" stroke-width="3"/>
                    <path d="M22 11 L33 21 L31 21 L31 31 L24 31 L24 24 L20 24 L20 31 L13 31 L13 21 L11 21 Z" fill="white"/>
                </svg>`;
                return {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    scaledSize: new google.maps.Size(44, 56),
                    anchor: new google.maps.Point(22, 56),
                };
            }

            function clearRoute() {
                routeMarkers.forEach(m => m.setMap(null));
                routeMarkers = [];
                if (routePolyline) { routePolyline.setMap(null); routePolyline = null; }
                markers.forEach(m => m.setOpacity(1));
                // Przywroc permanent start marker (mogl byc ukryty przy pokazywaniu trasy)
                if (permanentStartMarker && tripStart) permanentStartMarker.setMap(map);
            }

            function escapeHtml(s) {
                return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
            }
        })();
        </script>

        <?php endif; ?>
    </div>
</section>
