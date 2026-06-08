<?php
/**
 * Kolaboratywna mapa atrakcji - Google Maps + Places API.
 *
 * @var \App\Models\Trip        $trip
 * @var \App\Models\Participant $participant
 * @var list<\App\Models\TripPlace> $places
 * @var array<int, array{nickname:string,color:string,avatar:?string}> $authors
 * @var string $myColor
 * @var string $googleMapsApiKey
 */
use App\Helpers\Csrf;

$csrfToken = Csrf::token();
$placesJson = json_encode(array_map(static fn($p) => $p->toArray(), $places), JSON_UNESCAPED_UNICODE);
$authorsJson = json_encode($authors, JSON_UNESCAPED_UNICODE);
$voteStatsJson = json_encode($voteStats ?? [], JSON_UNESCAPED_UNICODE);
$hasApiKey = $googleMapsApiKey !== '';
?>

<style>
    .gm-style .gm-style-iw-c { padding-right: 12px !important; }
    #places-map { background: #e5e3df; }
    /* Custom InfoWindow content */
    .iw-place { min-width: 240px; max-width: 320px; }
    .iw-place h4 { font-weight: 700; margin: 0 0 6px; font-size: 15px; color: #1A1A2E; }
    .iw-place p { margin: 4px 0; font-size: 13px; color: #444; line-height: 1.4; }
    .iw-place .author { font-size: 11px; color: #888; margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee; }
    .iw-place .photo-strip { display: flex; gap: 4px; margin: 8px 0; overflow-x: auto; }
    .iw-place .photo-strip img { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
    /* PlaceAutocompleteElement wrapper - matchowanie naszego stylu inputa */
    .gmp-place-autocomplete-wrapper { width: 100%; }
    .gmp-place-autocomplete-wrapper gmp-place-autocomplete {
        width: 100%;
        display: block;
    }
</style>

<section class="section">
    <div class="wrap" style="max-width:1400px">

    <!-- Header w landing v2 stylu -->
    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <a href="<?= e(url('/p/' . $participant->accessToken)) ?>"
               class="text-sm text-mist hover:text-primary transition inline-flex items-center gap-1 mb-3"
               style="color: var(--fg-2)">
                <span class="iconify" data-icon="ph:arrow-left-bold"></span> Wróć do ankiety
            </a>
            <span class="eyebrow eyebrow--teal" style="margin-bottom:14px">
                <span class="iconify" data-icon="ph:map-pin-bold"></span> Atrakcje ekipy
            </span>
            <h1 style="font-family: var(--font-display); font-weight: 800; font-size: clamp(28px, 4vw, 44px); margin: 14px 0 12px; color: var(--heading); line-height:1.1">
                🗺️ Wspólna mapa miejsc
            </h1>
            <p style="color: var(--fg-2); max-width: 560px; line-height:1.55">
                Dodaj konkretne miejsca, które chcesz odwiedzić. Inni zobaczą Twoje propozycje
                i ocenią ile chętnie tam pojadą. Im więcej miejsc, tym lepsze trasy.
            </p>
        </div>
        <?php if ($hasApiKey): ?>
        <button id="add-place-btn" type="button" class="btn btn-primary" style="align-self:start">
            <span class="iconify" data-icon="ph:plus-bold"></span>
            Dodaj miejsce
        </button>
        <?php endif; ?>
    </div>

    <?php if (!$hasApiKey): ?>
        <div class="rounded-2xl bg-amber-100 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 p-6 text-center">
            <p class="font-semibold text-amber-900 dark:text-amber-200 mb-2">⚠️ Mapa wymaga konfiguracji</p>
            <p class="text-sm text-amber-800 dark:text-amber-300">
                Administrator musi skonfigurować klucz <code class="font-mono bg-amber-200/50 dark:bg-amber-900/50 px-1.5 py-0.5 rounded">GOOGLE_MAPS_API_KEY</code> w pliku <code class="font-mono bg-amber-200/50 dark:bg-amber-900/50 px-1.5 py-0.5 rounded">.env</code>.
                Bez tego mapa atrakcji nie zadziała.
            </p>
        </div>
    <?php else: ?>

    <!-- Mapa + lista -->
    <div class="grid lg:grid-cols-[1fr_380px] gap-4">
        <div class="rounded-2xl overflow-hidden border-2 border-mist/15 bg-paper dark:bg-deep">
            <?php
            $startJson = ($trip->startLat !== null && $trip->startLng !== null)
                ? json_encode([
                    'name' => $trip->startName ?? 'Punkt startowy',
                    'lat'  => $trip->startLat,
                    'lng'  => $trip->startLng,
                ], JSON_UNESCAPED_UNICODE)
                : '';
            ?>
            <div id="places-map"
                 data-places='<?= e($placesJson) ?>'
                 data-authors='<?= e($authorsJson) ?>'
                 data-votes='<?= e($voteStatsJson) ?>'
                 data-start='<?= e($startJson) ?>'
                 data-my-color="<?= e($myColor) ?>"
                 style="height: 75vh; min-height: 520px;"></div>
        </div>

        <!-- Lista miejsc -->
        <aside class="rounded-2xl border border-mist/15 bg-paper dark:bg-deep max-h-[75vh] flex flex-col overflow-hidden">
            <h3 class="shrink-0 px-5 pt-5 pb-3 font-display font-bold text-lg text-ink dark:text-pale border-b border-mist/15">
                Miejsca <span class="text-mist font-normal text-sm">(<?= count($places) ?>)</span>
            </h3>
            <div id="places-list" class="flex-1 overflow-y-auto scroll-thin px-5 py-4 space-y-3">
                <?php if (empty($places)): ?>
                    <p class="text-mist text-sm italic">
                        Nikt jeszcze nie dodał miejsca. Bądź pierwszy! Kliknij "Dodaj miejsce"
                        i wyszukaj nazwę miejsca lub kliknij na mapie.
                    </p>
                <?php else: ?>
                    <?php foreach ($places as $p):
                        $author = $authors[$p->participantId] ?? ['nickname' => '?', 'color' => '#6B7280', 'avatar' => null];
                        $isMine = $p->participantId === $participant->id;
                        $vs = $voteStats[$p->id] ?? ['avg' => null, 'count' => 0, 'my_score' => null];
                    ?>
                    <article class="rounded-xl border border-mist/15 p-3 hover:border-primary/30 transition cursor-pointer"
                             data-place-id="<?= e($p->id) ?>"
                             data-lat="<?= e($p->lat) ?>"
                             data-lng="<?= e($p->lng) ?>">
                        <div class="flex items-start gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-white text-xs font-bold shrink-0 mt-0.5"
                                  style="background:<?= e($author['color']) ?>">
                                <?= e(mb_strtoupper(mb_substr($author['nickname'], 0, 1))) ?>
                            </span>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm text-ink dark:text-pale truncate"><?= e($p->name) ?></h4>
                                <?php if ($p->address): ?>
                                    <p class="text-xs text-mist truncate"><?= e($p->address) ?></p>
                                <?php endif; ?>
                                <?php if ($p->description): ?>
                                    <p class="text-xs text-ink/70 dark:text-pale/70 mt-1 line-clamp-2"><?= e($p->description) ?></p>
                                <?php endif; ?>
                                <!-- Statystyki ocen + czas zwiedzania -->
                                <div class="mt-1.5 flex items-center gap-1.5 text-xs">
                                    <span data-vote-summary class="inline-flex items-center gap-1.5">
                                        <?php if ($vs['avg'] !== null): ?>
                                            <span class="text-amber-400">★</span>
                                            <span class="font-semibold text-ink dark:text-pale"><?= number_format((float) $vs['avg'], 1, ',', '') ?></span>
                                            <span class="text-mist">(<?= $vs['count'] ?>)</span>
                                        <?php else: ?>
                                            <span class="text-mist italic">Brak ocen</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-mist">·</span>
                                    <span data-visit-chip class="text-mist">⏱️ <?php
                                        $vm = $p->visitMinutes;
                                        echo $vm < 60 ? $vm . 'min' : (
                                            $vm % 60 === 0 ? ($vm / 60) . 'h' : sprintf('%.1fh', $vm / 60)
                                        );
                                    ?></span>
                                    <span data-my-score class="ml-auto text-secondary <?= $vs['my_score'] === null ? 'hidden' : '' ?>">
                                        <?= $vs['my_score'] !== null ? 'Twoja: ' . number_format((float) $vs['my_score'], 1, ',', '') . '★' : '' ?>
                                    </span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-2 text-xs text-mist">
                                    <span>— <?= e($author['nickname']) ?></span>
                                    <?php if ($isMine): ?>
                                        <button type="button" class="ml-auto text-red-500 hover:text-red-700 transition"
                                                data-delete-place="<?= e($p->id) ?>" title="Usuń">
                                            🗑
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <!-- Propozycje tras - algorytm klastrowania + TSP -->
    <section id="routes-section" class="mt-8">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="font-display font-bold text-2xl text-ink dark:text-pale">
                🚗 Propozycje tras
            </h2>
            <button type="button" id="routes-clear" class="hidden text-sm text-mist hover:text-primary transition">
                ← Pokaż wszystkie miejsca
            </button>
        </div>
        <p class="text-mist text-sm mb-4">
            Algorytm grupuje top-ocenione miejsca po regionach geograficznych. Kliknij propozycję żeby zobaczyć trasę na mapie.
        </p>
        <div id="routes-list" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <p class="text-mist italic text-sm col-span-full">Ładuję propozycje...</p>
        </div>
    </section>

    <!-- Modal dodawania miejsca -->
    <div id="add-place-modal" class="fixed inset-0 z-[9999] hidden bg-black/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-paper dark:bg-deep rounded-2xl shadow-pop-lg max-w-lg w-full max-h-[90vh] overflow-y-auto scroll-thin">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-display font-bold text-xl text-ink dark:text-pale">Dodaj miejsce</h3>
                    <button type="button" id="close-modal" class="text-mist hover:text-primary transition text-2xl leading-none">×</button>
                </div>

                <form id="add-place-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                            Wyszukaj miejsce <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="place-search" autocomplete="off" required maxlength="200"
                               placeholder="np. Park Krka, Plitvice, Hotel..."
                               class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
                        <p class="mt-1 text-xs text-mist">Wpisz nazwę i wybierz z podpowiedzi Google. Możesz też kliknąć na mapie żeby dodać dowolne miejsce.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                            Nazwa wyświetlana
                        </label>
                        <input type="text" id="place-name" required maxlength="200"
                               placeholder="Jak nazwać miejsce w naszej liście?"
                               class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                            Opis (opcjonalnie)
                        </label>
                        <textarea id="place-description" maxlength="2000" rows="3"
                                  placeholder="Czemu warto? Co tam jest fajnego?"
                                  class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition resize-y"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink dark:text-pale mb-1.5">
                            ⏱️ Ile czasu zajmie zwiedzanie?
                        </label>
                        <p class="text-xs text-mist mb-3">Algorytm użyje tego do oszacowania długości trasy.</p>
                        <div class="rounded-xl bg-cream dark:bg-night border-2 border-mist/20 p-4">
                            <div class="flex items-baseline justify-between gap-3 mb-3">
                                <span id="place-visit-display" class="font-display font-bold text-2xl text-primary">1h</span>
                                <span id="place-visit-hint" class="text-xs text-mist text-right">krótki przystanek</span>
                            </div>
                            <input type="range" id="place-visit-minutes"
                                   min="15" max="720" step="15" value="60"
                                   class="w-full accent-primary cursor-pointer">
                            <div class="flex justify-between mt-1.5 text-[10px] text-mist">
                                <span>15min</span><span>1h</span><span>4h</span><span>8h</span><span>12h</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden fields -->
                    <input type="hidden" id="place-lat">
                    <input type="hidden" id="place-lng">
                    <input type="hidden" id="place-address">
                    <input type="hidden" id="place-country">
                    <input type="hidden" id="place-google-id">

                    <div id="selected-place" class="hidden p-3 rounded-xl bg-secondary/10 border border-secondary/30 text-sm">
                        <div class="font-semibold text-secondary mb-1">✓ Wybrano lokalizację</div>
                        <div id="selected-place-info" class="text-ink dark:text-pale"></div>
                    </div>

                    <div id="form-error" class="hidden p-3 rounded-xl bg-red-100 dark:bg-red-950/40 border border-red-300 text-sm text-red-700 dark:text-red-300"></div>

                    <div class="flex gap-2 pt-2">
                        <button type="button" id="cancel-modal"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">
                            Anuluj
                        </button>
                        <button type="submit" id="submit-place"
                                class="flex-1 px-4 py-2.5 rounded-xl bg-primary-deep text-white font-semibold hover:bg-primary transition disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                            Dodaj
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal szczegolow miejsca (z galeria/media/linki/upload) -->
    <div id="detail-modal" class="fixed inset-0 z-[9999] hidden bg-black/70 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-paper dark:bg-deep rounded-2xl shadow-pop-lg max-w-3xl w-full max-h-[90vh] flex flex-col">
            <!-- Sticky header - cały czas widoczny -->
            <div class="px-6 pt-6 pb-4 border-b border-mist/15 shrink-0">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 id="detail-name" class="font-display font-bold text-2xl text-ink dark:text-pale"></h3>
                        <p id="detail-address" class="text-sm text-mist mt-1"></p>
                        <p id="detail-author" class="text-xs text-mist mt-1.5"></p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" id="detail-edit-btn"
                                class="hidden w-10 h-10 flex items-center justify-center rounded-full text-mist hover:text-primary hover:bg-mist/10 transition"
                                title="Edytuj nazwę i opis">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </button>
                        <button type="button" id="detail-close"
                                class="w-10 h-10 flex items-center justify-center rounded-full text-mist hover:text-primary hover:bg-mist/10 transition leading-none">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scrollable body -->
            <div class="px-6 py-5 overflow-y-auto flex-1 scroll-thin">

                <!-- View mode: opis jako paragraf -->
                <p id="detail-description" class="text-ink dark:text-pale leading-relaxed mb-5 whitespace-pre-line"></p>

                <!-- Edit mode (initially hidden) - form do edycji nazwy/opisu -->
                <form id="detail-edit-form" class="hidden mb-5 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-mist mb-1">Nazwa</label>
                        <input type="text" id="detail-edit-name" maxlength="200" required
                               class="w-full px-3 py-2 rounded-lg bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-mist mb-1">Opis</label>
                        <textarea id="detail-edit-description" maxlength="2000" rows="4"
                                  placeholder="Czemu warto tu pojechać?"
                                  class="w-full px-3 py-2 rounded-lg bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-mist mb-2">⏱️ Czas zwiedzania</label>
                        <div class="rounded-lg bg-cream dark:bg-night border-2 border-mist/20 p-3">
                            <div class="flex items-baseline justify-between gap-3 mb-2">
                                <span id="detail-edit-visit-display" class="font-display font-bold text-xl text-primary">1h</span>
                                <span id="detail-edit-visit-hint" class="text-[11px] text-mist text-right">krótki przystanek</span>
                            </div>
                            <input type="range" id="detail-edit-visit-minutes"
                                   min="15" max="720" step="15" value="60"
                                   class="w-full accent-primary cursor-pointer">
                            <div class="flex justify-between mt-1 text-[10px] text-mist">
                                <span>15min</span><span>1h</span><span>4h</span><span>8h</span><span>12h</span>
                            </div>
                        </div>
                    </div>
                    <div id="detail-edit-error" class="hidden p-2.5 rounded-lg bg-red-100 dark:bg-red-950/40 border border-red-300 text-sm text-red-700"></div>
                    <div class="flex gap-2">
                        <button type="button" id="detail-edit-cancel" class="flex-1 px-4 py-2 rounded-lg bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">Anuluj</button>
                        <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-primary-deep text-white font-semibold hover:bg-primary transition">Zapisz</button>
                    </div>
                </form>

                <!-- Sekcja oceniania (half-star support) -->
                <div id="detail-rating" class="mb-5 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/50">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <h4 class="font-semibold text-sm text-ink dark:text-pale mb-1">Czy chcesz tu pojechać?</h4>
                            <p class="text-xs text-mist">Klik lewa połowa gwiazdki = .5, prawa = pełna (0.5 - 5.0).</p>
                        </div>
                        <div id="detail-rating-stats" class="text-sm text-mist text-right"></div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        <div id="detail-half-stars" class="half-stars relative inline-block text-3xl leading-none">
                            <span class="stars-empty text-amber-300/30 select-none">★★★★★</span>
                            <span class="stars-filled absolute top-0 left-0 text-amber-400 overflow-hidden whitespace-nowrap select-none" style="width: 0%; transition: width 0.15s;">★★★★★</span>
                            <div class="zones absolute top-0 left-0 w-full h-full flex">
                                <?php for ($i = 1; $i <= 10; $i++): $val = $i / 2; ?>
                                    <button type="button" data-detail-rate="<?= $val ?>" class="flex-1 cursor-pointer bg-transparent border-0 p-0" title="<?= $val ?>"></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <span id="detail-current-value" class="ml-2 text-base font-semibold text-amber-500 min-w-[36px]"></span>
                        <button type="button" id="detail-rating-clear" class="ml-3 text-xs text-mist hover:text-red-500 transition hidden">Usuń moją ocenę</button>
                    </div>
                </div>

                <!-- Galeria mediow -->
                <div id="detail-media" class="space-y-5">
                    <!-- Sekcje zdjec/wideo/linkow wstawiane dynamicznie -->
                </div>

                <!-- Sekcja upload - kazdy uczestnik moze dorzucic media do dowolnego miejsca -->
                <div id="detail-uploader" class="hidden mt-6 pt-6 border-t border-mist/15">
                    <h4 class="font-display font-bold text-lg mb-1 text-ink dark:text-pale">Dodaj media</h4>
                    <p class="text-xs text-mist mb-3">Każdy z ekipy może dorzucić zdjęcia, wideo i linki - przekonaj resztę że warto tu pojechać.</p>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <!-- Upload zdjecia -->
                        <label class="cursor-pointer flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-mist/30 hover:border-primary/50 hover:bg-primary/5 transition">
                            <span class="text-2xl">📷</span>
                            <span class="text-sm font-medium text-ink dark:text-pale">Zdjęcie</span>
                            <span class="text-xs text-mist">max 5 sztuk, 5MB</span>
                            <input type="file" id="upload-image" accept="image/jpeg,image/png,image/webp" class="hidden">
                        </label>
                        <!-- Upload wideo -->
                        <label class="cursor-pointer flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-mist/30 hover:border-primary/50 hover:bg-primary/5 transition">
                            <span class="text-2xl">🎬</span>
                            <span class="text-sm font-medium text-ink dark:text-pale">Wideo</span>
                            <span class="text-xs text-mist">max 3 sztuki, 50MB</span>
                            <input type="file" id="upload-video" accept="video/mp4,video/webm,video/quicktime" class="hidden">
                        </label>
                        <!-- Dodaj link -->
                        <button type="button" id="add-link-btn"
                                class="flex flex-col items-center justify-center gap-2 p-4 rounded-xl border-2 border-dashed border-mist/30 hover:border-primary/50 hover:bg-primary/5 transition">
                            <span class="text-2xl">🔗</span>
                            <span class="text-sm font-medium text-ink dark:text-pale">Link</span>
                            <span class="text-xs text-mist">Booking, YouTube, blog</span>
                        </button>
                    </div>
                    <!-- Progress bar uploadu -->
                    <div id="upload-status" class="mt-4 hidden">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span id="upload-label" class="text-ink dark:text-pale font-medium"></span>
                            <span id="upload-percent" class="text-mist font-mono">0%</span>
                        </div>
                        <div class="w-full h-2.5 bg-mist/15 rounded-full overflow-hidden">
                            <div id="upload-progress-bar" class="h-full bg-primary-deep transition-all duration-200 ease-out" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox - pelnoekranowa galeria z nawigacja (obrazy + wideo) -->
    <div id="lightbox" class="fixed inset-0 z-[10001] hidden items-center justify-center select-none" style="background:#000">
        <button type="button" id="lightbox-close" aria-label="Zamknij"
                class="absolute top-4 right-4 w-12 h-12 rounded-full text-white transition flex items-center justify-center z-10"
                style="background:rgba(255,255,255,0.15); backdrop-filter: blur(8px)">
            <span class="iconify" data-icon="ph:x-bold" style="font-size:22px"></span>
        </button>
        <button type="button" id="lightbox-prev" aria-label="Poprzednie"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full text-white transition flex items-center justify-center z-10"
                style="background:rgba(255,255,255,0.15); backdrop-filter: blur(8px)">
            <span class="iconify" data-icon="ph:caret-left-bold" style="font-size:22px"></span>
        </button>
        <button type="button" id="lightbox-next" aria-label="Następne"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full text-white transition flex items-center justify-center z-10"
                style="background:rgba(255,255,255,0.15); backdrop-filter: blur(8px)">
            <span class="iconify" data-icon="ph:caret-right-bold" style="font-size:22px"></span>
        </button>
        <div id="lightbox-counter" class="absolute top-4 left-4 px-3 py-1.5 rounded-full text-white text-sm font-mono z-10"
             style="background:rgba(255,255,255,0.15); backdrop-filter: blur(8px)"></div>

        <div id="lightbox-media-container" class="max-w-full max-h-[88vh] flex items-center justify-center">
            <!-- img albo video wstawiane dynamicznie przez JS -->
        </div>

        <!-- Atrybucja: typ + zrodlo -->
        <div id="lightbox-caption" class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full text-white text-sm z-10"
             style="background:rgba(255,255,255,0.15); backdrop-filter: blur(8px)"></div>
    </div>

    <!-- Modal dodawania linka (proste) -->
    <div id="link-modal" class="fixed inset-0 z-[10000] hidden bg-black/70 items-center justify-center p-4">
        <div class="bg-paper dark:bg-deep rounded-2xl shadow-pop-lg max-w-md w-full p-6">
            <h3 class="font-display font-bold text-xl mb-4 text-ink dark:text-pale">Dodaj link</h3>
            <form id="link-form" class="space-y-3">
                <input type="url" id="link-url" required placeholder="https://..."
                       class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
                <input type="text" id="link-caption" maxlength="300" placeholder="Opis (opcjonalnie, np. 'Recenzja na blogu')"
                       class="w-full px-4 py-2.5 rounded-xl bg-cream dark:bg-night border-2 border-mist/20 focus:border-primary text-ink dark:text-pale outline-none transition">
                <div id="link-error" class="hidden p-2.5 rounded-lg bg-red-100 dark:bg-red-950/40 border border-red-300 text-sm text-red-700"></div>
                <div class="flex gap-2 pt-2">
                    <button type="button" id="link-cancel" class="flex-1 px-4 py-2.5 rounded-xl bg-mist/15 text-ink dark:text-pale font-medium hover:bg-mist/25 transition">Anuluj</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-primary-deep text-white font-semibold hover:bg-primary transition">Dodaj</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.__PLACES_CONFIG__ = {
            csrf: <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>,
            myParticipantId: <?= (int) $participant->id ?>,
            urls: {
                create: <?= json_encode(url('/p/' . $participant->accessToken . '/places'), JSON_UNESCAPED_SLASHES) ?>,
                deleteTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/delete'), JSON_UNESCAPED_SLASHES) ?>,
                mediaListTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/media'), JSON_UNESCAPED_SLASHES) ?>,
                mediaUploadTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/media/upload'), JSON_UNESCAPED_SLASHES) ?>,
                mediaLinkTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/media/link'), JSON_UNESCAPED_SLASHES) ?>,
                mediaDeleteTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/media/MID/delete'), JSON_UNESCAPED_SLASHES) ?>,
                editTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/edit'), JSON_UNESCAPED_SLASHES) ?>,
                voteTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/vote'), JSON_UNESCAPED_SLASHES) ?>,
                voteDeleteTemplate: <?= json_encode(url('/p/' . $participant->accessToken . '/places/ID/vote/delete'), JSON_UNESCAPED_SLASHES) ?>,
                routes: <?= json_encode(url('/p/' . $participant->accessToken . '/routes'), JSON_UNESCAPED_SLASHES) ?>,
                assetBase: <?= json_encode(rtrim(env('APP_URL', ''), '/') . '/', JSON_UNESCAPED_SLASHES) ?>,
            },
        };
    </script>
    <!-- places.js MUSI byc przed Google Maps loader - definiuje initPlacesMap callback -->
    <script src="<?= e(asset('assets/js/places.js')) ?>"></script>
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=places,marker&language=pl&region=PL&loading=async&callback=initPlacesMap"></script>
    <?php endif; ?>
    </div>
</section>
