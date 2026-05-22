<?php
/**
 * Mini-wizard ocen - uczestnik klika gwiazdki, przechodzi do nastepnego miejsca.
 *
 * @var \App\Models\Trip $trip
 * @var \App\Models\Participant $participant
 * @var list<\App\Models\TripPlace> $toRate
 * @var array<int, array{nickname:string,color:string}> $authors
 * @var int $totalCount
 * @var int $remainingCount
 * @var string $googleMapsApiKey
 */
use App\Helpers\Csrf;

$csrfToken = Csrf::token();
$placesJson = json_encode(array_map(static function ($p) use ($authors) {
    $author = $authors[$p->participantId] ?? ['nickname' => '?', 'color' => '#6B7280'];
    return [
        'id'              => $p->id,
        'name'            => $p->name,
        'address'         => $p->address,
        'description'     => $p->description,
        'lat'             => $p->lat,
        'lng'             => $p->lng,
        'google_place_id' => $p->googlePlaceId,
        'visit_minutes'   => $p->visitMinutes,
        'author'          => $author['nickname'],
        'author_color'    => $author['color'],
    ];
}, $toRate), JSON_UNESCAPED_UNICODE);
$mediaUrlTemplate = url('/p/' . $participant->accessToken . '/places/ID/media');
$voteUrlTemplate  = url('/p/' . $participant->accessToken . '/places/ID/vote');
$mapUrl           = url('/p/' . $participant->accessToken . '/atrakcje');
$assetBase        = rtrim((string) env('APP_URL', ''), '/') . '/';
?>

<section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <a href="<?= e($mapUrl) ?>" class="text-sm text-mist hover:text-primary transition inline-flex items-center gap-1">
            ← Wróć do mapy
        </a>
        <span id="rater-progress" class="text-sm font-mono text-mist"></span>
    </div>

    <h1 class="font-display font-bold text-3xl md:text-4xl text-ink dark:text-pale mb-2">
        ⭐ Oceń miejsca
    </h1>
    <p class="text-mist mb-6">
        Kliknij gwiazdki - przechodzimy automatycznie do następnego.
    </p>

    <!-- Progress bar -->
    <div class="w-full h-2 bg-mist/15 rounded-full overflow-hidden mb-6">
        <div id="progress-bar" class="h-full bg-primary-deep transition-all duration-300" style="width: 0%"></div>
    </div>

    <!-- Karta z aktualnym miejscem -->
    <div id="rater-card" class="hidden rounded-2xl bg-paper dark:bg-deep border-2 border-mist/15 overflow-hidden">
        <div class="p-5 md:p-6">
            <div class="flex items-start gap-3 mb-3">
                <span id="rater-author-badge" class="inline-flex items-center justify-center w-8 h-8 rounded-full text-white text-sm font-bold shrink-0 mt-0.5"></span>
                <div class="flex-1 min-w-0">
                    <h2 id="rater-name" class="font-display font-bold text-xl text-ink dark:text-pale leading-tight"></h2>
                    <p id="rater-address" class="text-sm text-mist mt-0.5"></p>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        <span id="rater-visit-chip" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/30 text-xs text-primary font-medium leading-none">
                            <span class="text-sm leading-none" aria-hidden="true">⏱️</span>
                            <span id="rater-visit-text" class="leading-none">1h</span>
                        </span>
                        <span id="rater-author-info" class="text-xs text-mist"></span>
                    </div>
                </div>
            </div>

            <p id="rater-description" class="text-ink dark:text-pale text-sm leading-relaxed mb-4 whitespace-pre-line"></p>

            <!-- Galeria mediow (Google + user uploaded) -->
            <div id="rater-media" class="space-y-3 mb-5"></div>

            <!-- Gwiazdki (half-star support) -->
            <div class="border-t border-mist/15 pt-5">
                <h3 class="font-semibold text-sm text-ink dark:text-pale mb-2">Czy chcesz tu pojechać?</h3>
                <p class="text-xs text-mist mb-3">Oceń od 0.5 (raczej nie) do 5.0 (koniecznie!). Klik lewa połowa gwiazdki = .5, prawa = pełna.</p>
                <div class="flex items-center justify-center gap-2">
                    <div class="half-stars relative inline-block text-5xl leading-none" data-current="0">
                        <span class="stars-empty text-amber-300/30 select-none">★★★★★</span>
                        <span class="stars-filled absolute top-0 left-0 text-amber-400 overflow-hidden whitespace-nowrap select-none" style="width: 0%; transition: width 0.15s;">★★★★★</span>
                        <div class="zones absolute top-0 left-0 w-full h-full flex">
                            <?php for ($i = 1; $i <= 10; $i++): $val = $i / 2; ?>
                                <button type="button" data-rate="<?= $val ?>" class="flex-1 cursor-pointer bg-transparent border-0 p-0" title="<?= $val ?>"></button>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <span id="rater-current-value" class="ml-3 text-lg font-semibold text-amber-500 min-w-[40px]"></span>
                </div>
                <button type="button" id="rater-skip"
                        class="mt-4 w-full text-sm text-mist hover:text-primary transition">
                    Pomiń to miejsce →
                </button>
            </div>
        </div>
    </div>

    <!-- Ekran końcowy -->
    <div id="rater-done" class="hidden rounded-2xl bg-secondary/10 border-2 border-secondary/30 p-8 text-center">
        <div class="text-5xl mb-3">🎉</div>
        <h2 class="font-display font-bold text-2xl text-ink dark:text-pale mb-2">Wszystko ocenione!</h2>
        <p class="text-mist mb-5">Dzięki za pomoc w decyzji - im więcej ocen, tym lepsze sugestie tras.</p>
        <a href="<?= e($mapUrl) ?>"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-105 transition shadow-pop">
            🗺️ Wróć do mapy
        </a>
    </div>

    <!-- Ekran "nic do oceny" - jeśli toRate jest puste od początku -->
    <?php if (empty($toRate)): ?>
    <div class="rounded-2xl bg-secondary/10 border-2 border-secondary/30 p-8 text-center">
        <div class="text-5xl mb-3">✓</div>
        <h2 class="font-display font-bold text-2xl text-ink dark:text-pale mb-2">Nic do oceny!</h2>
        <p class="text-mist mb-5">Oceniłeś już wszystkie <?= $totalCount ?> miejsc dodanych przez ekipę.</p>
        <a href="<?= e($mapUrl) ?>"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-primary-deep text-white font-semibold hover:bg-primary hover:scale-105 transition shadow-pop">
            🗺️ Wróć do mapy
        </a>
    </div>
    <?php endif; ?>
</section>

<!-- Lightbox - pelnoekranowa galeria (obrazy + wideo) -->
<div id="lb" class="fixed inset-0 z-[10001] hidden bg-black/95 items-center justify-center select-none">
    <button type="button" id="lb-close" aria-label="Zamknij"
            class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">×</button>
    <button type="button" id="lb-prev" aria-label="Poprzednie"
            class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">‹</button>
    <button type="button" id="lb-next" aria-label="Następne"
            class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 text-white text-2xl transition flex items-center justify-center z-10">›</button>
    <div id="lb-counter" class="absolute top-4 left-4 px-3 py-1.5 rounded-full bg-white/10 text-white text-sm font-mono z-10"></div>
    <div id="lb-media" class="max-w-full max-h-[88vh] flex items-center justify-center"></div>
    <div id="lb-caption" class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-white/10 text-white text-sm z-10 backdrop-blur-sm"></div>
</div>

<?php if (!empty($toRate) && $googleMapsApiKey !== ''): ?>
<script>
window.__RATER__ = {
    csrf: <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES) ?>,
    places: <?= $placesJson ?>,
    mediaUrlTemplate: <?= json_encode($mediaUrlTemplate, JSON_UNESCAPED_SLASHES) ?>,
    voteUrlTemplate: <?= json_encode($voteUrlTemplate, JSON_UNESCAPED_SLASHES) ?>,
    assetBase: <?= json_encode($assetBase, JSON_UNESCAPED_SLASHES) ?>,
};
</script>
<script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?= e($googleMapsApiKey) ?>&libraries=places&language=pl&region=PL&loading=async&callback=initRater"></script>
<script>
(function () {
    'use strict';
    let PlaceCtor = null;
    let currentIdx = 0;
    const cfg = window.__RATER__;
    const places = cfg.places;
    const total = places.length;

    window.initRater = async function () {
        try {
            const lib = await google.maps.importLibrary('places');
            PlaceCtor = lib.Place;
        } catch (e) {}
        showCurrent();
        bindStars();
        document.getElementById('rater-skip')?.addEventListener('click', () => next());
    };

    function bindStars() {
        document.querySelectorAll('.half-stars .zones button').forEach(btn => {
            btn.addEventListener('click', () => {
                const score = parseFloat(btn.getAttribute('data-rate'));
                vote(score);
            });
            btn.addEventListener('mouseenter', () => highlight(parseFloat(btn.getAttribute('data-rate'))));
            btn.addEventListener('mouseleave', () => highlight(0));
        });
    }

    function highlight(score) {
        const filled = document.querySelector('.half-stars .stars-filled');
        const current = document.getElementById('rater-current-value');
        const pct = (score / 5) * 100;
        if (filled) filled.style.width = pct + '%';
        if (current) current.textContent = score > 0 ? score.toFixed(1).replace('.', ',') : '';
    }

    function showCurrent() {
        if (currentIdx >= total) {
            // Zakonczone
            document.getElementById('rater-card').classList.add('hidden');
            document.getElementById('rater-done').classList.remove('hidden');
            updateProgress();
            return;
        }
        const place = places[currentIdx];
        document.getElementById('rater-card').classList.remove('hidden');
        document.getElementById('rater-done').classList.add('hidden');
        document.getElementById('rater-name').textContent = place.name;
        document.getElementById('rater-address').textContent = place.address || '';
        document.getElementById('rater-description').textContent = place.description || '';

        const badge = document.getElementById('rater-author-badge');
        badge.style.background = place.author_color;
        badge.textContent = (place.author || '?').charAt(0).toUpperCase();
        document.getElementById('rater-author-info').textContent = '— dodał(a): ' + place.author;
        document.getElementById('rater-visit-text').textContent = formatVisit(place.visit_minutes);

        highlight(0);
        updateProgress();
        loadMedia(place);
    }

    function formatVisit(vm) {
        vm = parseInt(vm, 10) || 60;
        if (vm < 60) return vm + ' min';
        if (vm % 60 === 0) return (vm / 60) + ' h';
        return (vm / 60).toFixed(1).replace('.', ',') + ' h';
    }

    function updateProgress() {
        const done = currentIdx;
        const pct = total > 0 ? (done / total * 100) : 0;
        document.getElementById('progress-bar').style.width = pct + '%';
        document.getElementById('rater-progress').textContent = `${Math.min(done + 1, total)} z ${total}`;
        if (currentIdx >= total) {
            document.getElementById('progress-bar').style.width = '100%';
            document.getElementById('rater-progress').textContent = `${total} z ${total} ✓`;
        }
    }

    let raterLightboxItems = [];

    async function loadMedia(place) {
        const container = document.getElementById('rater-media');
        container.innerHTML = '<p class="text-xs text-mist italic">Ładuję media...</p>';

        const [userP, googleP] = await Promise.allSettled([
            fetchUserMedia(place.id),
            fetchGooglePhotos(place.google_place_id),
        ]);
        const userMedia = userP.status === 'fulfilled' ? userP.value : [];
        const googlePhotos = googleP.status === 'fulfilled' ? googleP.value : [];

        let html = '';
        raterLightboxItems = []; // reset

        if (googlePhotos.length > 0) {
            html += '<div><h4 class="text-xs font-semibold text-mist uppercase mb-1.5">📸 Zdjęcia z Google</h4><div class="grid grid-cols-3 gap-1.5">';
            for (const ph of googlePhotos) {
                const thumb = ph.getURI ? ph.getURI({ maxWidth: 300, maxHeight: 200 }) : ph.getUrl({ maxWidth: 300, maxHeight: 200 });
                const big = ph.getURI ? ph.getURI({ maxWidth: 1600, maxHeight: 1200 }) : ph.getUrl({ maxWidth: 1600, maxHeight: 1200 });
                const idx = raterLightboxItems.length;
                raterLightboxItems.push({ type: 'image', url: big, source: 'Google' });
                html += `<button type="button" data-lb-idx="${idx}" class="block hover:opacity-90 transition cursor-zoom-in bg-transparent border-0 p-0"><img src="${esc(thumb)}" alt="" class="w-full h-20 object-cover rounded-lg" loading="lazy"></button>`;
            }
            html += '</div></div>';
        }

        const images = userMedia.filter(m => m.type === 'image');
        if (images.length > 0) {
            html += '<div><h4 class="text-xs font-semibold text-mist uppercase mb-1.5">📷 Wasze zdjęcia</h4><div class="grid grid-cols-3 gap-1.5">';
            for (const m of images) {
                const src = cfg.assetBase + m.file_path;
                const idx = raterLightboxItems.length;
                raterLightboxItems.push({ type: 'image', url: src, source: place.author });
                html += `<button type="button" data-lb-idx="${idx}" class="block hover:opacity-90 transition cursor-zoom-in bg-transparent border-0 p-0"><img src="${esc(src)}" alt="" class="w-full h-20 object-cover rounded-lg" loading="lazy"></button>`;
            }
            html += '</div></div>';
        }

        const videos = userMedia.filter(m => m.type === 'video');
        if (videos.length > 0) {
            html += '<div><h4 class="text-xs font-semibold text-mist uppercase mb-1.5">🎬 Wideo</h4><div class="grid grid-cols-3 gap-1.5">';
            for (const m of videos) {
                const src = cfg.assetBase + m.file_path;
                const idx = raterLightboxItems.length;
                raterLightboxItems.push({ type: 'video', url: src, source: place.author });
                html += `<button type="button" data-lb-idx="${idx}" class="block relative hover:opacity-90 transition cursor-zoom-in bg-transparent border-0 p-0">
                    <video preload="metadata" class="w-full h-20 object-cover rounded-lg pointer-events-none"><source src="${esc(src)}"></video>
                    <span class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <span class="w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center text-sm">▶</span>
                    </span>
                </button>`;
            }
            html += '</div></div>';
        }

        const links = userMedia.filter(m => m.type === 'link');
        if (links.length > 0) {
            html += '<div><h4 class="text-xs font-semibold text-mist uppercase mb-1.5">🔗 Linki</h4><ul class="space-y-1">';
            for (const m of links) {
                html += `<li><a href="${esc(m.url)}" target="_blank" rel="noopener" class="text-sm text-secondary hover:underline">${esc(m.caption || m.url)}</a></li>`;
            }
            html += '</ul></div>';
        }

        if (html === '') html = '<p class="text-xs text-mist italic">Brak dodatkowych media.</p>';
        container.innerHTML = html;

        // Bind lightbox handlers
        container.querySelectorAll('[data-lb-idx]').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.getAttribute('data-lb-idx'), 10);
                openLightbox(raterLightboxItems, idx);
            });
        });
    }

    async function fetchUserMedia(placeId) {
        const url = cfg.mediaUrlTemplate.replace('ID', placeId);
        const r = await fetch(url);
        const data = await r.json();
        return data.ok ? (data.media || []) : [];
    }

    async function fetchGooglePhotos(gid) {
        if (!gid || !PlaceCtor) return [];
        try {
            const placeObj = new PlaceCtor({ id: gid });
            await placeObj.fetchFields({ fields: ['photos'] });
            return (placeObj.photos || []).slice(0, 6);
        } catch (e) {
            return [];
        }
    }

    async function vote(score) {
        const place = places[currentIdx];
        if (!place) return;
        // Disable stars na czas
        document.querySelectorAll('.rater-star').forEach(s => s.disabled = true);
        try {
            const fd = new FormData();
            fd.append('_csrf', cfg.csrf);
            fd.append('score', String(score));
            const url = cfg.voteUrlTemplate.replace('ID', place.id);
            const r = await fetch(url, { method: 'POST', body: fd });
            const data = await r.json();
            if (data.ok) {
                // Krotki visual feedback - podswietl wybrana ilosc gwiazdek
                highlight(score);
                setTimeout(() => next(), 350);
            } else {
                alert(data.error || 'Nie udało się zapisać oceny.');
                document.querySelectorAll('.rater-star').forEach(s => s.disabled = false);
            }
        } catch (e) {
            alert('Błąd sieci.');
            document.querySelectorAll('.rater-star').forEach(s => s.disabled = false);
        }
    }

    function next() {
        currentIdx++;
        document.querySelectorAll('.rater-star').forEach(s => s.disabled = false);
        showCurrent();
    }

    function esc(s) {
        return String(s ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
    }

    // Lightbox ===================================================
    let lbItems = [];
    let lbIdx = 0;

    function setupLightbox() {
        const lb = document.getElementById('lb');
        if (!lb) return;
        document.getElementById('lb-close')?.addEventListener('click', closeLightbox);
        document.getElementById('lb-prev')?.addEventListener('click', () => navLightbox(-1));
        document.getElementById('lb-next')?.addEventListener('click', () => navLightbox(1));
        lb.addEventListener('click', (e) => { if (e.target === lb) closeLightbox(); });

        document.addEventListener('keydown', (e) => {
            if (lb.classList.contains('hidden')) return;
            if (e.key === 'Escape') closeLightbox();
            else if (e.key === 'ArrowLeft') navLightbox(-1);
            else if (e.key === 'ArrowRight') navLightbox(1);
        });

        let touchStartX = 0, touchStartY = 0;
        lb.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });
        lb.addEventListener('touchend', (e) => {
            const dx = e.changedTouches[0].screenX - touchStartX;
            const dy = e.changedTouches[0].screenY - touchStartY;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) navLightbox(dx > 0 ? -1 : 1);
        }, { passive: true });
    }

    function openLightbox(items, idx) {
        if (!Array.isArray(items) || items.length === 0) return;
        lbItems = items;
        lbIdx = Math.max(0, Math.min(idx, items.length - 1));
        showLightboxItem();
        const lb = document.getElementById('lb');
        lb.classList.remove('hidden');
        lb.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        const lb = document.getElementById('lb');
        lb.classList.add('hidden');
        lb.classList.remove('flex');
        document.body.style.overflow = '';
        const media = document.getElementById('lb-media');
        if (media) media.innerHTML = '';
    }

    function navLightbox(delta) {
        if (lbItems.length === 0) return;
        lbIdx = (lbIdx + delta + lbItems.length) % lbItems.length;
        showLightboxItem();
    }

    function showLightboxItem() {
        const container = document.getElementById('lb-media');
        const counter = document.getElementById('lb-counter');
        const caption = document.getElementById('lb-caption');
        const item = lbItems[lbIdx];
        if (!container || !item) return;
        if (item.type === 'video') {
            container.innerHTML = `<video src="${esc(item.url)}" controls autoplay class="max-w-full max-h-[88vh] rounded-lg"></video>`;
        } else {
            container.innerHTML = `<img src="${esc(item.url)}" alt="" class="max-w-full max-h-[88vh] object-contain">`;
        }
        if (counter) counter.textContent = (lbIdx + 1) + ' / ' + lbItems.length;
        if (caption) {
            const typeLabel = item.type === 'video' ? '🎬 Wideo' : '📸 Zdjęcie';
            caption.textContent = `${typeLabel} · ${item.source || '?'}`;
        }
        const showNav = lbItems.length > 1;
        document.getElementById('lb-prev')?.classList.toggle('hidden', !showNav);
        document.getElementById('lb-next')?.classList.toggle('hidden', !showNav);
    }

    setupLightbox();
})();
</script>
<?php endif; ?>
