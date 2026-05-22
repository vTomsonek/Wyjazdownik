<?php
/**
 * Tryb trasy - live geolocation + mapa atrakcji posortowanych po dystansie.
 *
 * @var \App\Models\Trip            $trip
 * @var array<int,array<string,mixed>> $placesJson
 * @var string                       $googleMapsApiKey
 */
$placesData = json_encode($placesJson, JSON_UNESCAPED_UNICODE);
$tripName   = $trip->name;
?>
<div class="fixed inset-0 flex flex-col bg-cream dark:bg-night">

    <!-- Top bar -->
    <div id="route-topbar" class="shrink-0 z-20 bg-paper dark:bg-deep border-b border-mist/15 shadow-sm">
        <div class="flex items-center gap-3 px-3 py-2.5">
            <a href="<?= e(url('/summary/' . $trip->summaryPublicToken)) ?>"
               class="shrink-0 text-mist hover:text-primary transition" aria-label="Wróć do podsumowania">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex-1 min-w-0">
                <div class="text-[10px] uppercase tracking-wide text-mist font-semibold">🚗 Tryb trasy</div>
                <div class="font-display font-bold text-base text-ink dark:text-pale truncate"><?= e($tripName) ?></div>
            </div>
            <button type="button" id="route-fullscreen"
                    class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-mist/15 text-ink dark:text-pale hover:bg-mist/25 transition"
                    aria-label="Pełny ekran mapy">
                <svg id="route-fullscreen-icon-expand" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/>
                </svg>
                <svg id="route-fullscreen-icon-collapse" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 3v3a2 2 0 0 1-2 2H3M21 8h-3a2 2 0 0 1-2-2V3M3 16h3a2 2 0 0 1 2 2v3M16 21v-3a2 2 0 0 1 2-2h3"/>
                </svg>
            </button>
            <button type="button" id="route-recenter"
                    class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary text-white shadow-pop hover:bg-primary-deep transition"
                    aria-label="Wyśrodkuj na mojej pozycji" disabled>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
                </svg>
            </button>
        </div>
        <div id="route-gps-status" class="hidden px-3 py-2 text-xs bg-amber-100 dark:bg-amber-950/40 text-amber-900 dark:text-amber-200 border-t border-amber-300/40">
            <!-- statusy GPS wstawiane przez JS -->
        </div>
    </div>

    <?php if ($googleMapsApiKey === ''): ?>
        <div class="flex-1 flex items-center justify-center p-6 text-center">
            <div class="rounded-2xl bg-amber-100 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 p-6 max-w-md">
                <p class="text-amber-900 dark:text-amber-200">⚠️ Brak klucza Google Maps API w konfiguracji.</p>
            </div>
        </div>
    <?php elseif (empty($placesJson)): ?>
        <div class="flex-1 flex items-center justify-center p-6 text-center">
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/15 p-6 max-w-md">
                <div class="text-4xl mb-3">🗺️</div>
                <p class="text-mist">Brak miejsc na mapie. Wróć do <a href="<?= e(url('/summary/' . $trip->summaryPublicToken)) ?>" class="text-primary underline">podsumowania</a>.</p>
            </div>
        </div>
    <?php else: ?>

    <!-- Mapa - flex-1, zajmuje cala dostepna wysokosc minus bottom sheet -->
    <div id="route-map-wrap" class="flex-1 min-h-0 relative">
        <div id="route-map" class="absolute inset-0 bg-cream dark:bg-deep"></div>
        <!-- Floating buttons (poza route-map zeby Google Maps ich nie nadpisalo) -->
        <button type="button" id="route-fs-exit"
                class="hidden absolute top-3 right-3 z-30 inline-flex items-center justify-center w-11 h-11 rounded-full bg-paper dark:bg-deep text-ink dark:text-pale border border-mist/30 shadow-pop"
                aria-label="Wyjdź z trybu pełnoekranowego">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M8 3v3a2 2 0 0 1-2 2H3M21 8h-3a2 2 0 0 1-2-2V3M3 16h3a2 2 0 0 1 2 2v3M16 21v-3a2 2 0 0 1 2-2h3"/>
            </svg>
        </button>
        <button type="button" id="route-fs-recenter"
                class="hidden absolute bottom-3 right-3 z-30 inline-flex items-center justify-center w-11 h-11 rounded-full bg-primary text-white shadow-pop"
                aria-label="Wyśrodkuj na mojej pozycji">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
            </svg>
        </button>
        <!-- Overlay loadera (na czas pobierania pozycji / mapy) -->
        <div id="route-loader" class="absolute inset-0 z-10 flex items-center justify-center bg-cream/80 dark:bg-night/80 pointer-events-none">
            <div class="rounded-2xl bg-paper dark:bg-deep border border-mist/20 px-4 py-3 shadow-pop flex items-center gap-3">
                <div class="w-5 h-5 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm text-ink dark:text-pale">Ładowanie mapy...</span>
            </div>
        </div>
    </div>

    <!-- Bottom sheet - lista miejsc posortowana po dystansie -->
    <div id="route-sheet" class="shrink-0 bg-paper dark:bg-deep border-t border-mist/15 shadow-pop-lg transition-all duration-200" style="max-height: 45vh;">
        <div class="px-4 pt-3 pb-1.5 flex items-center justify-between">
            <h2 class="font-display font-bold text-sm text-ink dark:text-pale">
                📍 Miejsca w okolicy <span id="route-count" class="font-normal text-mist">(<?= count($placesJson) ?>)</span>
            </h2>
            <span id="route-sort-info" class="text-xs text-mist">Sortuj: dystans</span>
        </div>
        <div id="route-places-list" class="overflow-y-auto scroll-thin px-3 pb-3 space-y-2" style="max-height: calc(45vh - 40px);">
            <!-- Karty miejsc wstawiane przez JS -->
            <div class="text-center text-xs text-mist py-3 italic">Włącz lokalizację aby zobaczyć dystanse...</div>
        </div>
    </div>

    <!-- Detail modal - szczegoly miejsca (opis + galeria) -->
    <div id="route-detail-modal" class="hidden fixed inset-0 z-40 bg-black/70 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-paper dark:bg-deep w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-pop-lg overflow-hidden flex flex-col" style="max-height: 92vh;">
            <!-- Sticky header -->
            <div class="shrink-0 px-5 pt-4 pb-3 border-b border-mist/15 flex items-start gap-3">
                <span id="route-detail-author" class="inline-flex items-center justify-center w-9 h-9 rounded-full text-white text-sm font-bold shrink-0"></span>
                <div class="flex-1 min-w-0">
                    <h2 id="route-detail-name" class="font-display font-bold text-lg text-ink dark:text-pale leading-tight"></h2>
                    <p id="route-detail-address" class="text-xs text-mist mt-0.5"></p>
                </div>
                <button type="button" id="route-detail-close"
                        class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-mist/15 text-mist transition"
                        aria-label="Zamknij">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <!-- Scrollable body -->
            <div class="flex-1 overflow-y-auto scroll-thin px-5 py-4">
                <!-- Meta chips -->
                <div class="flex items-center gap-2 flex-wrap mb-3">
                    <span id="route-detail-rating" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/30 border border-amber-300/40 text-xs font-semibold text-amber-700 dark:text-amber-200"></span>
                    <span id="route-detail-visit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary/10 border border-primary/30 text-xs font-medium text-primary"></span>
                    <span id="route-detail-distance" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-secondary/10 border border-secondary/30 text-xs font-medium text-secondary hidden"></span>
                </div>
                <!-- Opis -->
                <p id="route-detail-description" class="text-sm text-ink dark:text-pale leading-relaxed mb-4 whitespace-pre-line"></p>
                <!-- Media -->
                <div id="route-detail-media" class="space-y-4">
                    <div class="text-center text-xs text-mist py-3 italic">Ładuję media...</div>
                </div>
            </div>
            <!-- Sticky CTA -->
            <div class="shrink-0 px-5 py-3 border-t border-mist/15 bg-paper dark:bg-deep">
                <a id="route-detail-navigate" href="#" target="_blank" rel="noopener"
                   class="block w-full text-center px-5 py-3 rounded-xl bg-primary text-white font-bold shadow-pop hover:bg-primary-deep transition">
                    🚗 Nawiguj do tego miejsca
                </a>
            </div>
        </div>
    </div>

    <!-- Lightbox dla galerii -->
    <div id="route-lightbox" class="hidden fixed inset-0 z-50 bg-black/95 flex items-center justify-center">
        <button type="button" id="route-lightbox-close" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white transition" aria-label="Zamknij">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <button type="button" id="route-lightbox-prev" class="absolute left-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white transition" aria-label="Poprzednie">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <button type="button" id="route-lightbox-next" class="absolute right-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white transition" aria-label="Następne">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
        </button>
        <div id="route-lightbox-stage" class="max-w-[92vw] max-h-[88vh] flex items-center justify-center"></div>
        <div id="route-lightbox-caption" class="absolute bottom-4 left-0 right-0 text-center text-white/90 text-sm px-4"></div>
    </div>

    <script>
        window.__LIVE_ROUTE_CONFIG__ = {
            places: <?= $placesData ?>,
            tripName: <?= json_encode($tripName, JSON_UNESCAPED_UNICODE) ?>,
            mediaUrlTemplate: <?= json_encode(url('/summary/' . $trip->summaryPublicToken . '/places/ID/media'), JSON_UNESCAPED_SLASHES) ?>,
            assetBase: <?= json_encode(rtrim((string) env('APP_URL', ''), '/') . '/', JSON_UNESCAPED_SLASHES) ?>,
        };
    </script>
    <script src="<?= e(asset('assets/js/route-live.js')) ?>"></script>
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=marker,places&language=pl&region=PL&loading=async&callback=initLiveRoute"></script>

    <?php endif; ?>
</div>
