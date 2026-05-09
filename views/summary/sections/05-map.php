<?php
/**
 * Sekcja 5: Mapa zbiorcza - wszystkie pinezki uczestnikow z legenda kolorow.
 * Legenda dziala jak filter: kliknij uczestnika zeby zobaczyc tylko jego pomysly.
 * @var \App\Services\SummaryAggregator $agg
 */
$pins         = $agg->mapPins();
$participants = $agg->participants();
$colors       = $agg->colorMap();
$anonymous    = $agg->isAnonymous();

// Zbierz pinezki w formacie JSON dla JS
$pinsJson = json_encode(array_map(static fn($p) => $p->toArray(), $pins), JSON_UNESCAPED_UNICODE);

// Mapa: participant_id => [name, color, count]
$byParticipant = [];
foreach ($participants as $i => $p) {
    $byParticipant[$p->id] = [
        'id'    => $p->id,
        'name'  => $anonymous ? ('Uczestnik ' . ($i + 1)) : $p->nickname,
        'color' => $colors[$p->id] ?? '#FF6B35',
        'count' => 0,
    ];
}
foreach ($pins as $pin) {
    if (isset($byParticipant[$pin->participantId])) {
        $byParticipant[$pin->participantId]['count']++;
    }
}
?>

<section class="py-16 md:py-24 3xl:py-32">
    <div class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8">

        <header class="mb-10 md:mb-14 text-center">
            <span class="inline-block mb-3 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">SEKCJA 5 / 7</span>
            <h2 class="font-display font-bold text-3xl md:text-5xl 3xl:text-6xl text-ink dark:text-pale mb-3">
                🗺️ Mapa pomysłów ekipy
            </h2>
            <p class="text-mist text-lg max-w-2xl mx-auto">
                Wszystkie pinezki, trasy i obszary uczestników w jednym miejscu. Kliknij uczestnika w legendzie, żeby zobaczyć tylko jego pomysły.
            </p>
        </header>

        <?php if (empty($pins)): ?>
            <p class="text-center text-mist italic">Nikt jeszcze nie zaznaczył miejsc na mapie.</p>
        <?php else: ?>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" crossorigin="">
            <div class="grid lg:grid-cols-[1fr_280px] gap-4">
                <div class="rounded-2xl overflow-hidden border-2 border-mist/15 bg-paper dark:bg-deep">
                    <div id="summary-map"
                         data-review-pins='<?= e($pinsJson) ?>'
                         style="height: 70vh; min-height: 520px;"></div>
                </div>

                <!-- Legenda + filtry -->
                <aside class="rounded-2xl border border-mist/15 bg-paper dark:bg-deep p-5">
                    <h3 class="font-display font-bold text-lg text-ink dark:text-pale mb-3">Filtruj</h3>
                    <ul class="space-y-1.5" id="map-filter-list">
                        <!-- Wszyscy - domyslnie aktywny -->
                        <li>
                            <button type="button"
                                    class="map-filter-btn w-full flex items-center gap-3 text-sm px-3 py-2 rounded-lg transition-all is-active"
                                    data-filter="all">
                                <span class="inline-block w-4 h-4 rounded-full shrink-0 bg-gradient-to-br from-primary via-secondary to-accent"></span>
                                <span class="flex-1 text-left font-semibold text-ink dark:text-pale">Wszyscy</span>
                                <span class="text-mist font-mono text-xs"><?= count($pins) ?></span>
                            </button>
                        </li>
                        <li class="border-t border-mist/15 my-2"></li>
                        <?php foreach ($byParticipant as $entry): ?>
                            <?php if ($entry['count'] === 0) continue; ?>
                            <li>
                                <button type="button"
                                        class="map-filter-btn w-full flex items-center gap-3 text-sm px-3 py-2 rounded-lg transition-all"
                                        data-filter="<?= (int) $entry['id'] ?>">
                                    <span class="inline-block w-4 h-4 rounded-full shrink-0" style="background:<?= e($entry['color']) ?>"></span>
                                    <span class="flex-1 text-left text-ink dark:text-pale font-medium"><?= e($entry['name']) ?></span>
                                    <span class="text-mist font-mono text-xs"><?= $entry['count'] ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="mt-4 pt-4 border-t border-mist/15 text-xs text-mist">
                        Łącznie <strong class="text-ink dark:text-pale font-mono"><?= count($pins) ?></strong> elementów na mapie.
                    </p>
                </aside>
            </div>

            <style>
                .map-filter-btn { cursor: pointer; }
                .map-filter-btn:hover { background-color: rgba(107, 114, 128, 0.1); }
                .map-filter-btn.is-active {
                    background-color: rgba(255, 107, 53, 0.12);
                    box-shadow: inset 3px 0 0 #FF6B35;
                }
                .map-filter-btn.is-dimmed { opacity: 0.45; }
            </style>

            <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" crossorigin=""></script>
            <script src="<?= e(asset('assets/js/map-utils.js')) ?>"></script>
            <script>
                (function () {
                    if (typeof L === 'undefined' || !window.MapUtils) return;
                    const el = document.getElementById('summary-map');
                    if (!el || el._summaryMapInited) return;
                    el._summaryMapInited = true;

                    let pins = [];
                    try { pins = JSON.parse(el.getAttribute('data-review-pins') || '[]'); } catch (e) {}
                    if (!Array.isArray(pins) || pins.length === 0) return;

                    const map = L.map(el, { scrollWheelZoom: false }).setView([52.0, 19.0], 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap',
                    }).addTo(map);

                    // Trzymamy warstwy pogrupowane wg participant_id zeby moc filtrowac.
                    // Struktura: { [participantId]: [layer, layer, ...] }
                    const layersByParticipant = {};
                    const allLayers = [];

                    for (const pin of pins) {
                        const layer = window.MapUtils.geojsonToLayer(pin, '#FF6B35');
                        if (!layer) continue;
                        layer.bindPopup(window.MapUtils.buildPopup(pin));
                        const pid = pin.participant_id;
                        if (!layersByParticipant[pid]) layersByParticipant[pid] = [];
                        layersByParticipant[pid].push(layer);
                        allLayers.push(layer);
                    }

                    // Aktywna grupa - to co jest aktualnie pokazane na mapie
                    let group = L.featureGroup().addTo(map);
                    const showLayers = (layers) => {
                        group.clearLayers();
                        for (const layer of layers) group.addLayer(layer);
                        if (group.getLayers().length > 0) {
                            try { map.fitBounds(group.getBounds(), { maxZoom: 9, padding: [40, 40] }); } catch (e) {}
                        }
                    };

                    // Default: wszyscy
                    showLayers(allLayers);

                    // Filter buttons
                    const buttons = document.querySelectorAll('.map-filter-btn');
                    buttons.forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const filter = btn.getAttribute('data-filter');

                            buttons.forEach((b) => {
                                b.classList.remove('is-active');
                                b.classList.remove('is-dimmed');
                            });
                            btn.classList.add('is-active');

                            if (filter === 'all') {
                                showLayers(allLayers);
                            } else {
                                const pid = parseInt(filter, 10);
                                const layers = layersByParticipant[pid] || [];
                                showLayers(layers);
                                // Inne przyciski przygaszone dla podkreslenia ze filtrujemy
                                buttons.forEach((b) => {
                                    if (b !== btn && b.getAttribute('data-filter') !== 'all') {
                                        b.classList.add('is-dimmed');
                                    }
                                });
                            }
                        });
                    });

                    el.addEventListener('click', () => map.scrollWheelZoom.enable(), { once: true });
                })();
            </script>
        <?php endif; ?>
    </div>
</section>
