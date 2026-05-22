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

<section class="mx-auto max-w-7xl 3xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 py-8 md:py-12">

    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <a href="<?= e(url('/p/' . $participant->accessToken)) ?>"
               class="text-sm text-mist hover:text-primary transition inline-flex items-center gap-1 mb-2">
                ← Wróć
            </a>
            <h1 class="font-display font-bold text-3xl md:text-4xl 3xl:text-5xl text-ink dark:text-pale">
                🗺️ Atrakcje ekipy
            </h1>
            <p class="mt-2 text-mist max-w-2xl">
                Dodaj konkretne miejsca, które chcesz odwiedzić. Inni zobaczą Twoje propozycje
                i (wkrótce) ocenią ile chętnie tam pojadą. Im więcej miejsc, tym lepsze trasy.
            </p>
        </div>
        <?php if ($hasApiKey): ?>
        <button id="add-place-btn" type="button"
                class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-105 transition shadow-pop self-start md:self-end">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
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
            <div id="places-map"
                 data-places='<?= e($placesJson) ?>'
                 data-authors='<?= e($authorsJson) ?>'
                 data-my-color="<?= e($myColor) ?>"
                 style="height: 75vh; min-height: 520px;"></div>
        </div>

        <!-- Lista miejsc -->
        <aside class="rounded-2xl border border-mist/15 bg-paper dark:bg-deep p-5 max-h-[75vh] overflow-y-auto">
            <h3 class="font-display font-bold text-lg text-ink dark:text-pale mb-4">
                Miejsca <span class="text-mist font-normal text-sm">(<?= count($places) ?>)</span>
            </h3>
            <div id="places-list" class="space-y-3">
                <?php if (empty($places)): ?>
                    <p class="text-mist text-sm italic">
                        Nikt jeszcze nie dodał miejsca. Bądź pierwszy! Kliknij "Dodaj miejsce"
                        i wyszukaj nazwę miejsca lub kliknij na mapie.
                    </p>
                <?php else: ?>
                    <?php foreach ($places as $p):
                        $author = $authors[$p->participantId] ?? ['nickname' => '?', 'color' => '#6B7280', 'avatar' => null];
                        $isMine = $p->participantId === $participant->id;
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
                                <div class="mt-2 flex items-center gap-2 text-xs text-mist">
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

    <!-- Modal dodawania miejsca -->
    <div id="add-place-modal" class="fixed inset-0 z-[9999] hidden bg-black/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-paper dark:bg-deep rounded-2xl shadow-pop-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
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
        <div class="bg-paper dark:bg-deep rounded-2xl shadow-pop-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4 gap-3">
                    <div class="flex-1 min-w-0">
                        <h3 id="detail-name" class="font-display font-bold text-2xl text-ink dark:text-pale"></h3>
                        <p id="detail-address" class="text-sm text-mist mt-1"></p>
                        <p id="detail-author" class="text-xs text-mist mt-1.5"></p>
                    </div>
                    <button type="button" id="detail-close" class="text-mist hover:text-primary transition text-3xl leading-none shrink-0">×</button>
                </div>

                <p id="detail-description" class="text-ink dark:text-pale leading-relaxed mb-5 whitespace-pre-line"></p>

                <!-- Galeria mediow -->
                <div id="detail-media" class="space-y-5">
                    <!-- Sekcje zdjec/wideo/linkow wstawiane dynamicznie -->
                </div>

                <!-- Sekcja upload (tylko dla autora) -->
                <div id="detail-uploader" class="hidden mt-6 pt-6 border-t border-mist/15">
                    <h4 class="font-display font-bold text-lg mb-3 text-ink dark:text-pale">Dodaj media</h4>
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
                            <span class="text-xs text-mist">max 1 sztuka, 50MB</span>
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
                    <div id="upload-status" class="mt-3 text-sm hidden"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox - pelnoekranowa galeria z nawigacja (obrazy + wideo) -->
    <div id="lightbox" class="fixed inset-0 z-[10001] hidden bg-black/95 items-center justify-center select-none">
        <button type="button" id="lightbox-close" aria-label="Zamknij"
                class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">×</button>
        <button type="button" id="lightbox-prev" aria-label="Poprzednie"
                class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">‹</button>
        <button type="button" id="lightbox-next" aria-label="Następne"
                class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">›</button>
        <div id="lightbox-counter" class="absolute top-4 left-4 px-3 py-1.5 rounded-full bg-white/10 text-white text-sm font-mono z-10"></div>

        <div id="lightbox-media-container" class="max-w-full max-h-[88vh] flex items-center justify-center">
            <!-- img albo video wstawiane dynamicznie przez JS -->
        </div>

        <!-- Atrybucja: typ + zrodlo -->
        <div id="lightbox-caption" class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-white/10 text-white text-sm z-10 backdrop-blur-sm"></div>
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
                assetBase: <?= json_encode(rtrim(env('APP_URL', ''), '/') . '/', JSON_UNESCAPED_SLASHES) ?>,
            },
        };
    </script>
    <!-- places.js MUSI byc przed Google Maps loader - definiuje initPlacesMap callback -->
    <script src="<?= e(asset('assets/js/places.js')) ?>"></script>
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=places,marker&language=pl&region=PL&loading=async&callback=initPlacesMap"></script>
    <?php endif; ?>
</section>
