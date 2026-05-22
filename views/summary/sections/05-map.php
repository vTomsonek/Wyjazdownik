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

// Propozycje tras
$routes = (new RouteSuggestionService())->suggest($trip->id);

// JSON dla JS
$placesForJs = array_map(static function ($r) {
    return [
        'id'      => $r['place']->id,
        'name'    => $r['place']->name,
        'lat'     => $r['place']->lat,
        'lng'     => $r['place']->lng,
        'address' => $r['place']->address,
        'avg'     => $r['avg'],
        'count'   => $r['vote_count'],
        'author'  => $r['author'],
        'color'   => $r['color'],
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

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">SEKCJA 5 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🗺️ Atrakcje ekipy
            </h2>
            <p class="text-mist text-lg max-w-2xl mx-auto">
                Konkretne miejsca dodane przez ekipę. Algorytm zaproponował też możliwe trasy samochodowe.
            </p>
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

        <!-- Status ocen per uczestnik (peer pressure: dążymy żeby każdy ocenił wszystko) -->
        <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-5 mb-6">
            <h3 class="font-display font-bold text-base text-ink dark:text-pale mb-3 flex items-center gap-2">
                ⭐ Kto ile ocenił
            </h3>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                <?php foreach ($participants as $i => $p):
                    $voted = $voteCountByParticipant[$p->id] ?? 0;
                    $missing = $totalPlaces - $voted;
                    $fullDone = $missing === 0 && $totalPlaces > 0;
                    $nick = $nicks[$p->id] ?? '?';
                    $color = $colors[$p->id] ?? '#FF6B35';
                ?>
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl <?= $fullDone ? 'bg-secondary/10 border border-secondary/30' : ($missing > 0 ? 'bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40' : 'bg-mist/10') ?>">
                    <?php if (!$anonymous && $p->avatarPath): ?>
                        <img src="<?= e(asset($p->avatarPath)) ?>" alt=""
                             class="w-7 h-7 rounded-full object-cover shrink-0 border-2"
                             style="border-color: <?= e($color) ?>">
                    <?php else: ?>
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-white text-xs font-bold shrink-0" style="background:<?= e($color) ?>">
                            <?= e(mb_strtoupper(mb_substr($nick, 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-ink dark:text-pale truncate"><?= e($nick) ?></div>
                        <div class="text-xs <?= $fullDone ? 'text-secondary font-semibold' : ($missing > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-mist') ?>">
                            <?php if ($fullDone): ?>
                                ✓ <?= $voted ?>/<?= $totalPlaces ?> ocenione
                            <?php elseif ($missing > 0): ?>
                                <?= $voted ?>/<?= $totalPlaces ?> · brakuje <?= $missing ?>
                            <?php else: ?>
                                brak miejsc
                            <?php endif; ?>
                        </div>
                    </div>
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
            <p class="text-mist text-sm mb-5">
                Algorytm pogrupował miejsca po regionach i ułożył trasy w optymalnej kolejności. Kliknij propozycję żeby zobaczyć na mapie.
            </p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $routeColors = ['#FF6B35', '#2EC4B6', '#FFD23F', '#C2410C', '#0EA5E9'];
                foreach ($routes as $idx => $route):
                    $color = $routeColors[$idx % count($routeColors)];
                ?>
                <div class="rounded-2xl border-2 border-mist/15 bg-cream dark:bg-night p-5 hover:border-primary/30 transition" data-summary-route-idx="<?= $idx ?>">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h4 class="font-display font-bold text-lg text-ink dark:text-pale leading-snug"><?= e($route['name']) ?></h4>
                        <span class="w-4 h-4 rounded-full shrink-0 mt-1.5" style="background:<?= e($color) ?>"></span>
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs text-mist mb-3">
                        <span>📍 <?= count($route['places']) ?> miejsc</span>
                        <span>🛣️ ~<?= (int) $route['distance_km'] ?> km</span>
                        <span>⭐ <?= number_format($route['avg_score'], 1, ',', '') ?></span>
                    </div>
                    <ol class="text-sm text-ink/80 dark:text-pale/80 space-y-1 mb-3">
                        <?php foreach (array_slice($route['places'], 0, 5) as $i => $rp): ?>
                            <li><?= ($i + 1) ?>. <?= e($rp['name']) ?></li>
                        <?php endforeach; ?>
                        <?php if (count($route['places']) > 5): ?>
                            <li class="text-mist italic">+ <?= count($route['places']) - 5 ?> więcej</li>
                        <?php endif; ?>
                    </ol>
                    <button type="button" data-summary-show-route="<?= $idx ?>" data-color="<?= e($color) ?>"
                            class="w-full px-3 py-2 rounded-full text-white font-semibold text-sm hover:scale-105 transition"
                            style="background:<?= e($color) ?>">
                        Pokaż na mapie
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <script async defer
                src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=places,marker&language=pl&region=PL&loading=async&callback=initSummaryPlacesMap"></script>
        <script>
        (function () {
            const mapEl = document.getElementById('summary-places-map');
            if (!mapEl) return;
            let places = [];
            let routes = [];
            let tripStart = null;
            try {
                places = JSON.parse(mapEl.getAttribute('data-places') || '[]');
                routes = JSON.parse(mapEl.getAttribute('data-routes') || '[]');
                const startRaw = mapEl.getAttribute('data-start') || '';
                tripStart = startRaw !== '' ? JSON.parse(startRaw) : null;
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

            function openInfo(marker, p) {
                let scoreHtml = '';
                if (p.avg !== null && p.count > 0) {
                    scoreHtml = `<p style="margin:6px 0;font-size:13px"><strong style="color:#F59E0B">★ ${p.avg.toFixed(1).replace('.', ',')}</strong> <span style="color:#666">(${p.count} ${p.count === 1 ? 'ocena' : 'ocen'})</span></p>`;
                }
                infoWindow.setContent(`<div style="min-width:220px">
                    <h4 style="font-weight:700;margin:0 0 6px;font-size:15px">${escapeHtml(p.name)}</h4>
                    ${p.address ? `<p style="color:#666;font-size:12px;margin:4px 0">${escapeHtml(p.address)}</p>` : ''}
                    ${scoreHtml}
                    <p style="font-size:11px;color:#888;margin-top:6px">— ${escapeHtml(p.author)}</p>
                </div>`);
                infoWindow.open(map, marker);
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
